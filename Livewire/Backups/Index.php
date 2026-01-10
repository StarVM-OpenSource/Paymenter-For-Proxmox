<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire\Backups;

use Livewire\Attributes\Locked;
use Paymenter\Extensions\Servers\Proxmox\Livewire\Component;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Index extends Component
{
    #[Locked]
    public $backups = [];

    public $showRestore = false;

    public $confirmRestore = false;

    public $volid;

    public function mount(Server $server)
    {
        parent::mount($server);

        $proxmox = new Proxmox;
        $settings = $this->server->service->product->settings()->where('key', 'backups')->first();
        if ($settings?->value <= 0) {
            return $this->redirect(route('proxmox.server.overview', $this->server->id), true);
        }

        $this->backups = $proxmox->request('/nodes/' . $this->server->node->name . '/storage/' . $this->server->node->backup_location . '/content', data: [
            'content' => 'backup',
            'vmid' => $this->server->vm_id,
        ], location: $this->server->node->location)->json()['data'];
    }

    public function restoreBackup()
    {
        $this->validate([
            'volid' => 'required',
        ]);

        if (!$this->confirmRestore) {
            $this->notify('Please confirm the restore action', 'error');

            return;
        }

        // Check if backup exists
        $backup = collect($this->backups)->firstWhere('volid', $this->volid);
        if (!$backup) {
            $this->notify('Backup not found', 'error');

            return;
        }

        $proxmox = new Proxmox;
        $vmType = $this->server->os->isLxc() ? 'lxc' : 'qemu';
        $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType, 'post', data: [
            'vmid' => $this->server->vm_id,
            'force' => true,
            'archive' => $this->volid,
        ], location: $this->server->node->location);

        $this->notify('Backup restoration in progress', 'success');

        $this->showRestore = false;

        // Redirect to the server overview page
        $this->redirect(route('proxmox.server.overview', $this->server->id), true);
    }

    public function deleteBackup($volid)
    {
        // Check if backup exists
        $backup = collect($this->backups)->firstWhere('volid', $volid);
        if (!$backup) {
            $this->notify('Backup not found', 'error');

            return;
        }

        $proxmox = new Proxmox;
        $vmType = $this->server->os->isLxc() ? 'lxc' : 'qemu';
        $proxmox->requestAndWait('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/status/stop', 'post', [
            'vmid' => $this->server->vm_id,
        ], $this->server->node->name, $this->server->node->location);

        $response = $proxmox->request('/nodes/' . $this->server->node->name . '/storage/' . $this->server->node->backup_location . '/content/' . $volid, 'delete', location: $this->server->node->location);

        if ($response->status() == 200) {
            $this->notify('Backup deleted successfully', 'success');
            $this->backups = array_filter($this->backups, fn ($backup) => $backup['volid'] !== $volid);
        } else {
            $this->notify('Failed to delete backup: ' . $response->json()['errors'][0]['message'], 'error');
        }
    }

    public function render()
    {
        return view('proxmox::backups.index')->layoutData([
            'sidebar' => true,
        ]);
    }
}
