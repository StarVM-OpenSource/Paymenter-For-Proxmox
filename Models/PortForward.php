<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class PortForward extends Model
{
    protected $table = 'ext_proxmox_port_forwards';

    protected $fillable = [
        'server_id',
        'ip_pool_id',
        'protocol',
        'external_port',
        'internal_port',
        'internal_ip',
        'description',
        'is_range',
        'range_size',
        'is_active',
    ];

    protected $casts = [
        'is_range' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function ipPool()
    {
        return $this->belongsTo(IPPool::class, 'ip_pool_id');
    }

    /**
     * 生成添加 iptables 规则的命令
     */
    public function toIptablesAddRules(): array
    {
        return $this->generateIptablesRules('-A');
    }

    /**
     * 生成删除 iptables 规则的命令
     */
    public function toIptablesDeleteRules(): array
    {
        return $this->generateIptablesRules('-D');
    }

    /**
     * 生成 iptables 规则
     * 
     * 网络拓扑说明:
     * - nat_interface: 公网接口 (如 vmbr0)，外部流量从这里进入
     * - 内网: 通过内部桥接 (如 vmbr1) 连接 VM
     * 
     * 规则逻辑:
     * 1. PREROUTING/DNAT: 在公网接口上捕获流量，修改目标为内网 VM
     * 2. FORWARD: 允许从公网接口到内网 VM 的流量（使用 conntrack 自动处理返回流量）
     */
    private function generateIptablesRules(string $action): array
    {
        $rules = [];
        $natIp = $this->ipPool->nat_ip;
        $interface = $this->ipPool->nat_interface; // 公网接口，如 vmbr0

        $protocols = $this->protocol === 'both' ? ['tcp', 'udp'] : [$this->protocol];

        foreach ($protocols as $proto) {
            if ($this->is_range && $this->range_size > 1) {
                // 端口范围映射
                $extPortEnd = $this->external_port + $this->range_size - 1;
                $intPortEnd = $this->internal_port + $this->range_size - 1;

                // PREROUTING (DNAT) - 在公网接口捕获流量
                $rules[] = "iptables -t nat {$action} PREROUTING -i {$interface} -p {$proto} " .
                    "--dport {$this->external_port}:{$extPortEnd} -j DNAT " .
                    "--to-destination {$this->internal_ip}:{$this->internal_port}-{$intPortEnd}";

                // FORWARD 规则 - 允许新连接到内网 VM（conntrack 会自动处理返回流量）
                $rules[] = "iptables {$action} FORWARD -p {$proto} -d {$this->internal_ip} " .
                    "--dport {$this->internal_port}:{$intPortEnd} -m conntrack --ctstate NEW -j ACCEPT";
            } else {
                // 单端口映射
                // PREROUTING (DNAT) - 在公网接口捕获流量
                $rules[] = "iptables -t nat {$action} PREROUTING -i {$interface} -p {$proto} " .
                    "--dport {$this->external_port} -j DNAT " .
                    "--to-destination {$this->internal_ip}:{$this->internal_port}";

                // FORWARD 规则 - 允许新连接到内网 VM
                $rules[] = "iptables {$action} FORWARD -p {$proto} -d {$this->internal_ip} " .
                    "--dport {$this->internal_port} -m conntrack --ctstate NEW -j ACCEPT";
            }
        }

        return $rules;
    }

    /**
     * 获取可读的协议名称
     */
    public function getProtocolLabelAttribute(): string
    {
        return match ($this->protocol) {
            'tcp' => 'TCP',
            'udp' => 'UDP',
            'both' => 'TCP/UDP',
            default => strtoupper($this->protocol),
        };
    }

    /**
     * 获取外部访问地址
     */
    public function getExternalAddressAttribute(): string
    {
        return $this->ipPool->nat_ip . ':' . $this->external_port;
    }
}
