<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

class Network extends Component
{
    public function render()
    {
        return view('proxmox::network')->layoutData([
            'sidebar' => true,
        ]);
    }
}
