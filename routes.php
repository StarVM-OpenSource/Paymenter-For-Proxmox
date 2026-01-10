<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Backups\Index as BackupsIndex;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Index;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Network;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Overview;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Settings;
use Paymenter\Extensions\Servers\Proxmox\Livewire\VNC;

Route::group([
    'prefix' => 'servers',
    'as' => 'proxmox.',
    'middleware' => ['web', 'auth'],
], function () {
    Route::get('/', Index::class)->name('index');
    Route::get('{server}', Overview::class)->name('server.overview');
    Route::get('{server}/network', Network::class)->name('server.network');
    Route::get('{server}/backups', BackupsIndex::class)->name('server.backups');
    Route::get('{server}/vnc', VNC::class)->name('server.vnc');
    Route::get('{server}/settings', Settings::class)->name('server.settings');
    Route::get('{server}/ports', \Paymenter\Extensions\Servers\Proxmox\Livewire\PortForwards::class)->name('server.ports');
});
