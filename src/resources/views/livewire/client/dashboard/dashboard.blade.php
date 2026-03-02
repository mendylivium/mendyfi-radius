<div class="space-y-6" wire:poll.30s>

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-base-content">Dashboard</h2>
            <p class="text-sm text-base-content/60 mt-0.5">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('client.voucher.generate') }}"
               class="btn btn-primary btn-sm gap-1.5">
                <x-mary-icon name="o-plus" class="w-4 h-4" />
                Generate Vouchers
            </a>
            <span class="text-xs text-base-content/40 hidden sm:block">
                Auto-refreshes every 30s &bull; {{ now()->format('H:i:s') }}
            </span>
        </div>
    </div>

    {{-- Low stock alert --}}
    @if($this->lowStockProfiles->count() > 0)
        <div class="alert alert-warning shadow-sm" x-data="{ expanded: false }">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 shrink-0" />
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm">
                    {{ $this->lowStockProfiles->count() }} profile{{ $this->lowStockProfiles->count() > 1 ? 's' : '' }} low on stock
                </p>
                <p class="text-xs mt-0.5" x-show="expanded" x-collapse>
                    @foreach($this->lowStockProfiles as $profile)
                        <span class="font-medium">{{ $profile->name }}</span> ({{ $profile->stock }}){{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            </div>
            <button class="btn btn-ghost btn-xs"
                    @click="expanded = !expanded"
                    x-text="expanded ? 'Hide' : 'Details'">
            </button>
        </div>
    @endif

    {{-- Stats row: 2 cols on mobile, 4 on lg+ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">

        <x-mary-stat
            title="Active"
            description="Sessions"
            :value="$this->user->active_vouchers"
            icon="o-wifi"
            class="bg-base-100 shadow-sm"
            color="text-primary"
            tooltip="Vouchers currently connected" />

        <x-mary-stat
            title="Stock"
            description="Available"
            :value="$this->user->available_vouchers"
            icon="o-ticket"
            class="bg-base-100 shadow-sm"
            color="text-success"
            tooltip="Unused vouchers ready to sell" />

        <x-mary-stat
            title="Today"
            description="Generated"
            :value="$this->vouchersGeneratedToday"
            icon="o-plus-circle"
            class="bg-base-100 shadow-sm"
            color="text-info"
            tooltip="Vouchers generated today" />

        <x-mary-stat
            title="Today"
            description="Revenue"
            :value="'GYD ' . number_format($this->sales->earnToday, 2)"
            icon="o-banknotes"
            class="bg-base-100 shadow-sm"
            color="text-warning"
            tooltip="Revenue collected today" />

    </div>

    {{-- Secondary stats: earnings comparison --}}
    @php
        $todayChange  = $this->calculatePercentChange($this->sales->earnToday, $this->sales->earnYesterday);
        $weekChange   = $this->calculatePercentChange($this->sales->earnThisWeek, $this->sales->earnLastWeek);
        $monthChange  = $this->calculatePercentChange($this->sales->earnThisMonth, $this->sales->earnLastMonth);
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-4">

        <div class="bg-base-100 rounded-xl shadow-sm p-4 border border-base-300">
            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Yesterday</p>
            <p class="text-2xl font-bold text-base-content mt-1">
                {{ number_format($this->sales->earnYesterday, 2) }}
            </p>
            <p class="text-xs text-base-content/40 mt-0.5">GYD</p>
            @if($todayChange != 0)
                <p class="text-xs mt-2 {{ $todayChange >= 0 ? 'text-success' : 'text-error' }}">
                    <x-mary-icon name="{{ $todayChange >= 0 ? 'o-arrow-trending-up' : 'o-arrow-trending-down' }}" class="w-3.5 h-3.5 inline" />
                    {{ abs($todayChange) }}% vs yesterday
                </p>
            @endif
        </div>

        <div class="bg-base-100 rounded-xl shadow-sm p-4 border border-base-300">
            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">This Week</p>
            <p class="text-2xl font-bold text-base-content mt-1">
                {{ number_format($this->sales->earnThisWeek, 2) }}
            </p>
            <p class="text-xs text-base-content/40 mt-0.5">GYD</p>
            @if($weekChange != 0)
                <p class="text-xs mt-2 {{ $weekChange >= 0 ? 'text-success' : 'text-error' }}">
                    <x-mary-icon name="{{ $weekChange >= 0 ? 'o-arrow-trending-up' : 'o-arrow-trending-down' }}" class="w-3.5 h-3.5 inline" />
                    {{ abs($weekChange) }}% vs last week
                </p>
            @endif
        </div>

        <div class="bg-base-100 rounded-xl shadow-sm p-4 border border-base-300">
            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">This Month</p>
            <p class="text-2xl font-bold text-base-content mt-1">
                {{ number_format($this->sales->earnThisMonth, 2) }}
            </p>
            <p class="text-xs text-base-content/40 mt-0.5">GYD</p>
            @if($monthChange != 0)
                <p class="text-xs mt-2 {{ $monthChange >= 0 ? 'text-success' : 'text-error' }}">
                    <x-mary-icon name="{{ $monthChange >= 0 ? 'o-arrow-trending-up' : 'o-arrow-trending-down' }}" class="w-3.5 h-3.5 inline" />
                    {{ abs($monthChange) }}% vs last month
                </p>
            @endif
        </div>

    </div>

    {{-- Main content: stock table + recent sales --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        {{-- Voucher stock by profile (2/5 width on lg) --}}
        <div class="lg:col-span-2">
            <x-mary-card title="Voucher Stock" shadow separator>
                <x-slot:menu>
                    <a href="{{ route('client.voucher.generate') }}"
                       class="btn btn-ghost btn-xs gap-1">
                        <x-mary-icon name="o-plus" class="w-3.5 h-3.5" />
                        Generate
                    </a>
                </x-slot:menu>

                @if($this->vouchers->count())
                    <x-mary-table :headers="$stockHeaders" :rows="$this->vouchers->getCollection()" striped>
                        @scope('cell_stock', $voucher)
                            <div class="flex items-center justify-end gap-2">
                                <span class="font-mono font-semibold">{{ $voucher->stock }}</span>
                                @if($voucher->stock == 0)
                                    <x-mary-badge value="Out" class="badge-error badge-sm" />
                                @elseif($voucher->stock < $this->lowStockThreshold)
                                    <x-mary-badge value="Low" class="badge-warning badge-sm" />
                                @else
                                    <x-mary-badge value="OK" class="badge-success badge-sm" />
                                @endif
                            </div>
                        @endscope
                    </x-mary-table>

                    {{-- Pagination --}}
                    @if($this->vouchers->hasPages())
                        <div class="flex justify-end mt-3 text-xs">
                            {{ $this->vouchers->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-10 text-base-content/40">
                        <x-mary-icon name="o-inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">No profiles yet</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

        {{-- Recent sales (3/5 width on lg) --}}
        <div class="lg:col-span-3">
            <x-mary-card title="Recent Sales" shadow separator>
                @if($this->recentSales->count())
                    <x-mary-table :headers="$salesHeaders" :rows="$this->recentSales" striped>
                        @scope('cell_amount', $sale)
                            <span class="font-mono font-semibold">{{ number_format($sale->amount, 2) }}</span>
                        @endscope
                        @scope('cell_transact_date', $sale)
                            <span class="text-xs text-base-content/60">
                                {{ \Illuminate\Support\Carbon::parse($sale->transact_date)->format('M d, H:i') }}
                            </span>
                        @endscope
                    </x-mary-table>
                @else
                    <div class="text-center py-10 text-base-content/40">
                        <x-mary-icon name="o-banknotes" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">No sales recorded yet</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

    </div>

</div>
