<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Sales Report</h2>
            <p class="text-sm text-base-content/60">Hotspot transaction history
                <span class="badge badge-primary badge-sm ml-1">{{ $this->sales->total() }} records</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn btn-outline btn-sm gap-1.5" wire:click="exportCsv">
                <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4" />
                <span class="hidden sm:inline">Export CSV</span>
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <x-mary-card shadow>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">

            <div class="flex flex-col gap-1">
                <label class="text-xs text-base-content/60 font-medium">Date Range</label>
                <select wire:model.live="dateRange" class="select select-bordered select-sm w-full">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="last_week">Last Week</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            @if($dateRange === 'custom')
            <div class="flex flex-col gap-1">
                <label class="text-xs text-base-content/60 font-medium">From</label>
                <input type="date" wire:model.live="dateFrom" class="input input-bordered input-sm w-full">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-base-content/60 font-medium">To</label>
                <input type="date" wire:model.live="dateTo" class="input input-bordered input-sm w-full">
            </div>
            @endif

            <div class="flex flex-col gap-1">
                <label class="text-xs text-base-content/60 font-medium">Profile</label>
                <select wire:model.live="profileFilter" class="select select-bordered select-sm w-full">
                    <option value="">All Profiles</option>
                    @foreach($this->profiles as $profile)
                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-base-content/60 font-medium">Reseller</label>
                <select wire:model.live="resellerFilter" class="select select-bordered select-sm w-full">
                    <option value="">All Resellers</option>
                    @foreach($this->resellers as $reseller)
                        <option value="{{ $reseller->id }}">{{ $reseller->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs text-base-content/60 font-medium">Search</label>
                <div class="relative">
                    <x-mary-icon name="o-magnifying-glass" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40 pointer-events-none" />
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="input input-bordered input-sm w-full pl-8"
                        placeholder="Code, MAC, IP...">
                </div>
            </div>

            <div class="flex items-end">
                <button wire:click="clearFilters" class="btn btn-ghost btn-sm gap-1.5 w-full">
                    <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                    Clear
                </button>
            </div>

        </div>
    </x-mary-card>

    {{-- Summary stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-mary-stat
            title="Total Transactions"
            :value="number_format($this->salesTotals->total_count ?? 0)"
            icon="o-receipt-percent"
            class="bg-base-100 shadow-sm"
            color="text-primary" />

        <x-mary-stat
            title="Total Revenue"
            :value="number_format($this->salesTotals->total_amount ?? 0, 2)"
            icon="o-banknotes"
            class="bg-base-100 shadow-sm"
            color="text-success" />
    </div>

    <x-mary-card shadow>
        @if($this->sales->count() > 0)

        {{-- Mobile card view --}}
        <div class="md:hidden space-y-3">
            @forelse ($this->sales as $sale)
                <div class="border border-base-300 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-base-200/50">
                        <span class="font-mono font-bold text-sm">{{ $sale->code }}</span>
                        <x-partials.profile-badge
                            :name="$sale->profile_name ?? 'N/A'"
                            :uptime-limit="$sale->uptime_limit ?? 0"
                            :data-limit="$sale->data_limit ?? 0" />
                    </div>
                    <div class="divide-y divide-base-200 px-4">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-xs text-base-content/60">Reseller</span>
                            <span class="text-sm">
                                @if($sale->reseller_name)
                                    <span class="badge badge-info badge-sm">{{ $sale->reseller_name }}</span>
                                @else
                                    <span class="text-base-content/40 text-xs">Direct</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-start py-2">
                            <span class="text-xs text-base-content/60">Session</span>
                            <span class="text-xs text-right leading-relaxed">
                                {{ $this->isRandomMac($sale->mac_address) ? 'Random ' : '' }}MAC: {{ $sale->mac_address ?? 'N/A' }}<br>
                                IP: {{ $sale->ip_address ?? 'N/A' }}<br>
                                Router: {{ $sale->router_ip ?? 'N/A' }}{{ $sale->server_name ? " - {$sale->server_name}" : '' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-xs text-base-content/60">Price</span>
                            <span class="font-mono font-semibold text-sm">
                                <x-partials.price-display :price="$sale->amount" />
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-xs text-base-content/60">Date</span>
                            <span class="text-xs">
                                {{ Illuminate\Support\Carbon::parse($sale->transact_date)->format('M d, Y h:i A') }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                    <x-mary-icon name="o-chart-bar" class="w-12 h-12 mb-3" />
                    <p class="text-sm">No sales records found</p>
                </div>
            @endforelse

            {{-- Mobile page total --}}
            <div class="flex justify-between items-center px-4 py-3 bg-base-200/50 rounded-xl border border-base-300">
                <span class="text-xs font-medium text-base-content/60 uppercase tracking-wide">Page Total</span>
                <span class="font-mono font-semibold">{{ number_format($this->sales->sum('amount'), 2) }}</span>
            </div>
        </div>

        {{-- Desktop table view --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th>Voucher Code</th>
                        <th>Profile</th>
                        <th>Reseller</th>
                        <th>Session</th>
                        <th class="text-right">Price</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->sales as $sale)
                        <tr class="hover">
                            <td>
                                <span class="font-mono font-bold text-sm">{{ $sale->code }}</span>
                            </td>
                            <td>
                                <x-partials.profile-badge
                                    :name="$sale->profile_name ?? 'N/A'"
                                    :uptime-limit="$sale->uptime_limit ?? 0"
                                    :data-limit="$sale->data_limit ?? 0" />
                            </td>
                            <td>
                                @if($sale->reseller_name)
                                    <span class="badge badge-info badge-sm">{{ $sale->reseller_name }}</span>
                                @else
                                    <span class="text-base-content/40 text-xs">Direct</span>
                                @endif
                            </td>
                            <td class="text-xs text-base-content/70 leading-relaxed">
                                <div><span class="text-base-content/40">{{ $this->isRandomMac($sale->mac_address) ? 'Random ' : '' }}MAC:</span> {{ $sale->mac_address ?? 'N/A' }}</div>
                                <div><span class="text-base-content/40">IP:</span> {{ $sale->ip_address ?? 'N/A' }}</div>
                                <div><span class="text-base-content/40">Router:</span> {{ $sale->router_ip ?? 'N/A' }}{{ $sale->server_name ? " - {$sale->server_name}" : '' }}</div>
                            </td>
                            <td class="text-right">
                                <span class="font-mono font-semibold">
                                    <x-partials.price-display :price="$sale->amount" />
                                </span>
                            </td>
                            <td class="text-xs text-base-content/70">
                                {{ Illuminate\Support\Carbon::parse($sale->transact_date)->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                                    <x-mary-icon name="o-chart-bar" class="w-12 h-12 mb-3" />
                                    <p class="text-sm">No sales records found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-base-200/50">
                    <tr>
                        <td colspan="4" class="text-right text-xs font-medium text-base-content/60 uppercase tracking-wide">Page Total</td>
                        <td class="text-right">
                            <span class="font-mono font-semibold">{{ number_format($this->sales->sum('amount'), 2) }}</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex flex-col sm:flex-row justify-between items-center gap-2 mt-4 text-sm text-base-content/60">
            <span>Showing {{ $this->sales->firstItem() ?? 0 }}–{{ $this->sales->lastItem() ?? 0 }} of {{ $this->sales->total() }} entries</span>
            {{ $this->sales->links() }}
        </div>

        @else

        <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
            <x-mary-icon name="o-chart-bar" class="w-12 h-12 mb-3" />
            <p class="text-sm">No sales records found</p>
        </div>

        @endif
    </x-mary-card>

</div>
