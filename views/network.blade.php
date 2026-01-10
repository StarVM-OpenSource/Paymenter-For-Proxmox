<x-proxmox::layout :server="$server" view="network">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-router-fill class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Network Details</h2>
        </div>
    </div>

    <div class="flex flex-col gap-4 bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
        <div class="grid md:grid-cols-3 grid-cols-1 gap-4">
            <!-- Address -->
            <div class="flex flex-col gap-2">
                <h2 class="text-lg font-semibold">Address</h2>
                @foreach($server->ipAddresses as $network)
                <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                    <span>{{ $network->ip }}</span>
                    <div
                        x-data="{
                        copied: false,
                        copyText: '{{ $network->ip }}',
                        copyToClipboard() {
                            navigator.clipboard.writeText(this.copyText);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 3000);
                        }
                    }"
                        class="flex items-center">

                        <button
                            @click="copyToClipboard()"
                            type="button"
                            class="bg-background-secondary border border-neutral p-2 rounded-lg w-8 h-8 flex items-center justify-center">
                            <div class="size-4 absolute" x-show="copied" x-transition>
                                <x-ri-check-fill class="text-success stroke-current" />
                            </div>
                            <div class="size-4 absolute" x-show="!copied" x-transition>
                                <x-ri-file-copy-fill />
                            </div>
                        </button>

                    </div>
                </div>
                @endforeach
            </div>
            <!-- Gateway -->
            <div class="flex flex-col gap-2">
                <h2 class="text-lg font-semibold">Gateway</h2>
                @foreach($server->ipAddresses as $network)
                <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                    <span>{{ $network->gateway }}</span>
                    <div
                        x-data="{
                        copied: false,
                        copyText: '{{ $network->gateway }}',
                        copyToClipboard() {
                            navigator.clipboard.writeText(this.copyText);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 3000);
                        }
                    }"
                        class="flex items-center">

                        <button
                            @click="copyToClipboard()"
                            type="button"
                            class="bg-background-secondary border border-neutral p-2 rounded-lg w-8 h-8 flex items-center justify-center">
                            <div class="size-4 absolute" x-show="copied" x-transition>
                                <x-ri-check-fill class="text-success stroke-current" />
                            </div>
                            <div class="size-4 absolute" x-show="!copied" x-transition>
                                <x-ri-file-copy-fill />
                            </div>
                        </button>

                    </div>
                </div>
                @endforeach
            </div>
            <!-- Netmask -->
            <div class="flex flex-col gap-2">
                <h2 class="text-lg font-semibold">Netmask</h2>
                @foreach($server->ipAddresses as $network)
                <div class="text-md font-semibold text-base/60 flex items-center gap-2">
                    <span>{{ $network->netmask }}</span>
                    <div
                        x-data="{
                        copied: false,
                        copyText: '{{ $network->netmask }}',
                        copyToClipboard() {
                            navigator.clipboard.writeText(this.copyText);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 3000);
                        }
                    }"
                        class="flex items-center">

                        <button
                            @click="copyToClipboard()"
                            type="button"
                            class="bg-background-secondary border border-neutral p-2 rounded-lg w-8 h-8 flex items-center justify-center">
                            <div class="size-4 absolute" x-show="copied" x-transition>
                                <x-ri-check-fill class="text-success stroke-current" />
                            </div>
                            <div class="size-4 absolute" x-show="!copied" x-transition>
                                <x-ri-file-copy-fill />
                            </div>
                        </button>

                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</x-proxmox::layout>