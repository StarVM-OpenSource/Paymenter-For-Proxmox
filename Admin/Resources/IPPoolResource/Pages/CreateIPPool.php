<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource;

class CreateIPPool extends CreateRecord
{
    protected static string $resource = IPPoolResource::class;
}
