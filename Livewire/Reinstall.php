<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Reinstall extends Component
{
    public $os;

    public bool $visible = false;

    public $confirmReinstall = false;

    public function mount(Server $server)
    {
        parent::mount($server);
        $this->os = $server->os_id;
    }

    public function reinstall()
    {
        $this->validate([
            'os' => 'required',
        ]);
        if (!$this->confirmReinstall) {
            $this->notify('Please confirm the reinstall', 'error');

            return;
        }
        if (!$this->server->node->location->os->contains($this->os)) {
            $this->notify('The selected OS is not available for this server', 'error');

            return;
        }
        $this->server->os_id = $this->os;
        $this->server->save();

        $proxmox = new Proxmox;
        $proxmox->reinstall($this->server);

        $this->redirect(route('proxmox.server.overview', $this->server->id), true);
    }

    public function render()
    {
        return view('proxmox::reinstall', [
            'osList' => $this->server->node->location->os,
        ]);
    }
}
