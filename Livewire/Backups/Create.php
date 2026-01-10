<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire\Backups;

use Paymenter\Extensions\Servers\Proxmox\Livewire\Component;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Create extends Component
{
    public $visible;

    public $name;

    public $type = 'snapshot';

    public $compression = 'zstd';

    public function backup()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:snapshot,suspend,stop',
            'compression' => 'required|in:gzip,lzo,zstd,none',
        ]);

        $proxmox = new Proxmox;
        $proxmox->request('/nodes/' . $this->server->node->name . '/vzdump', 'post', data: [
            'vmid' => $this->server->vm_id,
            'storage' => $this->server->node->backup_location,
            'mode' => $this->type,
            'compress' => $this->compression == 'none' ? (int) false : $this->compression,
            'notes-template' => $this->name,
        ], location: $this->server->node->location);

        $this->notify('Backup started', 'success');

        $this->visible = false;

        $this->reset([
            'name',
            'type',
            'compression',
        ]);

    }

    public function render()
    {
        return view('proxmox::backups.create');
    }
}
