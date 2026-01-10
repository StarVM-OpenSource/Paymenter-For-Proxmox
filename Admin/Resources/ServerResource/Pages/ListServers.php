<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource;

class ListServers extends ListRecords
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
