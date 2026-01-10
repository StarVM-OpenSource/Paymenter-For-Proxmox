<?php

namespace Paymenter\Extensions\Servers\Proxmox\Jobs;

use App\Helpers\ExtensionHelper;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Paymenter\Extensions\Servers\Proxmox\Models\Location;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class SyncTrafficJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public Location $location)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Get all servers in this location
        $servers = $this->location->servers;
        $proxmox = new Proxmox;
        foreach ($servers as $server) {
            try {
                $vmType = $server->os->isLxc() ? 'lxc' : 'qemu';
                $traffic = $proxmox->request('/nodes/' . $server->node->name . '/' . $vmType . '/' . $server->vm_id . '/rrddata', data: [
                    'timeframe' => 'hour',
                ], location: $server->node->location)->json()['data'];

                $previouslyOverLimit = $server->isOverBandwidthLimit();

                $bandwidth = $server->bandwidth_usage;
                $date = $server->synced_at ?? Carbon::now()->firstOfMonth();
                foreach ($traffic as $dataPoint) {
                    if (Carbon::createFromTimestamp($dataPoint['time']) <= $date) {
                        continue;
                    }
                    $bandwidth += intval(floor(isset($dataPoint['netin']) ? $dataPoint['netin'] : 0)) * 60; // As proxmox gives per minute, convert to per second
                    $bandwidth += intval(floor(isset($dataPoint['netout']) ? $dataPoint['netout'] : 0)) * 60;
                }

                $server->bandwidth_usage = $bandwidth;
                $server->synced_at = Carbon::now();
                $server->save();

                if ($server->fresh()->isOverBandwidthLimit() && !$previouslyOverLimit) {
                    // Update server status to limit network
                    $settings = array_merge(ExtensionHelper::settingsToArray($server->service->product->settings), ExtensionHelper::getServiceProperties($server->service));

                    $proxmox->updateRatelimit($server, $settings, 1); // Limit to 1Mbps
                }
            } catch (Exception $e) {
                // Log error but continue with other servers
                \Log::error('Failed to sync traffic for Proxmox server ID ' . $server->id . ': ' . $e->getMessage());
            }
        }
    }
}
