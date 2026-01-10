<?php

namespace Paymenter\Extensions\Servers\Proxmox\Admin\Resources\IPPoolResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class IPAddressRelationManager extends RelationManager
{
    protected static string $relationship = 'ipAddresses';

    // Set label to IP Addresses
    protected static ?string $label = 'IP Address';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'ipv4' => 'IPv4',
                        'ipv6' => 'IPv6',
                    ])
                    ->default('ipv4')
                    ->disabledOn('edit')
                    ->reactive()
                    ->required(),
                TextInput::make('ip')
                    ->label('IP Address')
                    ->required()
                    ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                    ->disabledOn('edit')
                    ->unique(ignoreRecord: true)
                    ->placeholder('Enter the IP address'),
                TextInput::make('gateway')
                    ->label('Gateway')
                    ->required()
                    ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                    ->placeholder('Enter the gateway'),
                TextInput::make('netmask')
                    ->label('Netmask')
                    ->required()
                    ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                    ->placeholder('Enter the netmask'),
                TextInput::make('mac_address')
                    ->label('MAC Address')
                    ->maxLength(255)
                    ->placeholder('Enter the MAC address'),
                Select::make('server_id')
                    ->label('Server')
                    ->relationship('server', 'hostname')
                    ->searchable()
                    ->preload()
                    ->helperText('Select a server to assign this IP address')
                    ->placeholder('Select a server'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip')
            ->columns([
                TextColumn::make('ip')->label('IP Address')->searchable(),
                TextColumn::make('server.hostname')->label('Server')->sortable()->searchable(),
                TextColumn::make('gateway'),
                TextColumn::make('mac_address')->label('MAC Address'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('bulkaddaddress')
                    ->label('Bulk Add IP Addresses')
                    ->icon('ri-add-line')
                    ->schema(function () {
                        return [
                            Select::make('type')
                                ->label('Type')
                                ->options([
                                    'ipv4' => 'IPv4',
                                    'ipv6' => 'IPv6',
                                ])
                                ->default('ipv4')
                                ->reactive()
                                ->required(),
                            TextInput::make('start_ip')
                                ->label('Start IP Address')
                                ->required()
                                ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                                ->placeholder('Enter the start IP address'),
                            TextInput::make('end_ip')
                                ->label('End IP Address')
                                ->required()
                                ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                                ->placeholder('Enter the end IP address'),
                            TextInput::make('gateway')
                                ->label('Gateway')
                                ->required()
                                ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                                ->placeholder('Enter the gateway'),
                            TextInput::make('netmask')
                                ->label('Netmask')
                                ->required()
                                ->maxLength(fn (Get $get) => $get('type') === 'ipv4' ? 15 : 39) // 15 for IPv4, 39 for IPv6
                                ->placeholder('Enter the netmask'),
                        ];
                    })
                    ->action(function (array $data) {
                        // Get start IP and end IP
                        if ($data['type'] === 'ipv4') {
                            // Validate and convert IP addresses
                            $startIp = ip2long($data['start_ip']);
                            $endIp = ip2long($data['end_ip']);

                            if ($startIp === false || $endIp === false || $startIp > $endIp) {
                                throw ValidationException::withMessages(['mountedActions.0.data.end_ip' => 'End IP must be greater than or equal to Start IP.']);
                            }
                            // Generate IP addresses in the range
                            $ipAddresses = [];
                            for ($ip = $startIp; $ip <= $endIp; $ip++) {
                                $ipAddresses[] = long2ip($ip);
                            }
                        } else {
                            // For IPv6, let's be more conservative and use CIDR notation validation
                            $startIpBinary = inet_pton($data['start_ip']);
                            $endIpBinary = inet_pton($data['end_ip']);

                            if ($startIpBinary === false || $endIpBinary === false) {
                                throw ValidationException::withMessages(['mountedActions.0.data.start_ip' => 'Invalid IPv6 address.', 'mountedActions.0.data.end_ip' => 'Invalid IPv6 address.']);
                            }

                            $startBytes = array_values(unpack('C*', $startIpBinary));
                            $endBytes = array_values(unpack('C*', $endIpBinary));

                            if ($this->compareIPv6Bytes($startBytes, $endBytes) > 0) {
                                throw ValidationException::withMessages(['mountedActions.0.data.end_ip' => 'End IP must be greater than or equal to Start IP.']);
                            }

                            // Calculate approximate range size by checking significant byte differences
                            $rangeTooBig = $this->isIPv6RangeTooBig($startBytes, $endBytes);
                            if ($rangeTooBig) {
                                throw ValidationException::withMessages(['mountedActions.0.data.end_ip' => 'IPv6 range too large. Please specify a smaller range (maximum 10,000 addresses).']);
                            }

                            $ipAddresses = [];
                            $currentBytes = $startBytes;
                            $count = 0;

                            do {
                                if ($count >= 10000) {
                                    throw ValidationException::withMessages(['mountedActions.0.data.end_ip' => 'IPv6 range too large. Please specify a smaller range (maximum 10,000 addresses).']);
                                }

                                $ipBinary = pack('C*', ...$currentBytes);
                                $ipAddresses[] = inet_ntop($ipBinary);
                                $count++;

                            } while ($this->incrementIPv6Bytes($currentBytes) && $this->compareIPv6Bytes($currentBytes, $endBytes) <= 0);

                            $ipAddresses = array_unique($ipAddresses);
                            sort($ipAddresses);
                        }
                        // Create IP addresses in the pool
                        foreach ($ipAddresses as $ip) {
                            $this->getOwnerRecord()->ipAddresses()->create([
                                'type' => $data['type'],
                                'ip' => $ip,
                                'gateway' => $data['gateway'],
                                'netmask' => $data['netmask'],
                                'mac_address' => $data['mac_address'] ?? null,
                            ]);
                        }
                        $this->dispatch('refresh');
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->disabled(fn ($record) => $record->server_id !== null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->before(function (DeleteBulkAction $action, $records) {
                        foreach ($records as $record) {
                            if ($record->server_id !== null) {
                                Notification::make()
                                    ->title('Cannot delete IP Address')
                                    ->body('The IP Address is assigned to a server.')
                                    ->duration(5000)
                                    ->icon('ri-error-warning-line')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }
                    }),
                ]),
            ])->defaultSort('server_id', 'desc');
    }

    private function compareIPv6Bytes(array $a, array $b): int
    {
        for ($i = 0; $i < 16; $i++) {
            if ($a[$i] < $b[$i]) {
                return -1;
            }
            if ($a[$i] > $b[$i]) {
                return 1;
            }
        }

        return 0;
    }

    private function incrementIPv6Bytes(array &$bytes): bool
    {
        for ($i = 15; $i >= 0; $i--) {
            if ($bytes[$i] < 255) {
                $bytes[$i]++;

                return true;
            }
            $bytes[$i] = 0;
        }

        return false; // Overflow
    }

    private function isIPv6RangeTooBig(array $startBytes, array $endBytes): bool
    {
        // Quick check: if any of the first 14 bytes differ significantly,
        // the range is definitely too big
        for ($i = 0; $i < 14; $i++) {
            if ($endBytes[$i] > $startBytes[$i]) {
                // If there's a difference in higher-order bytes, range is huge
                return true;
            } elseif ($endBytes[$i] < $startBytes[$i]) {
                // This shouldn't happen if start <= end, but just in case
                return false;
            }
        }

        // Check the last two bytes for a reasonable range
        $lastTwoStart = ($startBytes[14] << 8) | $startBytes[15];
        $lastTwoEnd = ($endBytes[14] << 8) | $endBytes[15];

        return ($lastTwoEnd - $lastTwoStart) > 10000;
    }
}
