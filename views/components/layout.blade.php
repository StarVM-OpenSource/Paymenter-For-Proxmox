<div class=" container mt-14">
    @if($server->status === 'active')

    <div>
        <div class="flex flex-row items-center pb-4">
            <span class="text-2xl font-bold">
                {{ $server->hostname }}
            </span>
        </div>

        <div class="mb-10 mt-3 flex flex-row gap-2">
            <div class="flex flex-col items-center">
                <a href="{{ route('proxmox.server.overview', ['server' => $server->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'overview' ? 'text-base' : 'text-base/70' }}">
                    Overview
                </a>
                @if($view === 'overview')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
            <div class="flex flex-col items-center">
                <a href="{{ route('proxmox.server.network', ['server' => $server->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'network' ? 'text-base' : 'text-base/70' }}">
                    Network
                </a>
                @if($view === 'network')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
            @if($server->service->product->settings()->where('key', 'backups')->first()?->value > 0)
            <div class="flex flex-col items-center">
                <a href="{{ route('proxmox.server.backups', ['server' => $server->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'backups' ? 'text-base' : 'text-base/70' }}">
                    Backups
                </a>
                @if($view === 'backups')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
            @endif
            @if($server->isNatMode())
            <div class="flex flex-col items-center">
                <a href="{{ route('proxmox.server.ports', ['server' => $server->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'ports' ? 'text-base' : 'text-base/70' }}">
                    Ports
                </a>
                @if($view === 'ports')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
            @endif
            @if($server->node->vnc_enabled)
            <div class="flex flex-col items-center">
                <a wire:navigate href="{{ route('proxmox.server.vnc', ['server' => $server->id]) }}"
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'vnc' ? 'text-base' : 'text-base/70' }}">
                    VNC
                </a>
                @if($view === 'vnc')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
            @endif
            <div class="flex flex-col items-center">
                <a href="{{ route('proxmox.server.settings', ['server' => $server->id]) }}" wire:navigate
                    class="flex items-center gap-2 justify-center text-base font-semibold py-2.5 lg:py-2 px-3 rounded-md hover:bg-background-secondary/80 transition-colors duration-300 {{ $view === 'settings' ? 'text-base' : 'text-base/70' }}">
                    Settings
                </a>
                @if($view === 'settings')
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-base"></span>
                @else
                <span class="block h-0.5 w-full mt-1.5 rounded-full bg-transparent"></span>
                @endif
            </div>
        </div>
    </div>

    {{ $slot }}
    @else
    @php 
        $statuses = [
            'suspended' => [
                'color' => 'text-yellow-500',
                'message' => 'This server is suspended. Please contact support for more information.'
            ],
            'installing' => [
                'color' => 'text-blue-500',
                'message' => "This server is currently being (re)installed. Please wait until the process is complete.\nThis may take a few minutes."
            ],
        ];
    @endphp
    <!-- Full screen placeholder for inactive servers -->
    <div class="flex items-center justify-center h-full" @if($server->status === 'installing') wire:poll.5s @endif wire:key='{{ $server->id }}-{{ $server->status }}'>
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-4">Oops!</h2>
            <p class="{{ $statuses[$server->status]['color'] ?? 'text-red-500' }}">
                {{ $statuses[$server->status]['message'] ?? 'This server is currently inactive.' }}
            </p>
        </div>
    </div>
    @endif
</div>