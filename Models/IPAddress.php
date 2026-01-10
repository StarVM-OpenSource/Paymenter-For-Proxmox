<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class IPAddress extends Model
{
    protected $table = 'ext_proxmox_ip_addresses';

    protected $fillable = [
        'ip',
        'gateway',
        'bridge',
        'netmask',
        'mac_address',
        'server_id',
        'type',
        'ip_pool_id',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function ipPool()
    {
        return $this->belongsTo(IPPool::class, 'ip_pool_id');
    }
}
