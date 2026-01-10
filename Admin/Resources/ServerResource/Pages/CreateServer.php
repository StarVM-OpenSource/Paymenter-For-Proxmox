<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\Pages;

use App\Models\Service;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource;
use Paymenter\Extensions\Servers\Proxmox\Models\IPAddress;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['user_id'] = Service::find($data['service_id'])->user_id;

        $record = static::getModel()::create($data);

        // Assign the primary_ipv4 and primary_ipv6 addresses
        if ($data['primary_ipv4']) {
            $ip = IPAddress::find($data['primary_ipv4']);
            $ip->server_id = $record->id;
            $ip->save();
        }

        if ($data['primary_ipv6']) {
            $ip = IPAddress::find($data['primary_ipv6']);
            $ip->server_id = $record->id;
            $ip->save();
        }

        return $record;
    }
}
