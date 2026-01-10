<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use App\Models\Service;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class Server extends Model
{
    protected $table = 'ext_proxmox_servers';

    protected $fillable = [
        'user_id',
        'node_id',
        'os_id',
        'service_id',
        'vm_id',
        'primary_ipv4',
        'primary_ipv6',
        'status',
        'hostname',
        'bandwidth_usage',
        'port_range_start',
        'port_range_end',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function os()
    {
        return $this->belongsTo(OS::class);
    }

    public function ipAddresses()
    {
        return $this->hasMany(IPAddress::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function primaryIpv4()
    {
        return $this->belongsTo(IPAddress::class, 'primary_ipv4');
    }

    public function primaryIpv6()
    {
        return $this->belongsTo(IPAddress::class, 'primary_ipv6');
    }

    public function setting($settingKey)
    {
        // Read first from properties of the linked services, then from product settings
        if (
            $configOption = $this->service->configs()->whereHas('configOption', function ($query) use ($settingKey) {
                $query->where('env_variable', $settingKey);
            })->first()
        ) {
            return $configOption->value;
        } elseif ($this->service->properties->where('key', $settingKey)->first()) {
            return $this->service->properties->where('key', $settingKey)->first()->value;
        } else {
            $productSetting = $this->service->product->settings->where('key', $settingKey)->first();
            return $productSetting ? $productSetting->value : null;
        }
    }

    public function bandwidthLimit(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->setting('bandwidth_limit')) {
                    return $this->setting('bandwidth_limit') * 1024 * 1024 * 1024;
                } else {
                    return null;
                }
            }
        );
    }

    public function bandwidthUsagePercentage(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->bandwidthLimit) {
                    return ($this->bandwidth_usage / $this->bandwidthLimit) * 100;
                } else {
                    return null;
                }
            }
        );
    }

    public function isOverBandwidthLimit(): bool
    {
        if ($this->bandwidthLimit && $this->bandwidth_usage >= $this->bandwidthLimit) {
            return true;
        }
        return false;
    }

    public function portForwards()
    {
        return $this->hasMany(PortForward::class);
    }

    /**
     * 检查服务器是否使用 NAT 模式
     */
    public function isNatMode(): bool
    {
        return $this->primaryIpv4?->ipPool?->is_nat ?? false;
    }

    /**
     * 获取 NAT 公网 IP
     */
    public function getNatIpAttribute(): ?string
    {
        if ($this->isNatMode()) {
            return $this->primaryIpv4->ipPool->nat_ip;
        }
        return null;
    }

    /**
     * 获取产品设置的端口数量
     */
    public function getPortCountSettingAttribute(): int
    {
        return (int) ($this->setting('nat_port_count') ?? 20);
    }

    /**
     * 检查端口是否在此服务器的分配范围内
     */
    public function isPortInRange(int $port): bool
    {
        if (!$this->port_range_start || !$this->port_range_end) {
            return false;
        }
        return $port >= $this->port_range_start && $port <= $this->port_range_end;
    }

    /**
     * 获取此服务器的端口数量
     */
    public function getPortRangeCountAttribute(): int
    {
        if (!$this->port_range_start || !$this->port_range_end) {
            return 0;
        }
        return $this->port_range_end - $this->port_range_start + 1;
    }

    /**
     * 获取已使用的端口数量
     */
    public function getUsedPortCountAttribute(): int
    {
        return $this->portForwards()->count();
    }

    /**
     * 获取可用端口数量
     */
    public function getAvailablePortCountAttribute(): int
    {
        return max(0, $this->portRangeCount - $this->usedPortCount);
    }
}

