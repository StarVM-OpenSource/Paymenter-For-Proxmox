<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class IPPool extends Model
{
    protected $table = 'ext_proxmox_ip_pools';

    protected $fillable = [
        // Shows to customer
        'name',
        'network_interface',
        'mtu',
        'vlan',
        'firewall',
        // NAT mode fields
        'is_nat',
        'nat_ip',
        'nat_interface',
        'port_start',
        'port_end',
    ];

    protected $casts = [
        'is_nat' => 'boolean',
        'firewall' => 'boolean',
    ];

    public function nodes()
    {
        return $this->belongsToMany(Node::class, 'ext_proxmox_ip_pool_nodes', 'ip_pool_id', 'node_id');
    }

    public function ipAddresses()
    {
        return $this->hasMany(IPAddress::class, 'ip_pool_id', 'id');
    }

    public function portForwards()
    {
        return $this->hasMany(PortForward::class, 'ip_pool_id');
    }

    /**
     * 获取下一批可用端口
     */
    public function getNextAvailablePorts(int $count = 1): ?array
    {
        if (!$this->is_nat) {
            return null;
        }

        $usedPorts = $this->portForwards()->pluck('external_port')->toArray();
        $available = [];

        for ($port = $this->port_start; $port <= $this->port_end && count($available) < $count; $port++) {
            if (!in_array($port, $usedPorts)) {
                $available[] = $port;
            }
        }

        return count($available) === $count ? $available : null;
    }

    /**
     * 获取可用端口数量
     */
    public function getAvailablePortCountAttribute(): int
    {
        if (!$this->is_nat) {
            return 0;
        }

        $totalPorts = $this->port_end - $this->port_start + 1;
        $usedPorts = $this->portForwards()->count();

        return max(0, $totalPorts - $usedPorts);
    }

    /**
     * 获取已使用端口数量
     */
    public function getUsedPortCountAttribute(): int
    {
        return $this->portForwards()->count();
    }
}

