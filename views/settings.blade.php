<x-proxmox::layout :server="$server" view="settings">
    <div class=" bg-background-secondary border border-neutral rounded-lg">
        <!-- Hostname -->
        <div class="flex items-center justify-between gap-2 p-4">
            <div class="flex flex-col">

                <label class="text-lg font-semibold" for="hostname">
                    Hostname
                </label>
                <p class="text-sm font-semibold text-base/60">
                    Update the hostname of your server.
                </p>
            </div>
            <div class="flex gap-2 items-center">
                <x-form.input id="hostname" name="hostname" type="text" wire:model='hostname' />
                <x-button.primary wire:click="updateHostname" class="!w-fit" wire:loading.attr="disabled">
                    <div role="status" wire:loading wire:target='updateHostname'>
                        <x-ri-loader-5-fill aria-hidden="true" class="size-6 me-2 fill-background animate-spin" />
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div wire:loading.remove wire:target='updateHostname'>
                        Update
                    </div>
                </x-button.primary>
            </div>
        </div>

        <!-- Password reset -->
        <div class="flex flex-col gap-4 p-4 border-t border-neutral">
            <div class="flex items-center justify-between gap-2">
                <div class="flex flex-col">
                    <label class="text-lg font-semibold" for="password">
                        Root Password
                    </label>
                    <p class="text-sm font-semibold text-base/60">
                        Reset the root password of your server. A new password will be generated and sent to your email.
                    </p>
                </div>
                <div class="flex gap-2 items-center">
                    <x-button.primary wire:click="requestPasswordReset" class="!w-fit" wire:loading.attr="disabled">
                        <x-loading target="requestPasswordReset" />
                        <span wire:loading.remove wire:target="requestPasswordReset">
                            Reset Password
                        </span>
                    </x-button.primary>
                </div>
            </div>
            
            <!-- New Password Display -->
            @if($showPassword && $newPassword)
            <div class="bg-success/10 border border-success/30 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-success">New Password Generated</span>
                        <div class="flex items-center gap-2">
                            <code class="bg-background px-3 py-2 rounded font-mono text-lg select-all">{{ $newPassword }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $newPassword }}'); this.innerHTML='<svg class=\'size-5 text-success\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>'; setTimeout(() => this.innerHTML='<svg class=\'size-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'></path></svg>', 2000)" 
                                    class="p-2 hover:bg-background rounded transition-colors" title="Copy to clipboard">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                        <span class="text-xs text-base/60">This password has also been sent to your email. Save it now!</span>
                    </div>
                    <button wire:click="hidePassword" class="text-base/60 hover:text-base transition-colors">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            @endif
        </div>

        <div class="flex flex-col bg-error/10 border-t border-error/30 p-4 rounded-b-lg">
            <label class="text-lg font-semibold flex flex-row justify-between items-center gap-8" for="os">
                <div class="flex flex-col gap-2">
                    Reinstall Server
                    <p class="text-sm font-semibold text-base/60">The data on the server will be permanently deleted.
                        This action is irreversible and cannot be undone.</p>
                </div>
                <livewire:proxmox.reinstall :server="$server" />
            </label>
        </div>
    </div>
</x-proxmox::layout>