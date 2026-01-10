<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource;

class CreateNode extends CreateRecord
{
    protected static string $resource = NodeResource::class;
}
