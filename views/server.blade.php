<div class="space-y-4">
    <a href="{{ route('proxmox.server.overview', ['server' => $server->id]) }}" wire:navigate>
        <div class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg mb-4">

            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <div class="bg-secondary/10 p-2 rounded-lg">
                        <x-ri-server-line class="size-5 text-secondary" />
                    </div>
                    <span class="font-medium">{{ $status->name }}</span>
                </div>
                <div
                    class="size-5 rounded-md p-0.5
                        @if($status->status == 'running') text-success bg-success/20
                        @elseif($status->status == 'stopped') text-inactive bg-inactive/20
                        @else text-warning bg-warning/20 @endif">
                    @if($status->status == 'running')
                        <x-ri-checkbox-circle-fill />
                    @elseif($status->status == 'stopped')
                        <x-ri-forbid-fill />
                    @else
                        <x-ri-error-warning-fill />
                    @endif
                </div>
            </div>

            <div class="flex flex-row gap-6 mt-4">
                @if($server->primaryIpv4)
                <div class="flex flex-col">
                    <span class="text-xs text-base/50">IP Address</span>
                    <span class="text-base">{{ $server->primaryIpv4->ip }}</span>
                </div>
                @endif
                @if($server->primaryIpv6)
                <div class="flex flex-col">
                    <span class="text-xs text-base/50">IP Address (IPv6)</span>
                    <span class="text-base">{{ $server->primaryIpv6->ip }}</span>
                </div>
                @endif

                <div class="flex flex-col">
                    <span class="text-xs text-base/50">Memory</span>
                    <span class="text-base">
                        @if($status->status == 'running')
                            {{ Illuminate\Support\Number::fileSize(bytes: $status->mem) }}/
                        @endif
                        {{ Illuminate\Support\Number::fileSize(bytes: $status->maxmem) }}
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-base/50">Disk</span>
                    <span class="text-base">
                        {{ Illuminate\Support\Number::fileSize(bytes: $status->maxdisk) }}
                    </span>
                </div>
                @if($status->status == 'running')
                <div class="flex flex-col">
                    <span class="text-xs text-base/50">CPU</span>
                    <span class="text-base">{{ round($status->cpu * 100, 2) }}%</span>
                </div>
                @endif
            </div>
        </div>
    </a>
</div>
