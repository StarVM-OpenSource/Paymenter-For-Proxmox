<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource;

class ListNodes extends ListRecords
{
    protected static string $resource = NodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
