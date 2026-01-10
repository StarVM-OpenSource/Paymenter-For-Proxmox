<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources;

use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\LocationResource\Pages\CreateLocation;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\LocationResource\Pages\EditLocation;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\LocationResource\Pages\ListLocations;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-map-pin-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Proxmox';

    protected static ?string $label = 'Location';

    protected static ?string $slug = 'proxmox-locations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true),
                Select::make('server_placement')
                    ->label('Server Placement')
                    ->options([
                        'random' => 'Random Node',
                        'fill' => 'Fill Nodes (fill each node before moving to the next)',
                        'least_loaded_server' => 'Least Loaded Node (based on server count)',
                        'least_loaded_ram' => 'Least Loaded Node (based on RAM)',
                        'least_loaded_disk' => 'Least Loaded Node (based on disk)',
                        'least_loaded_cpu' => 'Least Loaded Node (based on CPU)',
                    ])
                    ->default('least_loaded_server')
                    ->helperText('The settings based on CPU, RAM, and disk must be configured in the node settings.')
                    ->required()
                    ->reactive(),
                // Host, user, token, and verify_ssl fields are now moved to the migration
                Forms\Components\TextInput::make('host')
                    ->label('Host')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Full URL of the Proxmox server (e.g. https://proxmox.paymenter.org:8006)'),
                Forms\Components\TextInput::make('user')
                    ->label('User')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The User + Token ID for Proxmox API access.'),
                Forms\Components\TextInput::make('token')
                    ->label('Token')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The API token for Proxmox access.'),
                Forms\Components\Toggle::make('verify_ssl')
                    ->label('Verify SSL')
                    ->default(false)
                    ->helperText('Whether to verify SSL certificates.'),
                Tabs::make('Tabs')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('OS')
                            ->columns(2)
                            ->schema([
                                Repeater::make('os')
                                    ->relationship('os')
                                    ->hiddenLabel()
                                    ->disabled(fn (Get $get) => !$get('name'))
                                    ->addActionLabel('Add OS')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter the name of the OS'),
                                        Select::make('type')
                                            ->label('Type')
                                            ->options([
                                                'qemu' => 'QEMU/KVM (Virtual Machine)',
                                                'lxc' => 'LXC (Container)',
                                            ])
                                            ->default('qemu')
                                            ->required()
                                            ->reactive(),
                                        TextInput::make('vm_id')
                                            ->label(fn (Get $get) => $get('type') === 'lxc' ? 'Template CT ID' : 'Template VM ID')
                                            ->numeric()
                                            ->placeholder(fn (Get $get) => $get('type') === 'lxc' 
                                                ? 'Enter the template CT ID to clone (e.g., 9001)' 
                                                : 'Enter the template VM ID to clone')
                                            ->helperText(fn (Get $get) => $get('type') === 'lxc' 
                                                ? 'The CT ID of your LXC template (convert a container to template first)' 
                                                : null)
                                            ->required()
                                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get, $record) {
                                                return $rule->where('location_id', $get('id'));
                                            }),
                                    ])
                                    ->cloneable()
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('nodes_count')
                    ->label('Nodes')
                    ->sortable()
                    ->searchable()
                    ->counts('nodes')
                    ->formatStateUsing(fn ($state) => $state ?: '0'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
