<x-proxmox::layout :server="$server" view="overview">


    <div class="flex items-center justify-between mb-6" wire:poll.{{ $fastPolling ? '2s' : '30s' }}="checkStatus"
        wire:key="{{ $fastPolling ? 'fast' : 'slow' }}">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-server-fill class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Server Details</h2>
        </div>

        <div>
            <div class="flex flex-row gap-2 w-fit hidden lg:flex">
                <x-button.primary wire:click="do('start')" class="disabled:cursor-not-allowed"
                    wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="$status->status !== 'stopped'">
                    <x-loading target="do('start')" />
                    <x-ri-play-fill class="size-4" />
                    <div wire:loading.remove wire:target="do('start')">
                        Start
                    </div>
                </x-button.primary>
                <x-button.secondary wire:click="do('shutdown')" class="disabled:cursor-not-allowed"
                    wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="$status->status !== 'running'">
                    <x-loading target="do('shutdown')" />
                    <x-ri-stop-fill class="size-4" />
                    <div wire:loading.remove wire:target="do('shutdown')">
                        Stop
                    </div>
                </x-button.secondary>
                <x-button.secondary wire:click="do('reboot')" class="disabled:cursor-not-allowed"
                    :disabled="$status->status !== 'running'">
                    <x-loading target="do('reboot')" />
                    <x-ri-loop-left-line class="size-4" />
                    <div wire:loading.remove wire:target="do('reboot')">
                        Restart
                    </div>
                </x-button.secondary>
                <x-button.danger wire:click="do('stop')" class="text-nowrap disabled:cursor-not-allowed"
                    :disabled="$status->status !== 'running'">
                    <x-loading target="do('stop')" />
                    <x-ri-shut-down-line class="size-4" />
                    <div wire:loading.remove wire:target="do('stop')">
                        Power Off
                    </div>
                </x-button.danger>
            </div>

            <div class="flex lg:hidden">
                <x-dropdown>
                    <x-slot:trigger>
                        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                            <x-ri-more-2-fill class="size-5" />
                        </div>
                    </x-slot:trigger>
                    <x-slot:content>
                        <div class="flex flex-col gap-2">
                            <x-button.primary wire:click="do('start')" class="w-full disabled:cursor-not-allowed"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                                :disabled="$status->status !== 'stopped'">
                                <x-loading target="do('start')" />
                                <x-ri-play-fill class="size-4" />
                                <div wire:loading.remove wire:target="do('start')">
                                    Start
                                </div>
                            </x-button.primary>
                            <x-button.secondary wire:click="do('shutdown')" class="w-full disabled:cursor-not-allowed"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                                :disabled="$status->status !== 'running'">
                                <x-loading target="do('shutdown')" />
                                <x-ri-stop-fill class="size-4" />
                                <div wire:loading.remove wire:target="do('shutdown')">
                                    Stop
                                </div>
                            </x-button.secondary>
                            <x-button.secondary wire:click="do('reboot')" class="w-full disabled:cursor-not-allowed"
                                :disabled="$status->status !== 'running'">
                                <x-loading target="do('reboot')" />
                                <x-ri-loop-left-line class="size-4" />
                                <div wire:loading.remove wire:target="do('reboot')">
                                    Restart
                                </div>
                            </x-button.secondary>
                            <x-button.danger wire:click="do('stop')"
                                class="text-nowrap w-full disabled:cursor-not-allowed"
                                :disabled="$status->status !== 'running'">
                                <x-loading target="do('stop')" />
                                <x-ri-shut-down-line class="size-4" />
                                <div wire:loading.remove wire:target="do('stop')">
                                    Power Off
                                </div>
                            </x-button.danger>
                        </div>
                    </x-slot:content>
                </x-dropdown>
            </div>
        </div>

        <style>
            progress {
                border-radius: var(--radius-lg);
                background-color: hsl(var(--color-background));
                border: 1px solid hsl(var(--color-neutral));
                width: 100%;
                overflow: hidden;
            }

            progress[value]::-webkit-progress-bar {
                background-color: hsl(var(--color-background));
            }

            progress[value]#memory::-webkit-progress-value {
                background-color: color-mix(in oklab, hsl(var(--color-success)) 60%, transparent);
                border: 1px solid hsl(var(--color-success));
                border-radius: var(--radius-lg);
            }

            progress[value]#memory::-moz-progress-bar {
                background-color: color-mix(in oklab, hsl(var(--color-success)) 60%, transparent);
                border: 1px solid hsl(var(--color-success));
                border-radius: var(--radius-lg);
            }

            progress[value]#cpu::-webkit-progress-value {
                background-color: color-mix(in oklab, hsl(var(--color-info)) 60%, transparent);
                border: 1px solid hsl(var(--color-info));
                border-radius: var(--radius-lg);
            }

            progress[value]#cpu::-moz-progress-bar {
                background-color: color-mix(in oklab, hsl(var(--color-info)) 60%, transparent);
                border: 1px solid hsl(var(--color-info));
                border-radius: var(--radius-lg);
            }
        </style>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mt-8 items-start">
        <div class="grid gap-8 items-start">
            <!-- Details -->
            <!-- Show Currently installed OS and primary IP address -->
            <div
                class="flex flex-col gap-4 bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Status
                    </h2>

                    <div class="flex items-center gap-2">
                        <div class="relative inline-flex size-5">
                            @if ($status->status === 'running')
                            <span class="absolute inset-0 rounded-full bg-success/20 animate-ping"></span>
                            <span
                                class="relative flex items-center justify-center size-5 p-1 rounded-full text-success bg-success/20">
                                <x-ri-circle-fill />
                            </span>
                            @else
                            <span
                                class="flex items-center justify-center size-5 p-1 rounded-full text-warning bg-warning/20">
                                <x-ri-error-warning-fill />
                            </span>
                            @endif
                        </div>

                        <div class="text-md font-semibold text-base/60">
                            {{ $status->status === 'running' ? 'Running' : 'Stopped' }}
                        </div>

                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Operating System
                    </h2>

                    <div class="text-md font-semibold text-base/60">
                        {{ $server->os->name }}
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Hostname
                    </h2>

                    <div class="text-md font-semibold text-base/60">
                        {{ $status->name }}
                    </div>
                </div>

                @if($server->primaryIpv4)
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Primary IPv4 Address
                    </h2>

                    <div class="text-md font-semibold text-base/60">
                        {{ $server->primaryIpv4->ip }}
                    </div>
                </div>
                @endif
                @if($server->primaryIpv6)
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Primary IPv6 Address
                    </h2>

                    <div class="text-md font-semibold text-base/60">
                        {{ $server->primaryIpv6->ip }}
                    </div>
                </div>
                @endif
            </div>

            <!-- CPU Usage -->
            <div class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        CPU Usage
                    </h2>
                    <p class="text-md font-semibold text-base/60">Average system-wide CPU utilization</p>
                    <div id="cpu-chart" wire:ignore></div>
                </div>
            </div>
            <div class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Network Usage
                    </h2>
                    <p class="text-md font-semibold text-base/60">Network usage of the system</p>
                    <div id="network-chart" wire:ignore></div>
                </div>
            </div>
        </div>
        <div class="grid gap-8 items-start">
            <div class="flex gap-4">
                <div
                    class="flex flex-col h-fit w-full bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold">
                            CPU Usage
                        </h2>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-base/60">{{ number_format($status->cpu * 100, 1) }}%</span>
                            <progress value="{{ $status->cpu * 100 }}" max="100" id="cpu"></progress>
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-col w-full h-fit bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold">
                            Memory Usage
                        </h2>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-base/60">{{ number_format($status->maxmem > 0 ? ($status->mem
                                / $status->maxmem * 100) : 0, 1) }}%</span>
                            <progress value="{{ $status->mem }}" max="{{ $status->maxmem }}" id="memory"></progress>
                        </div>
                    </div>
                </div>
                @if($server->bandwidth_usage_percentage !== null)
                <div
                    class="flex flex-col w-full h-fit bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold">
                            Bandwidth Usage
                        </h2>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-base/60">{{ Number::percentage($server->bandwidth_usage_percentage, 1) }}</span>
                            <progress value="{{ $server->bandwidth_usage }}" max="{{ $server->bandwidth_limit }}" id="bandwidth"></progress>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Memory Usage -->
            <div class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Memory Usage
                    </h2>
                    <p class="text-md font-semibold text-base/60">Precise utilization at the recorded time</p>
                    <div id="memory-chart" wire:ignore></div>
                </div>
            </div>
            <div class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">
                        Disk Usage
                    </h2>
                    <p class="text-md font-semibold text-base/60">Usage of root partition</p>
                    <div id="disk-chart" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>


    @assets
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @endassets

    @script
    <script>
        const hi = '';

        console.log('693333', 'v1.6.1')

        function formatBytes(a, b = 2) {
            if (!+a) return "0 Bytes";
            const c = 0 > b ? 0 : b,
                d = Math.floor(Math.log(a) / Math.log(1024));
            return `${parseFloat((a/Math.pow(1024,d)).toFixed(c))} ${["Bytes","KiB","MiB","GiB","TiB","PiB","EiB","ZiB","YiB"][d]}`
        }
        var maxMem = {{ $status->maxmem }};
        var baseOptions = {
            chart: {
                type: 'area',
                height: 300,
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false
                },
                background: 'transparent'
            },
            grid: {
                show: true,
                borderColor: 'hsl(var(--color-neutral))',
                strokeDashArray: 3,
                position: 'back',
                xaxis: {
                    lines: {
                        show: false,
                    }
                },
                yaxis: {
                    lines: {
                        show: true,
                    }
                },
                row: {
                    colors: 'hsl(var(--color-neutral))',
                    opacity: 0
                }
            },
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'light' : 'dark',
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    datetimeUTC: false,
                    style: {
                        colors: 'color-mix(in oklab, hsl(var(--color-base)) 60%, transparent)',
                    },
                },
            },
            stroke: {
                curve: 'smooth',
                width: 2,
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: false,
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                showForSingleSeries: true,
            },
            noData: {
                text: 'Loading...'
            }
        }
        var cpuOptions = {
            ...baseOptions,
            series: [{
                name: 'CPU Usage',
                data: [],
            }],
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toFixed(1) + "%";
                    },
                    style: {
                        colors: ['color-mix(in oklab, hsl(var(--color-base)) 60%, transparent)'],
                    },
                }
            },
            colors: ['#008ffb'],
        };
        var memoryOptions = {
            ...baseOptions,
            series: [{
                name: 'Memory Usage',
                data: [],
            }, {
                name: 'Memory Max',
                data: [],
            }],
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return formatBytes(val, 1);
                    },
                    style: {
                        colors: ['color-mix(in oklab, hsl(var(--color-base)) 60%, transparent)'],
                    },
                },
                max: maxMem,
                min: 0,
            },
            colors: ['#00E396', '#159368'],
        };

        var networkOptions = {
            ...baseOptions,
            series: [{
                name: 'Network In',
                data: []
            }, {
                name: 'Network Out',
                data: []
            }],
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return formatBytes(val);
                    },
                    style: {
                        colors: ['color-mix(in oklab, hsl(var(--color-base)) 60%, transparent)'],
                    },
                }
            },
            colors: ['#a37443', '#dcce58'],

        };

        var diskOptions = {
            ...baseOptions,
            series: [{
                name: 'Disk Read',
                data: []
            }, {
                name: 'Disk Write',
                data: []
            }],
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return formatBytes(val);
                    },
                    style: {
                        colors: ['color-mix(in oklab, hsl(var(--color-base)) 60%, transparent)'],
                    },
                }
            },
            colors: ['#9256b5', '#60496f'],
        };

        // Define charts
        var cpuChart;
        var memoryChart;
        var networkChart;
        var diskChart;

        requestAnimationFrame(() => {
            cpuChart = new ApexCharts(document.querySelector("#cpu-chart"), cpuOptions);
            cpuChart.render();
            memoryChart = new ApexCharts(document.querySelector("#memory-chart"), memoryOptions);
            memoryChart.render();
            networkChart = new ApexCharts(document.querySelector("#network-chart"), networkOptions);
            networkChart.render();
            diskChart = new ApexCharts(document.querySelector("#disk-chart"), diskOptions);
            diskChart.render();

            Livewire.dispatch('proxmox:stats');
        });

        Livewire.on('proxmox:stats:{{ $server->id }}', (data) => {
            data = data[0];

            cpuChart.updateSeries([{
                name: 'CPU Usage',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.cpu ? item.cpu * 100 : 0,
                    }
                }),
            }]);


            memoryChart.updateSeries([{
                name: 'Memory Usage',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.mem ? item.mem : 0,
                    }
                }),
            }, {
                name: 'Memory Max',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.maxmem ? item.maxmem : 0,
                    }
                })
            }]);

            networkChart.updateSeries([{
                name: 'Network In',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.netin ? item.netin : 0,
                    }
                }),
            }, {
                name: 'Network Out',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.netout ? item.netout : 0,
                    }
                })
            }]);

            diskChart.updateSeries([{
                name: 'Disk Read',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.diskread ? item.diskread : 0,
                    }
                }),
            }, {
                name: 'Disk Write',
                data: data.map(function(item) {
                    return {
                        x: item.time * 1000,
                        y: item.diskwrite ? item.diskwrite : 0,
                    }
                })
            }]);
        });

        // Set interval and refresh charts every 2 minutes
        setInterval(() => {
            Livewire.dispatch('proxmox:stats');
        }, 120000);
    </script>
    @endscript
</x-proxmox::layout>