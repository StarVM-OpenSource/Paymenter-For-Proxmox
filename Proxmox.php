<?php

namespace Paymenter\Extensions\Servers\Proxmox;

use App\Attributes\ExtensionMeta;
use App\Classes\Extension\Server as ClassServer;
use App\Helpers\ExtensionHelper;
use App\Models\Product;
use App\Models\Service;
use Exception;
use Illuminate\Console\Application as Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\LocationResource;
use Paymenter\Extensions\Servers\Proxmox\Admin\Resources\NodeResource;
use Paymenter\Extensions\Servers\Proxmox\Commands\ResetBandwidthUsageCommand;
use Paymenter\Extensions\Servers\Proxmox\Commands\SyncTrafficCommand;
use Paymenter\Extensions\Servers\Proxmox\Jobs\ReinstallJob;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Backups\Create;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Index;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Network;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Overview;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Reinstall;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Settings;
use Paymenter\Extensions\Servers\Proxmox\Models\IPPool;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;
use Paymenter\Extensions\Servers\Proxmox\Models\Node;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;

#[ExtensionMeta(
    'Proxmox',
    'Manage Proxmox VE virtual machines directly from Paymenter.',
    'v1.6.1',
    'Paymenter',
)]
class Proxmox extends ClassServer
{
    public function installed()
    {
        ExtensionHelper::runMigrations('extensions/Servers/Proxmox/database/migrations');
    }

    public function uninstalled()
    {
        ExtensionHelper::rollbackMigrations('extensions/Servers/Proxmox/database/migrations');
    }

    public function upgraded($oldVersion = null)
    {
        $this->installed();
    }

    public function boot()
    {
        include __DIR__ . '/routes.php';
        View::addNamespace('proxmox', __DIR__ . '/views');

        Livewire::component('proxmox.overview', Overview::class);
        Livewire::component('proxmox.network', Network::class);
        Livewire::component('proxmox.index', Index::class);
        Livewire::component('proxmox.server', \Paymenter\Extensions\Servers\Proxmox\Livewire\Server::class);
        Livewire::component('proxmox.reinstall', Reinstall::class);
        Livewire::component('proxmox.backups', \Paymenter\Extensions\Servers\Proxmox\Livewire\Backups\Index::class);
        Livewire::component('proxmox.backups.create', Create::class);
        Livewire::component('proxmox.settings', Settings::class);
        Livewire::component('proxmox.portforwards', \Paymenter\Extensions\Servers\Proxmox\Livewire\PortForwards::class);

        Event::listen('navigation.dashboard', function () {
            if (Server::where('user_id', auth()->id())->count() == 0) {
                return;
            }

            return [
                'name' => 'Servers',
                'route' => 'proxmox.index',
                'icon' => 'ri-server',
                'separator' => true,
                'priority' => 20,
            ];
        });

        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $schedule->command(SyncTrafficCommand::class)->everyFiveMinutes();
        $schedule->command(ResetBandwidthUsageCommand::class)->monthlyOn(1);

        if (app()->runningInConsole()) {
            Artisan::starting(function ($artisan) {
                $artisan->resolveCommands([
                    new SyncTrafficCommand(),
                    new ResetBandwidthUsageCommand(),
                ]);
            });
        }
    }

