<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class IPPoolNode extends Model
{
    protected $table = 'ext_proxmox_ip_pool_nodes';

    public $timestamps = false;

    protected $fillable = [
        'ip_pool_id',
        'node_id',
    ];
}
