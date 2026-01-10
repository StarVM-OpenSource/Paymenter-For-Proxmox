<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'ext_proxmox_locations';

    protected $fillable = [
        // Shows to customer
        'name',
        'server_placement',
        'host',
        'user',
        'token',
        'verify_ssl',
    ];

    public function nodes()
    {
        return $this->hasMany(Node::class, 'location_id');
    }

    public function os()
    {
        return $this->hasMany(OS::class);
    }

    public function servers()
    {
        return $this->hasManyThrough(Server::class, Node::class, 'location_id', 'node_id');
    }
}