    public function request($url, $method = 'get', $data = [], $location = null)
    {
        $url = rtrim($location->host, '/') . '/api2/json' . $url;

        $response = Http::withHeaders([
            'Authorization' => 'PVEAPIToken=' . $location->user . '=' . $location->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withOptions([
            'verify' => $location->verify_ssl ? true : false,
        ])->$method($url, $data)->throw();

        return $response;
    }

    
    public function getConfig($values = []): array
    {
        try {
            return [
                [
                    'name' => 'Notice',
                    'type' => 'placeholder',
                    'label' => new HtmlString('You\'ve enabled the Proxmox extension, visit <a class="text-primary-600" href="' . LocationResource::getUrl() . '">this</a> to continue the setup.'),
                ],
            ];
        } catch (Exception $e) {
            return [

            ];
        }
    }

    
    public function getProductConfig($values = []): array
    {
        if (!isset($values['location_id']) || $values['location_id'] == null) {
            return [
                [
                    'name' => 'location_id',
                    'label' => 'Location',
                    'type' => 'select',
                    'required' => true,
                    'options' => Location::all()->map(function ($node) {
                        return [
                            'label' => $node->name,
                            'value' => $node->id,
                        ];
                    })->toArray(),
                    'live' => true,
                    'description' => new HtmlString('Cannot find the location? Make sure you have created a location first. <a class="text-primary-600" href="' . LocationResource::getUrl('create') . '">Create Location</a>'),
                ],
            ];
        }

        if (Node::count() == 0) {
            return [
                [
                    'name' => 'Notice',
                    'type' => 'placeholder',
                    'label' => new HtmlString('Could not find any nodes. Please create a node first. [<a class="text-primary-600" href="' . NodeResource::getUrl() . '">Create Node</a>]'),
                ],
            ];
        }

        $location = Location::findOrFail($values['location_id']);

        $resourcePool = $this->request('/pools', location: $location);
        $poolList = [];
        foreach ($resourcePool->json()['data'] as $pool) {
            $poolList[] = [
                'label' => $pool['poolid'],
                'value' => $pool['poolid'],
            ];
        }

        return [
            [
                'name' => 'location_id',
                'label' => 'Location',
                'type' => 'select',
                'required' => true,
                'options' => Location::all()->map(function ($node) {
                    return [
                        'label' => $node->name,
                        'value' => $node->id,
                    ];
                })->toArray(),
                'live' => true,
                'description' => new HtmlString('Cannot find the location? Make sure you have created a location first. <a class="text-primary-600" href="' . LocationResource::getUrl('create') . '">Create Location</a>'),
            ],
            [
                'name' => 'ipv4',
                'label' => 'Number of IPv4 Addresses',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'ipv6',
                'label' => 'Number of IPv6 Addresses',
                'type' => 'number',
                'required' => false,
            ],
            [
                'name' => 'pool',
                'label' => 'Resource Pool',
                'type' => 'select',
                'required' => false,
                'options' => $poolList,
                'live' => true,
            ],
            [
                'name' => 'cpu',
                'label' => 'CPU Model',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['label' => 'Broadwell-IBRS (GenuineIntel)', 'value' => 'Broadwell-IBRS'],
                    ['label' => 'Skylake-Server-noTSX-IBRS (GenuineIntel)', 'value' => 'Skylake-Server-noTSX-IBRS'],
                    ['label' => 'Icelake-Server (GenuineIntel)', 'value' => 'Icelake-Server'],
                    ['label' => 'Icelake-Server-noTSX (GenuineIntel)', 'value' => 'Icelake-Server-noTSX'],
                    ['label' => 'Icelake-Server-v6 (GenuineIntel)', 'value' => 'Icelake-Server-v6'],
                    ['label' => 'EPYC-Rome-v4 (AuthenticAMD)', 'value' => 'EPYC-Rome-v4'],
                    ['label' => 'EPYC-IBPB (AuthenticAMD)', 'value' => 'EPYC-IBPB'],
                    ['label' => 'pentium3 (GenuineIntel)', 'value' => 'pentium3'],
                    ['label' => 'host (default)', 'value' => 'host'],
                    ['label' => 'EPYC-Rome-v2 (AuthenticAMD)', 'value' => 'EPYC-Rome-v2'],
                    ['label' => 'Icelake-Server-v3 (GenuineIntel)', 'value' => 'Icelake-Server-v3'],
                    ['label' => 'Cascadelake-Server (GenuineIntel)', 'value' => 'Cascadelake-Server'],
                    ['label' => 'EPYC-Genoa (AuthenticAMD)', 'value' => 'EPYC-Genoa'],
                    ['label' => 'Icelake-Client (GenuineIntel)', 'value' => 'Icelake-Client'],
                    ['label' => 'Broadwell-noTSX-IBRS (GenuineIntel)', 'value' => 'Broadwell-noTSX-IBRS'],
                    ['label' => 'SapphireRapids-v2 (GenuineIntel)', 'value' => 'SapphireRapids-v2'],
                    ['label' => 'Icelake-Client-noTSX (GenuineIntel)', 'value' => 'Icelake-Client-noTSX'],
                    ['label' => 'Cascadelake-Server-v2 (GenuineIntel)', 'value' => 'Cascadelake-Server-v2'],
                    ['label' => 'Nehalem-IBRS (GenuineIntel)', 'value' => 'Nehalem-IBRS'],
                    ['label' => 'EPYC (AuthenticAMD)', 'value' => 'EPYC'],
                    ['label' => 'qemu64 (default)', 'value' => 'qemu64'],
                    ['label' => 'Westmere (GenuineIntel)', 'value' => 'Westmere'],
                    ['label' => 'Haswell-noTSX-IBRS (GenuineIntel)', 'value' => 'Haswell-noTSX-IBRS'],
                    ['label' => 'Opteron_G2 (AuthenticAMD)', 'value' => 'Opteron_G2'],
                    ['label' => 'Cooperlake (GenuineIntel)', 'value' => 'Cooperlake'],
                    ['label' => 'Broadwell-noTSX (GenuineIntel)', 'value' => 'Broadwell-noTSX'],
                    ['label' => 'Cascadelake-Server-v4 (GenuineIntel)', 'value' => 'Cascadelake-Server-v4'],
                    ['label' => 'qemu32 (default)', 'value' => 'qemu32'],
                    ['label' => 'Skylake-Server-v5 (GenuineIntel)', 'value' => 'Skylake-Server-v5'],
                    ['label' => 'Opteron_G1 (AuthenticAMD)', 'value' => 'Opteron_G1'],
                    ['label' => 'EPYC-Rome (AuthenticAMD)', 'value' => 'EPYC-Rome'],
                    ['label' => 'Opteron_G4 (AuthenticAMD)', 'value' => 'Opteron_G4'],
                    ['label' => 'max (default)', 'value' => 'max'],
                    ['label' => 'core2duo (GenuineIntel)', 'value' => 'core2duo'],
                    ['label' => 'athlon (AuthenticAMD)', 'value' => 'athlon'],
                    ['label' => 'SandyBridge (GenuineIntel)', 'value' => 'SandyBridge'],
                    ['label' => 'Opteron_G5 (AuthenticAMD)', 'value' => 'Opteron_G5'],
                    ['label' => 'Skylake-Client-v4 (GenuineIntel)', 'value' => 'Skylake-Client-v4'],
                    ['label' => 'Haswell-IBRS (GenuineIntel)', 'value' => 'Haswell-IBRS'],
                    ['label' => 'EPYC-v4 (AuthenticAMD)', 'value' => 'EPYC-v4'],
                    ['label' => 'Cooperlake-v2 (GenuineIntel)', 'value' => 'Cooperlake-v2'],
                    ['label' => 'IvyBridge-IBRS (GenuineIntel)', 'value' => 'IvyBridge-IBRS'],
                    ['label' => 'KnightsMill (GenuineIntel)', 'value' => 'KnightsMill'],
                    ['label' => 'pentium (GenuineIntel)', 'value' => 'pentium'],
                    ['label' => 'Cascadelake-Server-noTSX (GenuineIntel)', 'value' => 'Cascadelake-Server-noTSX'],
                    ['label' => 'phenom (AuthenticAMD)', 'value' => 'phenom'],
                    ['label' => 'Skylake-Server-v4 (GenuineIntel)', 'value' => 'Skylake-Server-v4'],
                    ['label' => 'Westmere-IBRS (GenuineIntel)', 'value' => 'Westmere-IBRS'],
                    ['label' => 'pentium2 (GenuineIntel)', 'value' => 'pentium2'],
                    ['label' => 'kvm32 (default)', 'value' => 'kvm32'],
                    ['label' => 'Skylake-Client-IBRS (GenuineIntel)', 'value' => 'Skylake-Client-IBRS'],
                    ['label' => 'Skylake-Client (GenuineIntel)', 'value' => 'Skylake-Client'],
                    ['label' => 'Nehalem (GenuineIntel)', 'value' => 'Nehalem'],
                    ['label' => 'Cascadelake-Server-v5 (GenuineIntel)', 'value' => 'Cascadelake-Server-v5'],
                    ['label' => '486 (GenuineIntel)', 'value' => '486'],
                    ['label' => 'Penryn (GenuineIntel)', 'value' => 'Penryn'],
                    ['label' => 'Conroe (GenuineIntel)', 'value' => 'Conroe'],
                    ['label' => 'GraniteRapids (GenuineIntel)', 'value' => 'GraniteRapids'],
                    ['label' => 'Skylake-Server-IBRS (GenuineIntel)', 'value' => 'Skylake-Server-IBRS'],
                    ['label' => 'Skylake-Server (GenuineIntel)', 'value' => 'Skylake-Server'],
                    ['label' => 'IvyBridge (GenuineIntel)', 'value' => 'IvyBridge'],
                    ['label' => 'SapphireRapids (GenuineIntel)', 'value' => 'SapphireRapids'],
                    ['label' => 'kvm64 (default)', 'value' => 'kvm64'],
                    ['label' => 'EPYC-v3 (AuthenticAMD)', 'value' => 'EPYC-v3'],
                    ['label' => 'EPYC-Milan (AuthenticAMD)', 'value' => 'EPYC-Milan'],
                    ['label' => 'SandyBridge-IBRS (GenuineIntel)', 'value' => 'SandyBridge-IBRS'],
                    ['label' => 'Broadwell (GenuineIntel)', 'value' => 'Broadwell'],
                    ['label' => 'coreduo (GenuineIntel)', 'value' => 'coreduo'],
                    ['label' => 'Icelake-Server-v4 (GenuineIntel)', 'value' => 'Icelake-Server-v4'],
                    ['label' => 'Haswell (GenuineIntel)', 'value' => 'Haswell'],
                    ['label' => 'EPYC-Milan-v2 (AuthenticAMD)', 'value' => 'EPYC-Milan-v2'],
                    ['label' => 'Icelake-Server-v5 (GenuineIntel)', 'value' => 'Icelake-Server-v5'],
                    ['label' => 'EPYC-Rome-v3 (AuthenticAMD)', 'value' => 'EPYC-Rome-v3'],
                    ['label' => 'Skylake-Client-noTSX-IBRS (GenuineIntel)', 'value' => 'Skylake-Client-noTSX-IBRS'],
                    ['label' => 'Haswell-noTSX (GenuineIntel)', 'value' => 'Haswell-noTSX'],
                    ['label' => 'Opteron_G3 (AuthenticAMD)', 'value' => 'Opteron_G3'],
                    ['label' => 'x86-64-v4 (default)', 'value' => 'x86-64-v4'],
                    ['label' => 'x86-64-v3 (default)', 'value' => 'x86-64-v3'],
                    ['label' => 'x86-64-v2-AES (default)', 'value' => 'x86-64-v2-AES'],
                    ['label' => 'x86-64-v2 (default)', 'value' => 'x86-64-v2'],
                ],
                'live' => true,
                'default' => 'x86-64-v2-AES',
            ],

            [
                'name' => 'cores',
                'type' => 'text',
                'label' => 'CPU Cores',
                'required' => true,
            ],
            [
                'name' => 'sockets',
                'type' => 'number',
                'label' => 'CPU Sockets',
            ],
            [
                'name' => 'cpu_limit',
                'type' => 'number',
                'label' => 'CPU Limit',
                'default' => 0,
                'description' => '(0 = no limit)',
            ],
            [
                'name' => 'cpu_units',
                'type' => 'number',
                'label' => 'CPU Units',
                'default' => 100,
                'description' => 'The relative weight of CPU time this VM will get (100 = default)',
            ],
            [
                'name' => 'memory',
                'type' => 'text',
                'label' => 'Memory (MiB)',
                'required' => true,
            ],
            [
                'name' => 'disk',
                'type' => 'text',
                'label' => 'Disk (GiB)',
                'required' => true,
            ],
            [
                'name' => 'backups',
                'type' => 'number',
                'label' => 'Backups',
                'description' => 'Amount of backups user can create (0 = disabled)',
                'default' => 0,
            ],
            [
                'name' => 'cache',
                'type' => 'select',
                'label' => 'Cache',
                'description' => 'The cache of the VM',
                'options' => [
                    [
                        'label' => 'Default (no cache)',
                        'value' => 'default',
                    ],
                    [
                        'label' => 'Direct Sync',
                        'value' => 'directsync',
                    ],
                    [
                        'label' => 'Write Through',
                        'value' => 'writethrough',
                    ],
                    [
                        'label' => 'Write Back',
                        'value' => 'write back',
                    ],
                    [
                        'label' => 'Write Back (unsafe)',
                        'value' => 'unsafe',
                    ],
                    [
                        'label' => 'No Cache',
                        'value' => 'none',
                    ],
                ],
                'default' => 'default',
            ],

            [
                'name' => 'model',
                'type' => 'select',
                'label' => 'Network Model',
                'options' => [
                    [
                        'label' => 'VirtIO',
                        'value' => 'virtio',
                    ],
                    [
                        'label' => 'Intel E1000',
                        'value' => 'e1000',
                    ],
                    [
                        'label' => 'Realtek RTL8139',
                        'value' => 'rtl8139',
                    ],
                    [
                        'label' => 'VMWare VMXNET3',
                        'value' => 'vmxnet3',
                    ],
                ],
                'default' => 'virtio',
                'required' => true,
            ],
            [
                'name' => 'ratelimit',
                'type' => 'text',
                'label' => 'Network Rate limit (MB/s)',
                'placeholder' => 'unlimited',
            ],
            [
                'name' => 'bandwidth_limit',
                'type' => 'text',
                'label' => 'Bandwidth Limit (GB)',
                'description' => 'The maximum amount of bandwidth the server can use per month. (0 = unlimited)',
                'default' => 0,
            ],
            [
                'name' => 'autostart',
                'type' => 'checkbox',
                'label' => 'Start VM after creation',
                'default' => true,
                'description' => 'Automatically start the VM after it has been created.',
            ],
            [
                'name' => 'nat_port_count',
                'type' => 'number',
                'label' => 'NAT Port Count',
                'description' => 'Number of port forwards for NAT mode (only applicable if using NAT IP pool, 0 = disabled)',
                'default' => 20,
            ],
        ];
    }

    public function getCheckoutConfig(Product $product)
    {
        $oses = Location::findOrFail($product->settings()->where('key', 'location_id')->first()->value)->os()->get();

        return [
            [
                'name' => 'hostname',
                'label' => 'Hostname',
                'type' => 'text',
                'required' => true,
                'description' => 'The hostname of the server',
                'validation' => 'regex:/^(?!.*[.-]{2})[a-zA-Z0-9]([a-zA-Z0-9.-]{0,126}[a-zA-Z0-9])?$/',
            ],
            [
                'name' => 'os',
                'label' => 'Operating System',
                'type' => 'select',
                'required' => true,
                'options' => $oses->pluck('name', 'id')->toArray(),
            ],
        ];
    }

    
    public function testConfig(): bool|string
    {
        return true;
    }

    public function requestAndWait($url, $method = 'get', $data = [], $node = null, $location = null)
    {
        $response = $this->request($url, $method, $data, $location);
        $task = $response->json()['data'];
        sleep(1); 

        return $this->waitForTask($task, $node, $location);
    }

    public function waitForTask($task, $node, $location)
    {
        $status = $this->request('/nodes/' . $node . '/tasks/' . $task . '/status', location: $location)->json()['data']['status'];
        while ($status == 'running') {
            sleep(1);
            $status = $this->request('/nodes/' . $node . '/tasks/' . $task . '/status', location: $location)->json()['data']['status'];
        }

        return $status;
    }

    private function generateMacAddress(): string
    {
        $mac = [];
        for ($i = 0; $i < 6; $i++) {
            $mac[] = str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
        }
        $firstOctet = hexdec($mac[0]);
        $firstOctet = ($firstOctet | 0x02) & 0xFE;
        $mac[0] = str_pad(dechex($firstOctet), 2, '0', STR_PAD_LEFT);

        return implode(':', $mac);
    }

    public function install(Server $server, $settings, $password)
    {
        $server->status = 'installing';
        $server->save();
        if ($server->os->isLxc()) {
            $this->installLxc($server, $settings, $password);
        } else {
            $this->installQemu($server, $settings, $password);
            if (isset($settings['autostart']) && $settings['autostart']) {
                try {
                    $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/status/start', 'post', [
                        'vmid' => $server->vm_id,
                    ], $server->node->location);
                } catch (\Exception $e) {
                    \Log::info("QEMU VM start note: " . $e->getMessage());
                }
            }
        }
        $server->status = 'active';
        $server->save();
    }

    
    private function installQemu(Server $server, $settings, $password)
    {
        $this->requestAndWait('/nodes/' . $server->node->name . '/qemu/' . $server->os->vm_id . '/clone', 'post', [
            'newid' => $server->vm_id,
            'name' => $server->hostname,
            'target' => $server->node->name,
            'full' => 1,
            'storage' => $server->node->storage_location,
            'pool' => $settings['pool'],
        ], $server->node->name, $server->node->location);

        $this->syncServerSettings($server, $settings, $password);
    }

    
    private function installLxc(Server $server, $settings, $password)
    {
        $ipv4 = $server->primaryIpv4;
        $ipv6 = $server->primaryIpv6;
        \Log::info("LXC Install - Server {$server->vm_id}: primary_ipv4 ID = " . ($server->primary_ipv4 ?? 'NULL') . 
                   ", IPv4 object = " . ($ipv4 ? $ipv4->ip : 'NULL'));
        $cloneParams = [
            'newid' => $server->vm_id,
            'hostname' => $server->hostname,
            'target' => $server->node->name,
            'full' => 1,
            'storage' => $server->node->storage_location,
        ];
        if (!empty($settings['pool'])) {
            $cloneParams['pool'] = $settings['pool'];
        }

        \Log::info("LXC Step A: Cloning CT template {$server->os->vm_id} to {$server->vm_id}");

        $this->requestAndWait('/nodes/' . $server->node->name . '/lxc/' . $server->os->vm_id . '/clone', 'post', 
            $cloneParams, $server->node->name, $server->node->location);
        $resourceConfig = [
            'cores' => (int) $settings['cores'],
            'memory' => (int) $settings['memory'],
            'swap' => (int) ($settings['swap'] ?? 512),
            'onboot' => (int) ($settings['autostart'] ?? 1),
        ];

        \Log::info("LXC Step B1: Setting resources for CT {$server->vm_id}: " . json_encode($resourceConfig));

        $this->request('/nodes/' . $server->node->name . '/lxc/' . $server->vm_id . '/config', 'put', 
            $resourceConfig, $server->node->location);
        if ($ipv4 || $ipv6) {
            $mac = $this->generateMacAddress();
            $bridge = $ipv4?->ipPool?->network_interface ?? 'vmbr0';
            $netConfig = "name=eth0,bridge={$bridge},hwaddr={$mac}";
            
            if ($ipv4) {
                $subnet = $ipv4->subnet ?? 24;
                $netConfig .= ",ip={$ipv4->ip}/{$subnet},gw={$ipv4->gateway}";
            }
            if ($ipv6) {
                $netConfig .= ",ip6={$ipv6->ip}/64";
                if ($ipv6->gateway) {
                    $netConfig .= ",gw6={$ipv6->gateway}";
                }
            }
            if ($settings['rate_limit'] ?? 0) {
                $netConfig .= ',rate=' . $settings['rate_limit'];
            }
            if ($ipv4?->ipPool?->firewall ?? false) {
                $netConfig .= ',firewall=1';
            }
            if ($ipv4?->ipPool?->mtu ?? 0) {
                $netConfig .= ',mtu=' . $ipv4->ipPool->mtu;
            }

            \Log::info("LXC Step B2: Setting network for CT {$server->vm_id}: net0={$netConfig}");

            $this->request('/nodes/' . $server->node->name . '/lxc/' . $server->vm_id . '/config', 'put', 
                ['net0' => $netConfig], $server->node->location);
        }
        if (isset($settings['disk']) && $settings['disk'] > 0) {
            \Log::info("LXC Step B3: Resizing rootfs to {$settings['disk']}G");

            try {
                $this->requestAndWait('/nodes/' . $server->node->name . '/lxc/' . $server->vm_id . '/resize', 'put', [
                    'disk' => 'rootfs',
                    'size' => $settings['disk'] . 'G',
                ], $server->node->name, $server->node->location);
            } catch (\Exception $e) {
                \Log::info("LXC Step B3: Disk resize skipped (may already be at target size): " . $e->getMessage());
            }
        }
        \Log::info("LXC Step C: Starting CT {$server->vm_id}");

        $containerStarted = false;
        try {
            $this->request('/nodes/' . $server->node->name . '/lxc/' . $server->vm_id . '/status/start', 'post', 
                [], $server->node->location);
            sleep(5);
            $containerStarted = true;
        } catch (\Exception $e) {
            \Log::warning("LXC Step C: API start failed for CT {$server->vm_id}: " . $e->getMessage());
            if ($server->node->ssh_host && $server->node->ssh_password) {
                \Log::info("LXC Step C: Attempting SSH fallback start for CT {$server->vm_id}");
                try {
                    $this->sshStartContainer($server);
                    sleep(5);
                    $containerStarted = true;
                } catch (\Exception $sshE) {
                    \Log::warning("LXC Step C: SSH start also failed: " . $sshE->getMessage());
                }
            }
        }
        if ($password && $server->node->ssh_host && $server->node->ssh_password) {
            if (!$containerStarted) {
                \Log::warning("LXC Step D: Skipping password set - container {$server->vm_id} is not running. " .
                    "Password must be set manually: pct exec {$server->vm_id} -- bash -c 'echo \"root:PASSWORD\" | chpasswd'");
            } else {
                \Log::info("LXC Step D: Setting root password for CT {$server->vm_id} via SSH");

                try {
                    $this->setLxcPassword($server, $password);
                } catch (\Exception $e) {
                    \Log::warning("LXC password set failed for CT {$server->vm_id}: " . $e->getMessage() . 
                        ". Password must be set manually via console.");
                }
            }
        } else {
            \Log::info("LXC CT {$server->vm_id}: SSH not configured on node or no password provided. Password must be pre-configured in template or set via console.");
        }

        \Log::info("LXC CT {$server->vm_id} created successfully.");
    }

    
    private function setLxcPassword(Server $server, string $password): void
    {
        $node = $server->node;
        $sshHost = $node->ssh_host ?? $node->location->host;
        $sshHost = preg_replace('/^https?:\/\
        $sshHost = preg_replace('/:\d+$/', '', $sshHost);
        $sshPort = $node->ssh_port ?? 22;
        $sshPassword = $node->ssh_password;

        if (!$sshPassword) {
            throw new \Exception('SSH password not configured for node');
        }
        $connection = @ssh2_connect($sshHost, $sshPort);
        if (!$connection) {
            throw new \Exception("Could not connect to SSH: {$sshHost}:{$sshPort}");
        }

        if (!@ssh2_auth_password($connection, 'root', $sshPassword)) {
            throw new \Exception('SSH authentication failed');
        }
        $escapedPassword = str_replace("'", "'\\''", $password); 
        $command = "pct exec {$server->vm_id} -- bash -c 'echo \"root:{$escapedPassword}\" | chpasswd'";
        
        \Log::info("LXC Step D: Executing password change command for CT {$server->vm_id}");
        
        $stream = ssh2_exec($connection, $command);
        if (!$stream) {
            throw new \Exception('Failed to execute SSH command');
        }

        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);
        $errorOutput = stream_get_contents($stderr);
        fclose($stream);
        
        if (!empty($errorOutput)) {
            \Log::warning("LXC password change stderr for CT {$server->vm_id}: {$errorOutput}");
            if (str_contains($errorOutput, 'not running')) {
                throw new \Exception("Container not running - cannot set password");
            }
        }

        \Log::info("LXC password set successfully for CT {$server->vm_id}");
    }

    
    private function sshStartContainer(Server $server): void
    {
        $node = $server->node;
        
        $sshHost = $node->ssh_host ?? $node->location->host;
        $sshHost = preg_replace('/^https?:\/\
        $sshHost = preg_replace('/:\d+$/', '', $sshHost);
        $sshPort = $node->ssh_port ?? 22;
        $sshPassword = $node->ssh_password;

        if (!$sshPassword) {
            throw new \Exception('SSH password not configured for node');
        }

        $connection = @ssh2_connect($sshHost, $sshPort);
        if (!$connection) {
            throw new \Exception("Could not connect to SSH: {$sshHost}:{$sshPort}");
        }

        if (!@ssh2_auth_password($connection, 'root', $sshPassword)) {
            throw new \Exception('SSH authentication failed');
        }
        $command = "pct start {$server->vm_id}";
        
        \Log::info("LXC Step C: Executing SSH start command: {$command}");
        
        $stream = ssh2_exec($connection, $command);
        if (!$stream) {
            throw new \Exception('Failed to execute SSH command');
        }

        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);
        $errorOutput = stream_get_contents($stderr);
        fclose($stream);
        
        if (!empty($errorOutput) && !str_contains($errorOutput, 'already running')) {
            throw new \Exception("SSH start failed: {$errorOutput}");
        }

        \Log::info("LXC Step C: SSH start successful for CT {$server->vm_id}");
    }

    private function syncServerSettings(Server $server, $settings, $password = null)
    {
        $ipv4 = $server->primaryIpv4;
        $ipv6 = $server->primaryIpv6;

        $data = [
            'autostart' => $settings['autostart'] ?? 1,
            'cores' => $settings['cores'],
            'cpulimit' => $settings['cpulimit'] ?? 0,
            'cpuunits' => $settings['cpuunits'] ?? 1024,
            'sockets' => $settings['sockets'] ?? 1,
            'memory' => $settings['memory'],
            'cpu' => $settings['cpu'],
        ];

        if ($password) {
            $data['cipassword'] = $password;
        }
        $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/config', 'put', $data, $server->node->location);
        $disk = $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/config', location: $server->node->location)->json()['data'];
        $diskType = 'scsi0';
        if (isset($disk['virtio0'])) {
            $diskType = 'virtio0';
        } elseif (isset($disk['sata0'])) {
            $diskType = 'sata0';
        }
        $this->requestAndWait('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/resize', 'put', [
            'size' => $settings['disk'] . 'G',
            'disk' => $diskType,
        ], $server->node->name, $server->node->location);

        $config = [];
        if ($ipv4 && $ipv6 && $ipv4->vlan == $ipv6->vlan && ($ipv4->mac == $ipv6->mac || !$ipv4->mac || !$ipv6->mac)) {
            $location = $ipv4->ipPool;
            $network = $settings['model'] . '=' . $this->generateMacAddress() . ',bridge=' . $location->network_interface;
            $network = $this->applyPoolSettings($location, $network);
            if (isset($settings['ratelimit'])) {
                $network .= ',rate=' . $settings['ratelimit'];
            }
            $config['net0'] = $network;
            $config['ipconfig0'] = 'ip=' . $ipv4->ip . '/24' . ',gw=' . $ipv4->gateway . ',ip6=' . $ipv6->ip . '/64' . ',gw6=' . $ipv6->gateway;
            $this->makeIpset($server, 'ipfilter-net0', $server->ipAddresses()->pluck('type', 'ip')->toArray());
        } else {
            $ipCount = 0;
            if ($ipv4) {
                $network = $settings['model'] . '=' . ($ipv4->mac_address ? $ipv4->mac_address : $this->generateMacAddress()) . ',bridge=' . $ipv4->ipPool->network_interface;
                $network = $this->applyPoolSettings($ipv4->ipPool, $network);
                if (isset($settings['ratelimit'])) {
                    $network .= ',rate=' . $settings['ratelimit'];
                }
                $config['net' . $ipCount] = $network;
                $config['ipconfig' . $ipCount] = 'ip=' . $ipv4->ip . '/24' . ',gw=' . $ipv4->gateway;
                $this->makeIpset($server, 'ipfilter-net' . $ipCount, $server->ipAddresses()->pluck('type', 'ip')->toArray());
                $ipCount++;
            }
            if ($ipv6) {
                $network = $settings['model'] . '=' . ($ipv6->mac_address ? $ipv6->mac_address : $this->generateMacAddress()) . ',bridge=' . $ipv6->ipPool->network_interface;
                $network = $this->applyPoolSettings($ipv6->ipPool, $network);
                if (isset($settings['ratelimit'])) {
                    $network .= ',rate=' . $settings['ratelimit'];
                }
                $config['net' . $ipCount] = $network;
                $config['ipconfig' . $ipCount] = 'ip6=' . $ipv6->ip . '/64' . ',gw6=' . $ipv6->gateway;
                $this->makeIpset($server, 'ipfilter-net' . $ipCount, $server->ipAddresses()->pluck('type', 'ip')->toArray());
                $ipCount++;
            }
        }

        $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/config', 'put', $config, $server->node->location);
        $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/firewall/options', 'put', [
            'enable' => 1,
            'ipfilter' => 1,
            'policy_in' => 'ACCEPT',
            'policy_out' => 'ACCEPT',
        ], $server->node->location);
    }

    private function makeIpset(Server $server, $name, $ips)
    {
        $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/firewall/ipset', 'post', [
            'name' => $name,
            'comment' => 'Created by Paymenter',
        ], $server->node->location);

        foreach ($ips as $ip => $type) {
            $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/firewall/ipset/' . $name, 'post', [
                'cidr' => $ip . ($type == 'ipv4' ? '/32' : '/128'),
                'comment' => 'Created by Paymenter',
            ], $server->node->location);
        }
    }

    private function applyPoolSettings(IPPool $ipPool, $string)
    {
        if ($ipPool->vlan) {
            $string .= ',tag=' . $ipPool->vlan;
        }
        if ($ipPool->firewall) {
            $string .= ',firewall=1';
        }
        if ($ipPool->mtu) {
            $string .= ',mtu=' . $ipPool->mtu;
        }

        return $string;
    }

    public function updateRatelimit(Server $server, $settings, $ratelimit = null): void
    {
        $ratelimit = $ratelimit ?? $settings['ratelimit'] ?? null;
        $ipv4 = $server->primaryIpv4;
        $ipv6 = $server->primaryIpv6;
        $config = [];
        if ($ipv4 && $ipv6 && $ipv4->vlan == $ipv6->vlan && ($ipv4->mac == $ipv6->mac || !$ipv4->mac || !$ipv6->mac)) {
            $location = $ipv4->ipPool;
            $network = $settings['model'] . '=' . $this->generateMacAddress() . ',bridge=' . $location->network_interface;
            $network = $this->applyPoolSettings($location, $network);
            if ($ratelimit) {
                $network .= ',rate=' . $ratelimit;
            }
            $config['net0'] = $network;
            $config['ipconfig0'] = 'ip=' . $ipv4->ip . '/24' . ',gw=' . $ipv4->gateway . ',ip6=' . $ipv6->ip . '/64' . ',gw6=' . $ipv6->gateway;
        } else {
            $ipCount = 0;
            if ($ipv4) {
                $network = $settings['model'] . '=' . ($ipv4->mac_address ? $ipv4->mac_address : $this->generateMacAddress()) . ',bridge=' . $ipv4->ipPool->network_interface;
                $network = $this->applyPoolSettings($ipv4->ipPool, $network);
                if ($ratelimit) {
                    $network .= ',rate=' . $ratelimit;
                }
                $config['net' . $ipCount] = $network;
                $config['ipconfig' . $ipCount] = 'ip=' . $ipv4->ip . '/24' . ',gw=' . $ipv4->gateway;
                $ipCount++;
            }
            if ($ipv6) {
                $network = $settings['model'] . '=' . ($ipv6->mac_address ? $ipv6->mac_address : $this->generateMacAddress()) . ',bridge=' . $ipv6->ipPool->network_interface;
                $network = $this->applyPoolSettings($ipv6->ipPool, $network);
                if ($ratelimit) {
                    $network .= ',rate=' . $ratelimit;
                }
                $config['net' . $ipCount] = $network;
                $config['ipconfig' . $ipCount] = 'ip6=' . $ipv6->ip . '/64' . ',gw6=' . $ipv6->gateway;
                $ipCount++;
            }
        }

        $this->request('/nodes/' . $server->node->name . '/qemu/' . $server->vm_id . '/config', 'put', $config, $server->node->location);
    }

    public function reinstall(Server $server)
    {
        ReinstallJob::dispatch($server);
    }

    private function calculateNode(Location $location, Service $service, $settings)
    {
        
        $cpuNeeded = $settings['cores'] * $settings['sockets'];
        $memoryNeeded = (int) $settings['memory'];
        $diskNeeded = (int) $settings['disk'];

        if ($location->server_placement == 'random') {
            $node = $location->nodes()->inRandomOrder()->first();
        } elseif ($location->server_placement == 'fill') {
            $nodes = $location->nodes()->get();
            $node = null;
            foreach ($nodes as $n) {
                if ($n->canStoreServer($memoryNeeded, $diskNeeded, $cpuNeeded)) {
                    $node = $n;
                    break;
                }
            }
        } elseif ($location->server_placement == 'least_loaded_server') {
            $nodes = $location->nodes()->withCount('servers')->orderBy('servers_count', 'asc')->get();
            $node = null;
            foreach ($nodes as $n) {
                if ($n->canStoreServer($memoryNeeded, $diskNeeded, $cpuNeeded)) {
                    $node = $n;
                    break;
                }
            }
        } elseif ($location->server_placement == 'least_loaded_ram') {
            $nodes = $location->nodes()->withCount('servers')->orderBy('memory', 'asc')->get();
            $nodeAndRam = [];
            foreach ($nodes as $n) {
                if ($n->canStoreServer($memoryNeeded, $diskNeeded, $cpuNeeded)) {
                    $nodeAndRam[$n->id] = $n->ram - $n->resourcesAvailable()['memory'];
                }
            }
            asort($nodeAndRam);
            $node = null;
            foreach ($nodeAndRam as $id => $ram) {
                $node = Node::findOrFail($id);
                break;
            }
        } elseif ($location->server_placement == 'least_loaded_disk') {
            $nodes = $location->nodes()->withCount('servers')->orderBy('disk', 'asc')->get();
            $nodeAndDisk = [];
            foreach ($nodes as $n) {
                if ($n->canStoreServer($memoryNeeded, $diskNeeded, $cpuNeeded)) {
                    $nodeAndDisk[$n->id] = $n->disk - $n->resourcesAvailable()['disk'];
                }
            }
            asort($nodeAndDisk);
            $node = null;
            foreach ($nodeAndDisk as $id => $disk) {
                $node = Node::findOrFail($id);
                break;
            }
        } elseif ($location->server_placement == 'least_loaded_cpu') {
            $nodes = $location->nodes()->withCount('servers')->orderBy('cpu', 'asc')->get();
            $nodeAndCpu = [];
            foreach ($nodes as $n) {
                if ($n->canStoreServer($memoryNeeded, $diskNeeded, $cpuNeeded)) {
                    $nodeAndCpu[$n->id] = $n->cpu - $n->resourcesAvailable()['cpu'];
                }
            }
            asort($nodeAndCpu);
            $node = null;
            foreach ($nodeAndCpu as $id => $cpu) {
                $node = Node::findOrFail($id);
                break;
            }
        } else {
            throw new Exception('Invalid server placement');
        }
        if (!$node) {
            throw new Exception('No node found with enough resources');
        }

        return $node;
    }

    private function getAvailableIps(Node $node, $amount, $type = 'ipv4')
    {
        $pool = $node->ipPools()->whereHas('ipAddresses', function ($query) use ($type) {
            $query->whereNull('server_id')->where('type', $type);
        })->withCount([
            'ipAddresses' => function ($query) use ($type) {
                $query->whereNull('server_id')->where('type', $type);
            },
        ])->get()->filter(function ($pool) use ($amount) {
            return $pool->ip_addresses_count >= $amount;
        });

        if ($pool->count() == 0) {
            throw new Exception('No IP pool found with enough available ' . strtoupper($type) . ' addresses');
        }
        $pool = $pool->first();

        return (object) [
            'ips' => $pool->ipAddresses()->whereNull('server_id')->where('type', $type)->take($amount)->get(),
            'pool' => $pool,
        ];
    }

    public function recreateVncUser(Server $server)
    {
        try {
            $this->request('/access/users/' . 'PAYMENTER_VNC_' . $server->id . '@pve', 'delete', location: $server->node->location);
        } catch (Exception $e) {
        }

        $password = Str::password();
        $this->request('/access/users', 'post', [
            'userid' => 'PAYMENTER_VNC_' . $server->id . '@pve',
            'enable' => 1,
            'password' => $password,
        ], location: $server->node->location);
        try {
            $vncRole = $this->request('/access/acl', 'PUT', [
                'path' => '/vms/' . $server->vm_id,
                'roles' => 'PAYMENTER_VNC',
                'users' => 'PAYMENTER_VNC_' . $server->id . '@pve',
            ], location: $server->node->location);
        } catch (Exception $e) {
            $this->request('/access/roles', 'post', [
                'roleid' => 'PAYMENTER_VNC',
                'privs' => 'VM.Console',
            ], location: $server->node->location);
            $this->request('/access/acl', 'PUT', [
                'path' => '/vms/' . $server->vm_id,
                'roles' => 'PAYMENTER_VNC',
                'users' => 'PAYMENTER_VNC_' . $server->id . '@pve',
            ], location: $server->node->location);
        }

        return $password;
    }

    public function vnc(Server $server)
    {
        $password = $this->recreateVncUser($server);
        $vmType = $server->os->isLxc() ? 'lxc' : 'qemu';
        $consoleType = $server->os->isLxc() ? 'lxc' : 'kvm';
        $response = Http::withOptions([
            'verify' => $server->node->location->verify_ssl ? true : false,
        ])->post(rtrim($server->node->location->host, '/') . '/api2/json/access/ticket', [
            'username' => 'PAYMENTER_VNC_' . $server->id . '@pve',
            'password' => $password,
        ])->throw()->json()['data'];

        $domain = rtrim($server->node->location->host, '/');
        $domain = preg_replace('/^https?:\/\
        $domain = preg_replace('/:\d+$/', '', $domain);

        $vnc = Http::withCookies([
            'PVEAuthCookie' => $response['ticket'],
        ], $domain)
            ->withHeader('CSRFPreventionToken', $response['CSRFPreventionToken'])
            ->withOptions([
                'verify' => $server->node->location->verify_ssl ? true : false,
            ])->post(rtrim($server->node->location->host, '/') . '/api2/json/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/vncproxy', [
                'websocket' => 1,
            ])->json()['data'];
        $paymenterDomain = rtrim(config('settings.app_url'), '/');
        $paymenterDomain = preg_replace('/^https?:\/\
        $paymenterDomain = preg_replace('/:\d+$/', '', $paymenterDomain);
        $parts = explode('.', $paymenterDomain);
        if (count($parts) >= 2) {
            $paymenterDomain = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
        }
        setcookie('PVEAuthCookie', $response['ticket'], 0, '/', $paymenterDomain, false, true);

        return rtrim($server->node->location->host, '/') .
            '/?console=' . $consoleType .
            '&novnc=1' .
            '&node=' . $server->node->name .
            '&resize=scale' .
            '&vmid=' . $server->vm_id .
            '&path=/api2/json/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/vncwebsocket';
    }

    
    public function createServer(Service $service, $settings, $properties)
    {
        if (Server::where('service_id', $service->id)->exists()) {
            throw new Exception('Server already exists');
        }
        $settings = array_merge($settings, $properties);

        $location = Location::findOrFail($settings['location_id']);
        $node = $this->calculateNode($location, $service, $settings);
        $os = $location->os()->findOrFail($settings['os']);
        $ipv4 = (object) [
            'ips' => collect(),
            'pool' => null,
        ];
        if (isset($settings['ipv4']) && $settings['ipv4'] > 0) {
            $ipv4 = $this->getAvailableIps($node, $settings['ipv4'], 'ipv4');
        }
        $ipv6 = (object) [
            'ips' => collect(),
            'pool' => null,
        ];
        if (isset($settings['ipv6']) && $settings['ipv6'] > 0) {
            $ipv6 = $this->getAvailableIps($node, $settings['ipv6'], 'ipv6');
        }

        $vm_id = $this->getNextAvailableVmId($location);

        $password = Str::password(16, symbols: false);
        $server = new Server;
        $server->vm_id = $vm_id;
        $server->node_id = $node->id;
        $server->os_id = $os->id;
        $server->service_id = $service->id;
        $server->primary_ipv4 = $ipv4->ips->first()->id ?? null;
        $server->primary_ipv6 = $ipv6->ips->first()->id ?? null;
        $server->user_id = $service->user_id;
        $server->hostname = $properties['hostname'];
        $server->save();
        foreach ($ipv4->ips as $ip) {
            $ip->server_id = $server->id;
            $ip->save();
        }
        foreach ($ipv6->ips as $ip) {
            $ip->server_id = $server->id;
            $ip->save();
        }

        $this->install($server, $settings, $password);
        if ($server->isNatMode()) {
            try {
                $natService = new \Paymenter\Extensions\Servers\Proxmox\Services\NatService();
                $natService->initializeNatRules($server->node, $server->primaryIpv4->ipPool);
                $portCount = (int) ($settings['nat_port_count'] ?? 20);
                $natService->allocatePortRange($server, $portCount);
            } catch (Exception $e) {
                \Log::error("Failed to initialize NAT for server {$server->id}: " . $e->getMessage());
            }
        }

        return [
            'password' => $password,
            'ip' => $server->primaryIpv4->ip ?? null,
            'ipv6' => $server->primaryIpv6->ip ?? null,
            'hostname' => $properties['hostname'],
        ];
    }

    private function getNextAvailableVmId(Location $location): int
    {
        $vm_id = $this->request('/cluster/nextid', location: $location)->json()['data'];
        $maxDbVmId = Server::whereHas('node', function ($query) use ($location) {
            $query->where('location_id', $location->id);
        })->max('vm_id') ?? 100;
        $vm_id = max($vm_id, $maxDbVmId + 1);

        return $vm_id;
    }

    
    public function suspendServer(Service $service, $settings, $properties)
    {
        $server = Server::where('service_id', $service->id)->first();
        $vmType = $server->os->isLxc() ? 'lxc' : 'qemu';
        try {
            $this->request('/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/status/stop', 'post', [
                'vmid' => $server->vm_id,
            ], location: $server->node->location);
        } catch (Exception $e) {
        }
        $configKey = $server->os->isLxc() ? 'onboot' : 'autostart';
        $this->request('/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/config', 'put', [
            $configKey => 0,
        ], $server->node->location);

        $server->status = 'suspended';
        $server->save();
    }

    
    public function unsuspendServer(Service $service, $settings, $properties)
    {
        $server = Server::where('service_id', $service->id)->first();
        $vmType = $server->os->isLxc() ? 'lxc' : 'qemu';
        $configKey = $server->os->isLxc() ? 'onboot' : 'autostart';
        $this->request('/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/config', 'put', [
            $configKey => 1,
        ], $server->node->location);

        $server->status = 'active';
        $server->save();
    }

    
    public function terminateServer(Service $service, $settings, $properties)
    {
        if (!Server::where('service_id', $service->id)->exists()) {
            return false;
        }

        $server = Server::where('service_id', $service->id)->first();
        $isLxc = $server->os && $server->os->type === 'lxc';
        $vmType = $isLxc ? 'lxc' : 'qemu';
        \Log::info("Terminate: Checking NAT mode for server {$server->id}");
        if ($server->isNatMode()) {
            \Log::info("Terminate: Server {$server->id} is NAT mode, removing port forwards");
            try {
                $natService = new \Paymenter\Extensions\Servers\Proxmox\Services\NatService();
                $natService->removeAllPortForwards($server);
                \Log::info("Terminate: NAT ports removed successfully for server {$server->id}");
            } catch (Exception $e) {
                \Log::error("Failed to remove NAT ports for server {$server->id}: " . $e->getMessage());
            }
        } else {
            \Log::info("Terminate: Server {$server->id} is not NAT mode, skipping port forward cleanup");
        }
        try {
            $this->request('/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/status/stop', 'post', [
                'vmid' => $server->vm_id,
            ], location: $server->node->location);
            sleep(3);
        } catch (Exception $e) {
            \Log::info("Server {$server->vm_id} stop failed (may already be stopped): " . $e->getMessage());
        }
        try {
            $backups = $this->request('/nodes/' . $server->node->name . '/storage/' . $server->node->backup_location . '/content', data: [
                'content' => 'backup',
                'vmid' => $server->vm_id,
            ], location: $server->node->location)['data'];

            foreach ($backups as $backup) {
                $this->request('/nodes/' . $server->node->name . '/storage/' . $server->node->backup_location . '/content/' . $backup['volid'], 'delete', location: $server->node->location);
            }
        } catch (Exception $e) {
        }
        $this->request('/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id, 'delete', location: $server->node->location);
        $server->delete();

        return true;
    }

    
    public function upgradeServer(Service $service, $settings, $properties)
    {
        if (!Server::where('service_id', $service->id)->exists()) {
            return false;
        }

        $server = Server::where('service_id', $service->id)->first();
        $this->syncServerSettings($server, $settings, null);

        return true;
    }

    public function getActions(Service $service)
    {
        if (Server::where('service_id', $service->id)->exists()) {
            return [
                [
                    'type' => 'button',
                    'label' => 'Go to server',
                    'url' => route('proxmox.server.overview', ['server' => Server::where('service_id', $service->id)->first()]),
                ],
            ];
        }

        return [];
    }
}
