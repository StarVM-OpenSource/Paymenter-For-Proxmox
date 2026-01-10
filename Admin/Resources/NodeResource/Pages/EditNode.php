<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource;

class EditNode extends EditRecord
{
    protected static string $resource = NodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->servers()->exists()),
        ];
    }
}
