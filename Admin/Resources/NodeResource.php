<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources;

use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource\Pages\CreateNode;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource\Pages\EditNode;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource\Pages\ListNodes;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class NodeResource extends Resource
{
    protected static ?string $model = Node::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Proxmox';

    protected static ?string $label = 'Node';

    protected static string|\BackedEnum|null $navigationIcon = 'ri-base-station-line';

    protected static string|\BackedEnum|null $activeNavigationIcon = 'ri-base-station-fill';

    protected static ?string $slug = 'proxmox-nodes';

    public static function form(Schema $schema): Schema
    {
        $proxmox = new Proxmox;

        return $schema
            ->components([
                Forms\Components\Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->live()
                    ->helperText('Select a node group to assign this node to it.'),
                Forms\Components\Select::make('name')
                    ->label('Node')
                    ->required()
                    ->searchable()
                    ->options(function (Get $get) use ($proxmox) {
                        if (!$get('location_id')) {
                            return [];
                        }
                        $nodes = once(function () use ($proxmox, $get) {
                            try {
                                $nodes = $proxmox->request('/cluster/resources', data: [
                                    'type' => 'node',
                                ], location: Location::findOrFail($get('location_id')))->json()['data'] ?? [];

                                return collect($nodes)->map(function ($node) {
                                    return [
                                        'label' => $node['node'],
                                        'value' => $node['node'],
                                    ];
                                })->pluck('label', 'value');
                            } catch (\Exception $e) {
                                // Return empty array if connection fails (server offline)
                                return collect();
                            }
                        });

                        return $nodes;
                    })
                    ->disabledOn('edit')
                    ->disabled(fn (Get $get) => !$get('location_id'))
                    ->live(onBlur: true)
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule, Get $get) {
                        return $rule->where('location_id', $get('location_id'));
                    })
                    ->reactive(),
                Forms\Components\TextInput::make('memory')
                    ->label('RAM (MiB)')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('0 means no limit')
                    ->disabled(fn (Get $get) => !$get('name')),
                TextInput::make('disk')
                    ->label('Disk (GiB)')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('0 means no limit')
                    ->disabled(fn (Get $get) => !$get('name')),
                TextInput::make('cpu')
                    ->label('CPU Cores')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('0 means no limit')
                    ->disabled(fn (Get $get) => !$get('name')),
                TextInput::make('storage_location')
                    ->label('Storage Location')
                    ->required()
                    ->default('local')
                    ->maxLength(255)
                    ->helperText('The storage location to use for this node, e.g., local, local-lvm')
                    ->disabled(fn (Get $get) => !$get('name')),
                TextInput::make('backup_location')
                    ->label('Backup Location')
                    ->required()
                    ->default('local')
                    ->maxLength(255)
                    ->helperText('The backup location to use for this node, e.g., local, local-lvm')
                    ->disabled(fn (Get $get) => !$get('name')),
                Toggle::make('vnc_enabled')
                    ->label('VNC Enabled')
                    ->default(true)
                    ->helperText('Enable or disable VNC access for this node. (this requires the Proxmox instance to be on the same domain as the Paymenter installation)')
                    ->required(),

                \Filament\Schemas\Components\Section::make('SSH Configuration')
                    ->description('Required for NAT mode to execute iptables commands')
                    ->schema([
                        TextInput::make('ssh_host')
                            ->label('SSH Host')
                            ->placeholder('Leave empty to use Proxmox API host'),
                        TextInput::make('ssh_port')
                            ->label('SSH Port')
                            ->numeric()
                            ->default(22),
                        TextInput::make('ssh_password')
                            ->label('SSH Password')
                            ->password()
                            ->revealable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Node')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('servers_count')
                    ->label('Servers')
                    ->counts('servers')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
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
            'index' => ListNodes::route('/'),
            'create' => CreateNode::route('/create'),
            'edit' => EditNode::route('/{record}/edit'),
        ];
    }
}
