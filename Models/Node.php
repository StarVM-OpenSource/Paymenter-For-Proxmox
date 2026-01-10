<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $table = 'ext_proxmox_nodes';

    protected $fillable = [
        // Shows to customer
        'name',
        'memory',
        'disk',
        'cpu',
        'location_id',
        'storage_location',
        'backup_location',
        'vnc_enabled',
        // SSH configuration for NAT
        'ssh_host',
        'ssh_port',
        'ssh_password',
    ];

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function ipAddresses()
    {
        return $this->hasMany(IPAddress::class);
    }

    public function ipPools()
    {
        return $this->belongsToMany(IPPool::class, 'ext_proxmox_ip_pool_nodes', 'node_id', 'ip_pool_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function resourcesAvailable()
    {
        $servers = $this->servers()->with('service.product')->get();

        $totalMemory = $this->memory;
        $totalDisk = $this->disk;
        $totalCpu = $this->cpu;
        $usedMemory = 0;
        $usedDisk = 0;
        $usedCpu = 0;
        foreach ($servers as $server) {
            $usedMemory += (int) $server->service->product->settings()->where('key', 'memory')->first()->value ?? 0;
            $usedDisk += (int) $server->service->product->settings()->where('key', 'disk')->first()->value ?? 0;
            $usedCpu += (int) $server->service->product->settings()->where('key', 'cpu')->first()->value ?? 0;
        }

        return [
            'memory' => $totalMemory == 0 ? null : $totalMemory - $usedMemory,
            'disk' => $totalDisk == 0 ? null : $totalDisk - $usedDisk,
            'cpu' => $totalCpu == 0 ? null : $totalCpu - $usedCpu,
        ];
    }

    public function canStoreServer($memory, $disk, $cpu)
    {
        $resources = $this->resourcesAvailable();

        if ($resources['memory'] !== null && $resources['memory'] < $memory) {
            return false;
        }
        if ($resources['disk'] !== null && $resources['disk'] < $disk) {
            return false;
        }
        if ($resources['cpu'] !== null && $resources['cpu'] < $cpu) {
            return false;
        }

        return true;
    }
}
