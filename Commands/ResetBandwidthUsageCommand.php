<?php

namespace Paymenter\Extensions\Servers\Proxmox\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Paymenter\Extensions\Servers\Proxmox\Jobs\SyncTrafficJob;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;

class ResetBandwidthUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:proxmox:reset-bandwidth-usage {--force : Force the reset even if not the first day of the month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes traffic data with Proxmox';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if today is the first day of the month
        if (Carbon::now()->day !== Carbon::now()->startOfMonth()->day && ! $this->option('force')) {
            $this->info('Bandwidth usage can only be reset on the first day of the month.');
            return;
        }

        // Reset bandwidth usage for all locations
        $locations = Location::all();
        foreach ($locations as $location) {
            $servers = $location->servers;
            foreach ($servers as $server) {
                $server->bandwidth_usage = 0;
                $server->save();
            }
        }
        $this->info('Bandwidth usage has been reset for all servers.');
    }
}
