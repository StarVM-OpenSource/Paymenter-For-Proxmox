<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Ipv4RelationManager extends RelationManager
{
    protected static string $relationship = 'ipaddresses';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip')
            ->columns([
                TextColumn::make('ip')->searchable(),
                TextColumn::make('gateway'),
                TextColumn::make('bridge'),
                TextColumn::make('netmask'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AssociateAction::make()
                    ->modelLabel('IPv4')
                    ->preloadRecordSelect()
                    ->label('Add')
                    ->recordSelectOptionsQuery(function (Builder $query, RelationManager $livewire) {
                        $node = $livewire->ownerRecord->node;
                        if (!$node) {
                            return $query;
                        }
                        $ipPoolIds = $node->ipPools->pluck('id');
                        $query->whereIn('ip_pool_id', $ipPoolIds);
                        $query->where('server_id', null);

                        return $query;
                    }),
            ])
            ->recordActions([
                DissociateAction::make()
                    ->label('Remove')
                    ->before(function (DissociateAction $action, RelationManager $livewire) {
                        if ($livewire->ownerRecord->primary_ip == $action->getRecord()->id) {
                            Notification::make()
                                ->danger()
                                ->title('You cannot remove the primary IP')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ]);
    }
}
