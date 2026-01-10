<div>
    <x-button.danger wire:click="$set('visible', true)" wire:loading.attr="disabled">
        <x-loading target="visible" />
        <span wire:loading.remove wire:target="visible">

            Reinstall
        </span>
    </x-button.danger>
    @if($visible)
    <x-modal id="reinstall" title="Reinstall Server" open>
        <div class="flex flex-col gap-4">
            <x-form.select label="Operating System" wire:model="os" placeholder="Select OS name" name="os">
                @foreach($osList as $os)
                <option value="{{ $os->id }}">{{ $os->name }}</option>
                @endforeach
            </x-form.select>

            <x-form.checkbox label="I'm sure I want to reinstall this server and delete all data"
                wire:model="confirmReinstall" name="confirmReinstall" required />
        </div>

        <div class="flex flex-row gap-4 mt-4">
            <x-button.secondary wire:click="$set('visible', false)">Cancel</x-button.secondary>
            <x-button.primary wire:click="reinstall" wire:loading.attr="disabled" wire:loading.class="opacity-50"
                wire:loading.attr="aria-busy" wire:target="reinstall">
                <x-loading target="reinstall" />
                <div wire:loading.remove wire:target="reinstall">
                    Reinstall
                </div>
            </x-button.primary>
        </div>
    </x-modal>
    @endif
</div>