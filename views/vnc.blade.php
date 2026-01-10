<x-proxmox::layout :server="$server" view="vnc">

    <iframe src="{{ $vnc }}" class="w-full min-h-[800px] border-0" height="800" allowfullscreen>
    </iframe>
</x-proxmox::layout>