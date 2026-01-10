<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->form(function (DeleteAction $action) {
                    return [
                        Checkbox::make('deleteFromProxmox')
                            ->label('Also delete server from Proxmox')
                            ->default(true),
                    ];
                })
                ->action(function (array $data, Server $record): void {
                    try {
                        if (($data['deleteFromProxmox'] ?? false)) {
                            $proxmox = new Proxmox;
                            $proxmox->terminateServer($record->service, [], []);
                        }
                    } catch (Exception $e) {
                        report($e);

                        Notification::make('Error')
                            ->title('Error occured while deleting the related server:')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                    $record->delete();
                }),
            Action::make('reinstall')
                ->label('Reinstall')
                ->action(function () {
                    $proxmox = new Proxmox;
                    $proxmox->reinstall($this->record);

                    Notification::make()
                        ->title('Reinstalling server')
                        ->success()
                        ->body('The server is being reinstalled.')
                        ->send();
                })
                ->color('danger')
                ->requiresConfirmation()
                ->icon('ri-refresh-line'),
        ];
    }
}
