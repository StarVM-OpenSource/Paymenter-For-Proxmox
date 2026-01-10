<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use App\Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;

class Index extends Component
{
    public function render()
    {
        return view('proxmox::index', [
            'servers' => Server::where('user_id', Auth::id())->get(),
        ])->layoutData([
            'sidebar' => true,
        ]);
    }
}
