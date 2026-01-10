<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class OS extends Model
{
    protected $table = 'ext_proxmox_oses';

    protected $fillable = [
        // Shows to customer
        'name',
        // Type: qemu (VM) or lxc (Container)
        'type',
        // For QEMU: template VM ID to clone
        'vm_id',
        // For LXC: template path (e.g., local:vztmpl/ubuntu-22.04.tar.gz)
        'template',
    ];

    /**
     * Check if this OS is for LXC container
     */
    public function isLxc(): bool
    {
        return $this->type === 'lxc';
    }

    /**
     * Check if this OS is for QEMU VM
     */
    public function isQemu(): bool
    {
        return $this->type === 'qemu' || empty($this->type);
    }
}

