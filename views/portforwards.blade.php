<x-proxmox::layout :server="$server" view="ports">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-share-forward-fill class="size-5" />
            </div>
            <div>
                <h2 class="text-xl font-semibold">Port Forwarding</h2>
                <p class="text-sm text-base/60">Manage your NAT port forwarding rules</p>
            </div>
        </div>
        @if($server->isNatMode())
        <div class="text-right">
            <div class="text-sm text-base/60">NAT IP</div>
            <div class="font-mono text-base">{{ $server->nat_ip }}</div>
        </div>
        @endif
    </div>

    @if(!$server->isNatMode())
    <div class="bg-warning-50 text-warning-700 p-4 rounded-lg border border-warning-200">
        This server is not in NAT mode. Port forwarding is not available.
    </div>
    @elseif(!$portStart || !$portEnd)
    <div class="bg-warning-50 text-warning-700 p-4 rounded-lg border border-warning-200">
        No port range has been allocated for this server. Please contact support.
    </div>
    @else

    <!-- 端口范围信息 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <div class="text-sm text-base/60">Your Port Range</div>
            <div class="text-xl font-bold font-mono">{{ $portStart }} - {{ $portEnd }}</div>
        </div>
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <div class="text-sm text-base/60">Total Ports</div>
            <div class="text-xl font-bold">{{ $portEnd - $portStart + 1 }}</div>
        </div>
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <div class="text-sm text-base/60">Used / Available</div>
            <div class="text-xl font-bold">{{ count($portForwards) }} / {{ ($portEnd - $portStart + 1) - count($portForwards) }}</div>
        </div>
    </div>

    <!-- 使用说明 -->
    <div class="bg-blue-50 border border-blue-200 text-blue-700 p-4 rounded-lg mb-6">
        <div class="flex items-start gap-2">
            <x-ri-information-line class="size-5 mt-0.5 flex-shrink-0" />
            <div class="text-sm">
                <p>You can use any external port from <strong>{{ $portStart }}</strong> to <strong>{{ $portEnd }}</strong> to forward to your server.</p>
                <p class="mt-1">Example: <code class="bg-blue-100 px-1 rounded">{{ $server->nat_ip }}:{{ $portStart }}</code> → your server port <code class="bg-blue-100 px-1 rounded">22</code> (SSH)</p>
            </div>
        </div>
    </div>

    <!-- 添加新端口 -->
    <div class="bg-background-secondary border border-neutral rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold mb-4">Add New Port Forward</h3>
        <form wire:submit.prevent="addPort" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-base/60 mb-1">External Port ({{ $portStart }}-{{ $portEnd }})</label>
                <input type="number" wire:model="newExternalPort" min="{{ $portStart }}" max="{{ $portEnd }}"
                    class="w-full bg-background border border-neutral rounded px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                    placeholder="{{ $portStart }}">
                @error('newExternalPort') <span class="text-danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="text-base/40 py-2">→</div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs text-base/60 mb-1">Internal Port (1-65535)</label>
                <input type="number" wire:model="newInternalPort" min="1" max="65535"
                    class="w-full bg-background border border-neutral rounded px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                    placeholder="22">
                @error('newInternalPort') <span class="text-danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-base/60 mb-1">Description (optional)</label>
                <input type="text" wire:model="newDescription"
                    class="w-full bg-background border border-neutral rounded px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                    placeholder="SSH, HTTP, Minecraft...">
            </div>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-600 transition-colors flex items-center gap-1">
                <x-ri-add-line class="size-5" />
                Add
            </button>
        </form>
    </div>

    <!-- 端口列表 -->
    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-background-tertiary border-b border-neutral">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-base/60 uppercase tracking-wider">External Address</th>
                        <th class="px-6 py-3 text-xs font-medium text-base/60 uppercase tracking-wider">→ Internal Port</th>
                        <th class="px-6 py-3 text-xs font-medium text-base/60 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-xs font-medium text-base/60 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral">
                    @forelse($portForwards as $pf)
                    <tr class="{{ $editingId === $pf->id ? 'bg-primary-50/10' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <code class="text-sm font-mono bg-background px-2 py-1 rounded">{{ $pf->external_address }}</code>
                                <button 
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText('{{ $pf->external_address }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="text-base/40 hover:text-base transition-colors">
                                    <x-ri-file-copy-line x-show="!copied" class="size-4" />
                                    <x-ri-check-line x-show="copied" class="size-4 text-success" />
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($editingId === $pf->id)
                            <input type="number" wire:model="editInternalPort" min="1" max="65535" 
                                class="w-24 bg-background border border-neutral rounded px-2 py-1 text-sm focus:ring-primary-500 focus:border-primary-500">
                            @else
                            <code class="font-mono">{{ $pf->internal_port }}</code>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-base/60 max-w-xs truncate">
                            @if($editingId === $pf->id)
                            <input type="text" wire:model="editDescription" 
                                class="w-full bg-background border border-neutral rounded px-2 py-1 text-sm focus:ring-primary-500 focus:border-primary-500" 
                                placeholder="Description">
                            @else
                            {{ $pf->description ?: '-' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($editingId === $pf->id)
                            <button wire:click="saveEdit" class="text-success hover:text-success-600 mr-2" title="Save">
                                <x-ri-check-line class="size-5" />
                            </button>
                            <button wire:click="cancelEdit" class="text-base/60 hover:text-base" title="Cancel">
                                <x-ri-close-line class="size-5" />
                            </button>
                            @else
                            <button wire:click="edit({{ $pf->id }})" class="text-primary hover:text-primary-600 mr-2" title="Edit">
                                <x-ri-edit-line class="size-5" />
                            </button>
                            <button wire:click="deletePort({{ $pf->id }})" 
                                onclick="return confirm('Are you sure you want to delete this port forward?')"
                                class="text-danger hover:text-danger-600" title="Delete">
                                <x-ri-delete-bin-line class="size-5" />
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-base/60">
                            <x-ri-server-line class="size-8 mx-auto mb-2 opacity-50" />
                            <p>No port forwards configured yet.</p>
                            <p class="text-xs mt-1">Add your first port forward above.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 常用端口参考 -->
    <div class="mt-6 p-4 bg-background-secondary border border-neutral rounded-lg">
        <h3 class="text-sm font-semibold mb-3">Common Port Reference</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
            <div class="flex justify-between"><span class="text-base/60">SSH:</span><code>22</code></div>
            <div class="flex justify-between"><span class="text-base/60">HTTP:</span><code>80</code></div>
            <div class="flex justify-between"><span class="text-base/60">HTTPS:</span><code>443</code></div>
            <div class="flex justify-between"><span class="text-base/60">MySQL:</span><code>3306</code></div>
            <div class="flex justify-between"><span class="text-base/60">PostgreSQL:</span><code>5432</code></div>
            <div class="flex justify-between"><span class="text-base/60">Redis:</span><code>6379</code></div>
            <div class="flex justify-between"><span class="text-base/60">Minecraft:</span><code>25565</code></div>
            <div class="flex justify-between"><span class="text-base/60">FTP:</span><code>21</code></div>
        </div>
    </div>
    @endif

</x-proxmox::layout>
