<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use App\Helpers\NotificationHelper;
use App\Rules\Domain;
use Illuminate\Support\Str;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Settings extends Component
{
    public $hostname = '';
    public $newPassword = null;
    public $showPassword = false;

    public function mount(Server $server)
    {
        parent::mount($server);
        $this->hostname = $server->hostname;
    }

    public function updateHostname()
    {
        $this->validate([
            'hostname' => [
                'required',
                'string',
                new Domain()
            ]
        ]);
        if ($this->hostname === $this->server->hostname) {
            $this->notify('Hostname is unchanged.', 'info');
            return;
        }
        // Update proxmox hostname 
        $proxmox = new Proxmox();
        $isLxc = $this->server->os && $this->server->os->type === 'lxc';
        $vmType = $isLxc ? 'lxc' : 'qemu';
        
        if ($isLxc) {
            // LXC uses 'hostname' directly
            $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/config', method: 'PUT', data: [
                'hostname' => $this->hostname,
            ], location: $this->server->node->location);
        } else {
            // QEMU uses 'name' and 'searchdomain'
            $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/config', method: 'PUT', data: [
                'name' => $this->hostname,
                'searchdomain' => $this->hostname,
            ], location: $this->server->node->location);
        }

        $this->server->update([
            'hostname' => $this->hostname,
        ]);

        $this->notify('Hostname updated successfully.', 'success');
    }

    public function requestPasswordReset()
    {
        try {
            $newPassword = Str::password(16, symbols: false);
            $proxmox = new Proxmox();
            $isLxc = $this->server->os && $this->server->os->type === 'lxc';
            $vmType = $isLxc ? 'lxc' : 'qemu';

            if ($isLxc) {
                // LXC: set password via SSH pct exec
                $this->setLxcPasswordViaSsh($newPassword);
            } else {
                // QEMU: use guest agent
                $os = $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/agent/get-osinfo', location: $this->server->node->location)->json();
                if (Str::contains(strtolower($os['data']['result']['name']), 'windows')) {
                    $username = 'Administrator';
                } else {
                    $username = 'root';
                }

                $proxmox->request('/nodes/' . $this->server->node->name . '/' . $vmType . '/' . $this->server->vm_id . '/agent/set-user-password', method: 'POST', data: [
                    'username' => $username,
                    'password' => $newPassword,
                ], location: $this->server->node->location);
            }

            // Show new password to user
            $this->newPassword = $newPassword;
            $this->showPassword = true;
            
            $this->notify('Password reset successfully! Your new password is displayed below.', 'success');

            // Send email notification
            NotificationHelper::serverCreatedNotification($this->server->service->user, $this->server->service, [
                'password' => $newPassword,
                'ip' => $this->server->primaryIpv4 ? $this->server->primaryIpv4->ip : null,
                'ipv6' => $this->server->primaryIpv6 ? $this->server->primaryIpv6->ip : null,
                'hostname' => $this->server->hostname,
            ]);
        } catch (\Exception $e) {
            \Log::error("Password reset failed for server {$this->server->id}: " . $e->getMessage());
            $this->notify('Failed to reset password: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Set LXC container password via SSH
     */
    private function setLxcPasswordViaSsh(string $password): void
    {
        $node = $this->server->node;
        
        if (!$node->ssh_host || !$node->ssh_password) {
            throw new \Exception('SSH is not configured for this node. Please contact support.');
        }

        $sshHost = $node->ssh_host;
        $sshHost = preg_replace('/^https?:\/\//', '', $sshHost);
        $sshHost = preg_replace('/:\d+$/', '', $sshHost);
        $sshPort = $node->ssh_port ?? 22;
        $sshPassword = $node->ssh_password;

        $connection = @ssh2_connect($sshHost, $sshPort);
        if (!$connection) {
            throw new \Exception('Could not connect to server via SSH.');
        }

        if (!@ssh2_auth_password($connection, 'root', $sshPassword)) {
            throw new \Exception('SSH authentication failed.');
        }

        // Escape password for shell
        $escapedPassword = str_replace("'", "'\\''", $password);
        $command = "pct exec {$this->server->vm_id} -- bash -c 'echo \"root:{$escapedPassword}\" | chpasswd'";
        
        $stream = ssh2_exec($connection, $command);
        if (!$stream) {
            throw new \Exception('Failed to execute password change command.');
        }

        stream_set_blocking($stream, true);
        $stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);
        $errorOutput = stream_get_contents($stderr);
        fclose($stream);

        if (!empty($errorOutput) && str_contains($errorOutput, 'not running')) {
            throw new \Exception('Container is not running. Please start it first.');
        }
    }

    public function hidePassword()
    {
        $this->showPassword = false;
        $this->newPassword = null;
    }

    public function render()
    {
        return view('proxmox::settings')->layoutData([
            'sidebar' => true,
        ]);
    }
}
