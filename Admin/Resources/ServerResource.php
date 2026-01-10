<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\Pages\CreateServer;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\Pages\EditServer;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\Pages\ListServers;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\ServerResource\RelationManagers\Ipv4RelationManager;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Proxmox';

    protected static ?string $label = 'Server';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-server-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-server-fill';

    protected static ?string $slug = 'proxmox-servers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('node_id')
                    ->label('Node')
                    ->required()
                    ->relationship('node', 'name')
                    ->searchable()
                    ->reactive()
                    ->disabledOn('edit')
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('os_id', null);
                    }),
                Select::make('os_id')
                    ->label('OS')
                    ->required()
                    ->relationship('os', 'name')
                    ->searchable()
                    ->helperText('Select the OS for the server (if editing, requires reinstallation)')
                    ->reactive(),
                Select::make('service_id')
                    ->label('Service')
                    ->required()
                    ->preload()
                    ->relationship('service', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->product->name} - " . ucfirst($record->status))
                    ->searchable()
                    ->disabledOn('edit'),
                TextInput::make('vm_id')
                    ->label('VM ID')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->reactive(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'installing' => 'Installing',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required()
                    ->reactive(),

                TextInput::make('hostname')
                    ->label('Hostname')
                    ->required()
                    ->maxLength(255)
                    ->reactive(),
                Select::make('primary_ipv4')
                    ->label('Primary IPv4')
                    ->relationship('primaryIpv4', 'ip', fn ($query, $record) => $query->where('server_id', $record?->id)->where('type', 'ipv4'))
                    ->searchable()
                    ->reactive()
                    ->helperText('Select the primary IP for the server (if editing, requires reinstallation)'),
                Select::make('primary_ipv6')
                    ->label('Primary IPv6')
                    ->relationship('primaryIpv6', 'ip', fn ($query, $record) => $query->where('server_id', $record?->id)->where('type', 'ipv6'))
                    ->searchable()
                    ->preload()
                    ->helperText('Select the primary IP for the server (if editing, requires reinstallation)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('node.name')
                    ->label('Node')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('os.name')
                    ->label('OS')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('service.id')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('vm_id')
                    ->label('VM ID')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            Ipv4RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServers::route('/'),
            'create' => CreateServer::route('/create'),
            'edit' => EditServer::route('/{record}/edit'),
        ];
    }
}
