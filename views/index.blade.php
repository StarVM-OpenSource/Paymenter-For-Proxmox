<div class="flex flex-col gap-4 container mt-14">
    <div class="flex flex-row items-center pb-4">
        <span class="text-2xl font-bold">Servers</span>
    </div>

    @foreach($servers as $server)
    <livewire:proxmox.server :server="$server" wire:key="proxmox-server-{{ $server->id }}" lazy />
    @endforeach
</div>