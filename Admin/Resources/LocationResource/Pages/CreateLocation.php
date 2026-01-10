<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\LocationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\LocationResource;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;
}
