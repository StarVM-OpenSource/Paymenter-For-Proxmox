<?php

namespace Paymenter\Extensions\Servers\Proxmox\Commands;

use App\Classes\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Paymenter\Extensions\Servers\Proxmox\Jobs\SyncTrafficJob;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;
use PDO;

class SyncTrafficCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:proxmox:sync-traffic';

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
        $this->info('Starting Proxmox traffic synchronization...');
        // Get all locations
        $locations = Location::all();

        foreach ($locations as $location) {
            SyncTrafficJob::dispatch($location);
        }

        $this->info('Proxmox traffic synchronization jobs dispatched.');
    }
}
