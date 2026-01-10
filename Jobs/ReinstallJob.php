<?php

namespace Paymenter\Extensions\Servers\Proxmox\Jobs;

use App\Helpers\ExtensionHelper;
use App\Helpers\NotificationHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class ReinstallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    // Timeout
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(public Server $server) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $proxmox = new Proxmox;
        // Default to qemu if os is null or type is not set
        $isLxc = $this->server->os && $this->server->os->type === 'lxc';
        $vmType = $isLxc ? 'lxc' : 'qemu';
        
        // Stop and delete server (try both types in case OS type changed)
        // Try stopping as both LXC and QEMU since the old type might differ from new
        foreach (['lxc', 'qemu'] as $type) {
            try {
                $proxmox->requestAndWait('/nodes/' . $this->server->node->name . '/' . $type . '/' . $this->server->vm_id . '/status/stop', 'post', [
                    'vmid' => $this->server->vm_id,
                ], $this->server->node->name, $this->server->node->location);
                \Log::info("Reinstall: Stopped {$type} {$this->server->vm_id}");
                break; // Success, no need to try other type
            } catch (Exception $e) {
                // Ignore errors, try next type
                \Log::info("Reinstall: Stop {$type} failed: " . $e->getMessage());
            }
        }

        // Try deleting as both LXC and QEMU
        foreach (['lxc', 'qemu'] as $type) {
            try {
                $proxmox->requestAndWait('/nodes/' . $this->server->node->name . '/' . $type . '/' . $this->server->vm_id, 'delete', [], $this->server->node->name, location: $this->server->node->location);
                \Log::info("Reinstall: Deleted {$type} {$this->server->vm_id}");
                break; // Success, no need to try other type
            } catch (Exception $e) {
                // Ignore "does not exist" errors
                if (!Str::contains($e->getMessage(), 'does not exist')) {
                    \Log::info("Reinstall: Delete {$type} failed: " . $e->getMessage());
                }
            }
        }

        $password = Str::password(16);

        // Create the server

        $settings = array_merge(ExtensionHelper::settingsToArray($this->server->service->product->settings), ExtensionHelper::getServiceProperties($this->server->service));

        // Create the server
        $proxmox->install($this->server, $settings, $password);

        NotificationHelper::serverCreatedNotification($this->server->service->user, $this->server->service, [
            'password' => $password,
            'ip' => $this->server->primaryIpv4 ? $this->server->primaryIpv4->ip : null,
            'ipv6' => $this->server->primaryIpv6 ? $this->server->primaryIpv6->ip : null,
            'hostname' => $this->server->hostname,
        ]);
    }
}
