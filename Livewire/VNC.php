<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class VNC extends Component
{
    public function mount(Server $server)
    {
        parent::mount($server);

        // Check if server node has VNC enabled
        if (!$this->server->node->vnc_enabled) {
            abort(403, 'VNC is not enabled on this server node.');
        }
    }

    public function render()
    {
        $proxmox = new Proxmox;

        return view('proxmox::vnc', [
            'vnc' => $proxmox->vnc($this->server),
        ])->layoutData([
            'sidebar' => true,
        ]);
    }
}
