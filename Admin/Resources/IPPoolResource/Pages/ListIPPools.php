<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource;

class ListIPPools extends ListRecords
{
    protected static string $resource = IPPoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
