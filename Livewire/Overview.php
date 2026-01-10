<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Overview extends Component
{
    public Server $server;

    public $fastPolling = false;

    public $status;

    /**
     * Get the VM type path (qemu or lxc)
     */
    private function getVmType(): string
    {
        // Default to qemu if os is null or type is not set
        if (!$this->server->os || !$this->server->os->type) {
            return 'qemu';
        }
        return $this->server->os->isLxc() ? 'lxc' : 'qemu';
    }

    public function mount(Server $server)
    {
        parent::mount($server);

        $proxmox = new Proxmox;
        $vmType = $this->getVmType();
        $this->status = (object) $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/status/current', location: $this->server->node->location)->json()['data'];
    }

    public function checkStatus()
    {
        $currentStatus = $this->status->status;
        $proxmox = new Proxmox;
        $vmType = $this->getVmType();
        $this->status = (object) $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/status/current', location: $this->server->node->location)->json()['data'];
        if ($this->status->status != $currentStatus) {
            $this->fastPolling = false;
        }
    }

    #[On('proxmox:stats')]
    public function stats()
    {
        $data = Cache::remember('proxmox-stats-' . $this->server->id, 60, function () {
            $proxmox = new Proxmox;
            $vmType = $this->getVmType();
            $stats = $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/rrddata', data: [
                'timeframe' => 'hour',
            ], location: $this->server->node->location);

            return $stats->json()['data'];
        });

        $this->dispatch('proxmox:stats:' . $this->server->id, $data);
    }

    public function do($action)
    {
        if (!in_array($action, ['start', 'shutdown', 'reboot', 'stop'])) {
            $this->notify('Invalid action', 'error');

            return;
        }
        $proxmox = new Proxmox;
        $vmType = $this->getVmType();
        $response = $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/status/' . $action, 'post', [
            'vmid' => $this->server->vm_id,
        ], location: $this->server->node->location);

        if ($response->status() == 200) {
            $this->notify('Action performed successfully', 'success');
            $this->fastPolling = true;
        } else {
            $this->notify('Action failed: ' . $response->json()['errors'][0]['message'], 'error');
        }
    }

    public function render()
    {
        return view('proxmox::overview')->layoutData([
            'sidebar' => true,
        ]);
    }
}

