<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Resellers</h2>
            <p class="text-sm text-base-content/60">
                Manage voucher reseller accounts
                <span class="badge badge-primary badge-sm ml-1">{{ $this->resellers->total() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('client.reseller.add') }}" class="btn btn-primary btn-sm">
                <x-mary-icon name="o-plus" class="w-4 h-4" />
                Add Reseller
            </a>
        </div>
    </div>

    <x-mary-card shadow>
        {{-- Search --}}
        <div class="mb-4">
            <label class="input input-bordered flex items-center gap-2 max-w-sm">
                <x-mary-icon name="o-magnifying-glass" class="w-4 h-4 text-base-content/40" />
                <input type="text" wire:model.live.debounce.500ms="searchStr"
                    placeholder="Search resellers..." class="grow" />
            </label>
        </div>

        {{-- Bulk Action Bar --}}
        @if(count($selectedItems) > 0)
        <div class="flex items-center gap-2 p-3 bg-warning/10 border border-warning/20 rounded-xl mb-4">
            <x-mary-icon name="o-check" class="w-4 h-4 text-warning" />
            <span class="text-sm font-medium flex-1">
                <strong>{{ count($selectedItems) }}</strong> reseller(s) selected
            </span>
            <div class="flex items-center gap-2">
                <button class="btn btn-success btn-sm"
                    wire:confirm="Are you sure you want to enable {{ count($selectedItems) }} reseller(s)?"
                    wire:click="bulkEnable">
                    <x-mary-icon name="o-check" class="w-4 h-4" />
                    Enable
                </button>
                <button class="btn btn-warning btn-sm"
                    wire:confirm="Are you sure you want to suspend {{ count($selectedItems) }} reseller(s)?"
                    wire:click="bulkDisable">
                    <x-mary-icon name="o-no-symbol" class="w-4 h-4" />
                    Suspend
                </button>
                <button class="btn btn-error btn-sm"
                    wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} reseller(s) and ALL their vouchers?"
                    wire:click="bulkDelete">
                    <x-mary-icon name="o-trash" class="w-4 h-4" />
                    Delete
                </button>
            </div>
        </div>
        @endif

        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-3">
            @forelse ($this->resellers as $reseller)
                <div class="border rounded-xl overflow-hidden {{ $reseller->status == 'suspended' ? 'border-error/40' : 'border-base-300' }}">
                    <div class="flex items-center justify-between p-3 {{ $reseller->status == 'suspended' ? 'bg-error/5' : 'bg-base-200/50' }}">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model.live="selectedItems"
                                value="{{ $reseller->id }}" class="checkbox checkbox-sm">
                            <div>
                                <span class="font-mono text-xs text-base-content/50">ID: {{ $reseller->id }}</span>
                                <div class="font-semibold text-base-content text-sm">{{ $reseller->name }}</div>
                            </div>
                        </div>
                        @if($reseller->status == 'suspended')
                            <span class="badge badge-error badge-sm gap-1">
                                <x-mary-icon name="o-no-symbol" class="w-3 h-3" />
                                Suspended
                            </span>
                        @else
                            <span class="badge badge-success badge-sm gap-1">
                                <x-mary-icon name="o-check" class="w-3 h-3" />
                                {{ ucfirst($reseller->status) }}
                            </span>
                        @endif
                    </div>
                    <div class="p-3 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Vouchers</span>
                            <span class="text-base-content text-right text-xs">
                                Available: {{ $reseller->available_vouchers }}<br>
                                Active: {{ $reseller->active_vouchers }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Today / Yesterday</span>
                            <span class="text-base-content">
                                <span class="font-semibold">{{ number_format($reseller->earnToday, 2) }}</span>
                                / {{ number_format($reseller->earnYesterday, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Week / Month</span>
                            <span class="text-base-content">
                                {{ number_format($reseller->earnThisWeek, 2) }} / {{ number_format($reseller->earnThisMonth, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Last Month / Total</span>
                            <span class="text-base-content">
                                {{ number_format($reseller->earnLastMonth, 2) }} / <span class="font-bold">{{ number_format($reseller->total_sales, 2) }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2 p-3 bg-base-200/30 border-t border-base-300">
                        <a href="{{ route('client.reseller.edit', $reseller->id) }}"
                            class="btn btn-ghost btn-sm flex-1">
                            <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                            Edit
                        </a>
                        <button class="btn btn-error btn-sm flex-1"
                            wire:click="delete({{ $reseller->id }})"
                            wire:confirm.prompt="Are you sure?\nThis will delete all vouchers under this reseller\n\nType {{ $reseller->id }} to confirm|{{ $reseller->id }}">
                            <x-mary-icon name="o-trash" class="w-4 h-4" />
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                    <x-mary-icon name="o-user-group" class="w-12 h-12 mb-3" />
                    <p class="text-sm">No registered resellers</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-sm">
                        </th>
                        <th>ID</th>
                        <th>Details</th>
                        <th>Vouchers</th>
                        <th>Today / Yesterday</th>
                        <th>Week / Month</th>
                        <th>Last Month / Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->resellers as $reseller)
                        <tr class="hover {{ $reseller->status == 'suspended' ? 'bg-error/5' : '' }}">
                            <td>
                                <input type="checkbox" wire:model.live="selectedItems"
                                    value="{{ $reseller->id }}" class="checkbox checkbox-sm">
                            </td>
                            <td>
                                <span class="font-mono font-bold text-base-content">{{ $reseller->id }}</span>
                            </td>
                            <td>
                                <div class="text-sm font-medium text-base-content">{{ $reseller->name }}</div>
                                <div class="mt-0.5">
                                    @if($reseller->status == 'suspended')
                                        <span class="badge badge-error badge-sm gap-1">
                                            <x-mary-icon name="o-no-symbol" class="w-3 h-3" />
                                            Suspended
                                        </span>
                                    @else
                                        <span class="badge badge-success badge-sm gap-1">
                                            <x-mary-icon name="o-check" class="w-3 h-3" />
                                            {{ ucfirst($reseller->status) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-base-content/50">Available:</span> {{ $reseller->available_vouchers }}</div>
                                    <div><span class="text-base-content/50">Active:</span> {{ $reseller->active_vouchers }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-base-content/50">Today:</span> <span class="font-semibold">{{ number_format($reseller->earnToday, 2) }}</span></div>
                                    <div><span class="text-base-content/50">Yesterday:</span> {{ number_format($reseller->earnYesterday, 2) }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-base-content/50">Week:</span> {{ number_format($reseller->earnThisWeek, 2) }}</div>
                                    <div><span class="text-base-content/50">Month:</span> {{ number_format($reseller->earnThisMonth, 2) }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-base-content/50">Last Month:</span> {{ number_format($reseller->earnLastMonth, 2) }}</div>
                                    <div><span class="text-base-content/50">Total:</span> <span class="font-bold">{{ number_format($reseller->total_sales, 2) }}</span></div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('client.reseller.edit', $reseller->id) }}"
                                        class="btn btn-ghost btn-xs">
                                        <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                        Edit
                                    </a>
                                    <button class="btn btn-error btn-xs"
                                        wire:click="delete({{ $reseller->id }})"
                                        wire:confirm.prompt="Are you sure?\nThis will delete all vouchers under this reseller\n\nType {{ $reseller->id }} to confirm|{{ $reseller->id }}">
                                        <x-mary-icon name="o-trash" class="w-4 h-4" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                                    <x-mary-icon name="o-user-group" class="w-12 h-12 mb-3" />
                                    <p class="text-sm">No registered resellers</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-end mt-4">
            {{ $this->resellers->links() }}
        </div>
    </x-mary-card>
</div>
