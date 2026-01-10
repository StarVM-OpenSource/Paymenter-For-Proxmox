<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use Livewire\Component;
use Paymenter\Extensions\Servers\Proxmox\Models\PortForward;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Services\NatService;

class PortForwards extends Component
{
    public Server $server;
    public $portForwards;
    
    // 添加新端口的表单
    public $newExternalPort = '';
    public $newInternalPort = '';
    public $newDescription = '';

    // 编辑端口的表单
    public $editingId = null;
    public $editInternalPort = '';
    public $editDescription = '';

    // 端口范围信息
    public $portStart = 0;
    public $portEnd = 0;

    public function mount(Server $server)
    {
        $this->server = $server;
        $this->loadPortForwards();
        $this->loadPortRange();
    }

    public function loadPortForwards()
    {
        $this->portForwards = $this->server->portForwards()->orderBy('external_port')->get();
    }

    public function loadPortRange()
    {
        // 从服务器的分配范围读取
        if ($this->server->port_range_start && $this->server->port_range_end) {
            $this->portStart = $this->server->port_range_start;
            $this->portEnd = $this->server->port_range_end;
        }
    }

    /**
     * 添加新的端口转发
     */
    public function addPort()
    {
        // 检查是否有端口范围分配
        if (!$this->portStart || !$this->portEnd) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No port range allocated for this server.']);
            return;
        }

        $this->validate([
            'newExternalPort' => "required|numeric|min:{$this->portStart}|max:{$this->portEnd}",
            'newInternalPort' => 'required|numeric|min:1|max:65535',
            'newDescription' => 'nullable|string|max:255',
        ]);

        $externalPort = (int) $this->newExternalPort;
        $internalPort = (int) $this->newInternalPort;

        // 检查端口是否已被使用
        $exists = PortForward::where('ip_pool_id', $this->server->primaryIpv4->ip_pool_id)
            ->where('external_port', $externalPort)
            ->exists();

        if ($exists) {
            $this->addError('newExternalPort', 'This external port is already in use.');
            return;
        }

        try {
            $natService = new NatService();
            
            $pf = PortForward::create([
                'server_id' => $this->server->id,
                'ip_pool_id' => $this->server->primaryIpv4->ip_pool_id,
                'protocol' => 'both',
                'external_port' => $externalPort,
                'internal_port' => $internalPort,
                'internal_ip' => $this->server->primaryIpv4->ip,
                'description' => $this->newDescription ?: null,
            ]);

            // 执行 iptables 规则
            $natService->executeIptablesRules($this->server->node, $pf->toIptablesAddRules());

            // 清空表单
            $this->newExternalPort = '';
            $this->newInternalPort = '';
            $this->newDescription = '';
            
            $this->loadPortForwards();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Port forward added successfully']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Failed to add port: ' . $e->getMessage()]);
        }
    }

    /**
     * 开始编辑端口
     */
    public function edit($id)
    {
        $pf = PortForward::find($id);
        if ($pf && $pf->server_id === $this->server->id) {
            $this->editingId = $id;
            $this->editInternalPort = $pf->internal_port;
            $this->editDescription = $pf->description;
        }
    }

    /**
     * 取消编辑
     */
    public function cancelEdit()
    {
        $this->editingId = null;
        $this->editInternalPort = '';
        $this->editDescription = '';
    }

    /**
     * 保存编辑
     */
    public function saveEdit()
    {
        $pf = PortForward::find($this->editingId);

        if (!$pf || $pf->server_id !== $this->server->id) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $this->validate([
            'editInternalPort' => 'required|numeric|min:1|max:65535',
            'editDescription' => 'nullable|string|max:255',
        ]);

        try {
            $natService = new NatService();
            $node = $this->server->node;

            // 先删除旧规则
            $natService->executeIptablesRules($node, $pf->toIptablesDeleteRules());

            // 更新端口
            $pf->internal_port = (int) $this->editInternalPort;
            $pf->description = $this->editDescription;
            $pf->save();

            // 添加新规则
            $natService->executeIptablesRules($node, $pf->toIptablesAddRules());

            $this->editingId = null;
            $this->loadPortForwards();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Port updated successfully']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Failed to update port: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除端口转发
     */
    public function deletePort($id)
    {
        $pf = PortForward::find($id);

        if (!$pf || $pf->server_id !== $this->server->id) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        try {
            $natService = new NatService();
            
            // 删除 iptables 规则
            $natService->executeIptablesRules($this->server->node, $pf->toIptablesDeleteRules());
            
            // 删除数据库记录
            $pf->delete();

            $this->loadPortForwards();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Port forward deleted successfully']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Failed to delete port: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('proxmox::portforwards')->layoutData([
            'sidebar' => true,
        ]);
    }
}


