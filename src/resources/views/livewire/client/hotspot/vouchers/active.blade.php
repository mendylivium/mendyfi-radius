<div class="space-y-4" wire:poll.{{ $autoRefresh ? '10s' : '999999s' }}="$refresh">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Active Vouchers</h2>
            <p class="text-sm text-base-content/60">Currently connected sessions
                <span class="badge badge-primary badge-sm ml-1">{{ $this->voucher->total() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn btn-outline btn-sm gap-1.5" wire:click="exportCsv">
                <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4" />
                <span class="hidden sm:inline">Export CSV</span>
            </button>
            <x-mary-button
                icon="{{ $autoRefresh ? 'o-arrow-path' : 'o-pause' }}"
                wire:click="toggleAutoRefresh"
                class="{{ $autoRefresh ? 'btn-primary btn-sm' : 'btn-ghost btn-sm' }}"
                tooltip="{{ $autoRefresh ? 'Live refresh ON' : 'Auto-refresh OFF' }}" />
            <x-mary-input
                placeholder="Search..."
                wire:model.live="search"
                icon="o-magnifying-glass"
                class="w-full sm:w-64" />
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if(count($selectedItems) > 0)
    <div class="flex items-center gap-2 p-3 bg-warning/10 border border-warning/20 rounded-xl">
        <span class="text-sm font-medium">{{ count($selectedItems) }} selected</span>
        <x-mary-button
            label="Disconnect"
            icon="o-signal-slash"
            wire:click="bulkDisconnect"
            wire:confirm="Disconnect {{ count($selectedItems) }} selected user(s)?"
            class="btn-warning btn-sm" />
        <x-mary-button
            label="Delete"
            icon="o-trash"
            wire:click="bulkDelete"
            wire:confirm="Delete {{ count($selectedItems) }} active voucher(s)? This cannot be undone."
            class="btn-error btn-sm" />
    </div>
    @endif

    <x-mary-card shadow>
        @if($this->voucher->count() > 0)
        {{-- Desktop table --}}
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-sm" />
                        </th>
                        <th>Voucher</th>
                        <th>Session</th>
                        <th>Time</th>
                        <th>Data</th>
                        <th class="hidden lg:table-cell">Dates</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->voucher as $voucher)
                        @php
                            $timeRemaining  = $this->getTimeRemaining($voucher);
                            $timePercentage = $this->getTimePercentage($voucher);
                            $dataPercentage = $this->getDataPercentage($voucher);
                            $timeColor      = $this->getStatusColor($timePercentage);
                            $dataColor      = $this->getStatusColor($dataPercentage);

                            $worstPct = min($timePercentage ?? 100, $dataPercentage ?? 100);
                            $rowClass = match(true) {
                                $worstPct <= 25 => 'bg-error/10',
                                $worstPct <= 50 => 'bg-warning/10',
                                default         => '',
                            };
                        @endphp
                        <tr class="{{ $rowClass }} hover">
                            <td>
                                <input type="checkbox" wire:model.live="selectedItems"
                                    value="{{ $voucher->id }}" class="checkbox checkbox-sm" />
                            </td>
                            <td>
                                <div class="font-mono font-bold text-sm">{{ $voucher->code }}</div>
                                <div class="text-xs text-base-content/60">{{ $voucher->profile_name }}</div>
                                <x-partials.price-display :price="$voucher->price" />
                            </td>
                            <td class="text-xs">
                                <div>{{ $this->isRandomMac($voucher->mac_address) ? 'Rnd ' : '' }}{{ $voucher->mac_address ?? 'N/A' }}</div>
                                <div>IP: {{ $voucher->ip_address ?? 'N/A' }}</div>
                                <div>Router: {{ $voucher->router_ip ?? 'N/A' }}</div>
                                <div class="text-base-content/50">
                                    ↓ {{ $this->convertBytes($voucher->session_download) }}
                                    ↑ {{ $this->convertBytes($voucher->session_upload) }}
                                </div>
                            </td>
                            <td style="min-width: 120px;">
                                @if($timePercentage !== null)
                                    <span class="badge badge-sm badge-{{ $timeColor }}">{{ $timeRemaining['text'] ?? 'N/A' }}</span>
                                    <progress class="progress progress-{{ $timeColor }} w-full mt-1" value="{{ $timePercentage }}" max="100"></progress>
                                    <div class="text-xs text-base-content/50">{{ round($timePercentage) }}% left</div>
                                @else
                                    <span class="badge badge-sm badge-ghost">Unlimited</span>
                                @endif
                            </td>
                            <td style="min-width: 120px;">
                                @if($voucher->data_limit > 0)
                                    <span class="badge badge-sm badge-{{ $dataColor }}">{{ $this->convertBytes($voucher->data_credit) }}</span>
                                    <progress class="progress progress-{{ $dataColor }} w-full mt-1" value="{{ $dataPercentage ?? 0 }}" max="100"></progress>
                                    <div class="text-xs text-base-content/50">{{ round($dataPercentage ?? 0) }}% of {{ $this->convertBytes($voucher->data_limit) }}</div>
                                @else
                                    <span class="badge badge-sm badge-ghost">Unlimited</span>
                                @endif
                            </td>
                            <td class="hidden lg:table-cell text-xs text-base-content/60">
                                <div>Used: {{ $voucher->used_date ? \Illuminate\Support\Carbon::parse($voucher->used_date)->format('M d, Y H:i') : 'N/A' }}</div>
                                <div>Exp: {{ $voucher->expire_date ? \Illuminate\Support\Carbon::parse($voucher->expire_date)->format('M d, Y H:i') : 'N/A' }}</div>
                            </td>
                            <td>
                                <x-mary-button
                                    label="Disconnect"
                                    icon="o-signal-slash"
                                    class="btn-ghost btn-xs btn-error"
                                    wire:confirm.prompt="Disconnect user?\n\nType {{ substr($voucher->id, -5) }} to confirm|{{ substr($voucher->id, -5) }}"
                                    wire:click="disconnect({{ $voucher->id }})" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Legend --}}
        <div class="flex gap-4 mt-3 text-xs text-base-content/60">
            <span><span class="badge badge-success badge-xs">●</span> &gt;50%</span>
            <span><span class="badge badge-warning badge-xs">●</span> 25-50%</span>
            <span><span class="badge badge-error badge-xs">●</span> &lt;25%</span>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-between items-center mt-4 text-sm text-base-content/60">
            <span>Showing {{ $this->voucher->firstItem() ?? 0 }}–{{ $this->voucher->lastItem() ?? 0 }} of {{ $this->voucher->total() }}</span>
            {{ $this->voucher->links() }}
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
            <x-mary-icon name="o-wifi" class="w-12 h-12 mb-3" />
            <p class="text-sm">No active sessions found</p>
        </div>
        @endif
    </x-mary-card>
</div>
