<x-proxmox::layout :server="$server" view="backups">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-hard-drive-fill class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Backup Details</h2>
        </div>

        <div>
            <div class="flex flex-row gap-2 w-fit hidden lg:flex">
                <livewire:proxmox.backups.create :server="$server" />
            </div>
        </div>

    </div>

    <div class="gap-4 flex flex-col">
        @foreach($backups as $backup)
        @php $backup = (object) $backup; @endphp
        <div
            class="flex flex-col gap-4 bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
            <div class="grid md:grid-cols-4 grid-cols-1 gap-4">
                <!-- Address -->
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">Backup</h2>
                    <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                        <span>{{ $backup->notes }}</span>
                    </div>
                </div>
                <!-- Gateway -->
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">Size</h2>
                    <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                        <span>{{ Illuminate\Support\Number::fileSize($backup->size, 2) }}</span>
                    </div>
                </div>
                <!-- Gateway -->
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">Created At</h2>
                    <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                        <span>{{ date('Y-m-d H:i:s', $backup->ctime) }}</span>
                    </div>
                </div>
                <!-- Restore/delete -->
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">Actions</h2>
                    <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                        <button @click="$wire.set('showRestore', true); $wire.set('volid', '{{ $backup->volid }}')"
                            class="bg-background-secondary border border-neutral p-2 rounded-lg w-9 flex items-center justify-center">
                            <x-ri-refresh-fill />
                        </button>
                        <button
                            onclick="confirm('Are you sure you want to delete this backup?') || event.stopImmediatePropagation()"
                            wire:click="deleteBackup('{{ $backup->volid }}')"
                            class="bg-background-secondary border border-neutral p-2 rounded-lg w-9 flex items-center justify-center">
                            <x-ri-delete-bin-fill />
                        </button>
                    </div>
                </div>
{{--
                <div class="flex">
                    <x-dropdown>
                        <x-slot:trigger>
                            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                                <x-ri-more-fill class="size-5" />
                            </div>
                        </x-slot:trigger>
                        <x-slot:content>
                            <div class="flex flex-col gap-2">

                                <x-button.secondary >
                                    <x-ri-refresh-fill class="size-4" />
                                        Restore
                                </x-button.secondary>

                                <x-button.danger >
                                    <x-ri-delete-bin-fill class="size-4" />
                                        Delete
                                </x-button.danger>
                            </div>
                        </x-slot:content>
                    </x-dropdown>
                </div>
--}}
            </div>
        </div>
        @endforeach
    </div>
    @if($showRestore)
    <x-modal id="restore-backup" title="Restore Backup" open>
        <div class="flex flex-col gap-4">
            <x-form.checkbox label="I'm sure I want to restore this backup and delete all current data"
                wire:model="confirmRestore" name="confirmRestore" required />
        </div>

        <div class="flex flex-row gap-4 mt-4">
            <x-button.secondary wire:click="$set('showRestore', false)">Cancel</x-button.secondary>
            <x-button.primary wire:click="restoreBackup" wire:loading.attr="disabled" wire:loading.class="opacity-50"
                wire:loading.attr="aria-busy" wire:target="restoreBackup">
                <x-loading target="restoreBackup" />
                <div wire:loading.remove wire:target="restoreBackup">
                    Restore
                </div>
            </x-button.primary>
        </div>
    </x-modal>
    @endif

</x-proxmox::layout>