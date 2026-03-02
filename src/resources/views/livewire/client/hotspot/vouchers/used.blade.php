<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Used Vouchers</h2>
            <p class="text-sm text-base-content/60">Historical session records
                <span class="badge badge-ghost badge-sm ml-1">{{ $this->vouchers->total() }}</span>
            </p>
        </div>
        <x-mary-input
            placeholder="Search..."
            wire:model.live.debounce.300ms="search"
            icon="o-magnifying-glass"
            class="w-full sm:w-64" />
    </div>

    {{-- Usage stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <x-mary-stat
            title="Total Used"
            :value="number_format($this->usageStats->total_used ?? 0)"
            icon="o-ticket"
            class="bg-base-100 shadow-sm"
            color="text-info" />
        <x-mary-stat
            title="Total Download"
            :value="$this->convertBytes($this->usageStats->total_download ?? 0)"
            icon="o-arrow-down-tray"
            class="bg-base-100 shadow-sm"
            color="text-success" />
        <x-mary-stat
            title="Total Upload"
            :value="$this->convertBytes($this->usageStats->total_upload ?? 0)"
            icon="o-arrow-up-tray"
            class="bg-base-100 shadow-sm"
            color="text-warning" />
    </div>

    {{-- Filters --}}
    <x-mary-card shadow>
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-40">
                <label class="label"><span class="label-text font-medium">Date Range</span></label>
                <select wire:model.live="dateRange" class="select select-bordered select-sm w-full">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            @if($dateRange === 'custom')
            <div>
                <label class="label"><span class="label-text font-medium">From</span></label>
                <input type="date" wire:model.live="dateFrom" class="input input-bordered input-sm" />
            </div>
            <div>
                <label class="label"><span class="label-text font-medium">To</span></label>
                <input type="date" wire:model.live="dateTo" class="input input-bordered input-sm" />
            </div>
            @endif
            <div class="mb-0.5">
                <x-mary-button
                    label="Clear"
                    icon="o-x-mark"
                    wire:click="clearFilters"
                    class="btn-ghost btn-sm" />
            </div>
        </div>
    </x-mary-card>

    {{-- Bulk action bar --}}
    @if(count($selectedItems) > 0)
    <div class="flex items-center gap-2 p-3 bg-warning/10 border border-warning/20 rounded-xl">
        <span class="text-sm font-medium">{{ count($selectedItems) }} selected</span>
        <x-mary-button
            label="Export CSV"
            icon="o-arrow-down-tray"
            wire:click="bulkExport"
            class="btn-ghost btn-sm" />
        <x-mary-button
            label="Delete"
            icon="o-trash"
            wire:click="bulkDelete"
            wire:confirm="Delete {{ count($selectedItems) }} used voucher(s)? This cannot be undone."
            class="btn-error btn-sm" />
    </div>
    @endif

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-sm" />
                        </th>
                        <th>Voucher</th>
                        <th>Status</th>
                        <th class="hidden lg:table-cell">Session</th>
                        <th>Data</th>
                        <th>Duration</th>
                        <th class="hidden md:table-cell">Dates</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->vouchers as $voucher)
                        @php
                            $status   = $this->getVoucherStatus($voucher);
                            $dataInfo = $this->getDataConsumed($voucher);
                            $duration = $this->getUsageDuration($voucher);

                            $statusBadge = match($status['color']) {
                                'success'   => 'badge-success',
                                'warning'   => 'badge-warning',
                                'secondary' => 'badge-ghost',
                                'info'      => 'badge-info',
                                default     => 'badge-ghost',
                            };
                        @endphp
                        <tr class="hover">
                            <td>
                                <input type="checkbox" wire:model.live="selectedItems"
                                    value="{{ $voucher->id }}" class="checkbox checkbox-sm" />
                            </td>
                            <td>
                                <div class="font-mono font-bold text-sm">{{ $voucher->code }}</div>
                                <div class="text-xs text-base-content/60">{{ $voucher->profile_name }}</div>
                                <x-partials.price-display :price="$voucher->price" />
                            </td>
                            <td>
                                <span class="badge badge-sm {{ $statusBadge }}">{{ $status['status'] }}</span>
                            </td>
                            <td class="hidden lg:table-cell text-xs text-base-content/60">
                                <div>{{ $this->isRandomMac($voucher->mac_address) ? 'Rnd ' : '' }}{{ $voucher->mac_address ?? 'N/A' }}</div>
                                <div>IP: {{ $voucher->ip_address ?? 'N/A' }}</div>
                                <div>Router: {{ $voucher->router_ip ?? 'N/A' }}</div>
                            </td>
                            <td style="min-width: 110px;">
                                @if($voucher->data_limit > 0)
                                    <span class="badge badge-info badge-sm">{{ $this->convertBytes($dataInfo['consumed']) }}</span>
                                    <div class="text-xs text-base-content/50 mt-0.5">of {{ $this->convertBytes($voucher->data_limit) }}</div>
                                    @if($dataInfo['percentage'] !== null)
                                        <progress class="progress progress-info w-full mt-1" value="{{ $dataInfo['percentage'] }}" max="100"></progress>
                                    @endif
                                @else
                                    <div class="text-xs text-base-content/50">
                                        ↓ {{ $this->convertBytes($voucher->session_download) }}<br>
                                        ↑ {{ $this->convertBytes($voucher->session_upload) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-ghost badge-sm">{{ $duration }}</span>
                                @if($voucher->uptime_limit > 0)
                                    <div class="text-xs text-base-content/50 mt-0.5">Left: {{ $this->convertSeconds($voucher->uptime_credit) }}</div>
                                @endif
                            </td>
                            <td class="hidden md:table-cell text-xs text-base-content/60">
                                <div>Gen: {{ \Illuminate\Support\Carbon::parse($voucher->generation_date)->format('M d, Y') }}</div>
                                <div>Used: {{ $voucher->used_date ? \Illuminate\Support\Carbon::parse($voucher->used_date)->format('M d, Y H:i') : 'N/A' }}</div>
                                <div>Exp: {{ $voucher->expire_date ? \Illuminate\Support\Carbon::parse($voucher->expire_date)->format('M d, Y H:i') : 'N/A' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-base-content/40">
                                <x-mary-icon name="o-clock" class="w-10 h-10 mx-auto mb-2" />
                                <p class="text-sm">No used voucher records found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex justify-between items-center mt-4 text-sm text-base-content/60">
            <span>Showing {{ $this->vouchers->firstItem() ?? 0 }}–{{ $this->vouchers->lastItem() ?? 0 }} of {{ $this->vouchers->total() }}</span>
            {{ $this->vouchers->links() }}
        </div>
    </x-mary-card>
</div>
