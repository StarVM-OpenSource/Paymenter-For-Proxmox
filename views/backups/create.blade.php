<div class="">
    <x-button.primary
        wire:click="$set('visible', true)">
        <x-ri-add-circle-fill class="size-4" />
        Create Backup
    </x-button.primary>
    @if($visible)
        <x-modal id="backup" title="Backup Server" open>
            <div class="flex flex-col gap-4">
                <x-form.input label="Backup Name" wire:model="name" placeholder="Enter backup name" name="name" required />

                <x-form.select label="Backup Type" wire:model="type" placeholder="Select backup type" name="type">
                    <option value="snapshot">Snapshot (Takes a snapshot of the current state of the VM)</option>
                    <option value="suspend">Suspend (Suspends the VM and takes a backup of the suspended state)</option>
                    <option value="stop">Stop (Stops the VM and takes a backup of the stopped state)</option>
                </x-form.select>

                <x-form.select label="Compression" wire:model="compression" placeholder="Select compression type" name="compression">
                    <option value="zstd">ZSTD</option>
                    <option value="gzip">Gzip</option>
                    <option value="lzo">LZO</option>
                    <option value="none">None</option>
                </x-form.select>
            </div>
        
            <div class="flex flex-row gap-4 mt-4">
                <x-button.secondary wire:click="$set('visible', false)">Cancel</x-button.secondary>
                <x-button.primary wire:click="backup" wire:loading.attr="disabled" wire:loading.class="opacity-50" wire:loading.attr="aria-busy" wire:target="reinstall">
                    <x-loading target="backup" />
                    <div wire:loading.remove wire:target="backup">
                        Create backup
                    </div>
                </x-button.primary>
            </div>
        </x-modal>
    @endif
</div>