<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource;

class EditIPPool extends EditRecord
{
    protected static string $resource = IPPoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->ipAddresses()->where('server_id', '!=', null)->exists()),
        ];
    }
}
