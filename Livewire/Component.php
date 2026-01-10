<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use App\Livewire\Component as BaseComponent;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;

class Component extends BaseComponent
{
    /**
     * The server instance.
     */
    public Server $server;

    /**
     * Mount the component with the server. c899b1ae9b4480d641bb88e8bdf2024e
     *
     * @return void
     */
    public function mount(Server $server)
    {
        $this->server = $server;
        if ($server->user_id != auth()->id()) {
            abort(403);
        }
    }
}
