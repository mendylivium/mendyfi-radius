<div class="row">
    <div class="col-12">
        <x-partials.flash />
    </div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header card-header-standard">
                <h6 class="card-title">
                    <i class="fas fa-users"></i>Resellers
                    <span class="badge badge-primary">{{ $this->resellers->total() }}</span>
                </h6>
                <div class="card-actions">
                    <a class="btn btn-action btn-action-primary" href="{{ route('client.reseller.add') }}">
                        <i class="fas fa-plus"></i>Add Reseller
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="search-wrapper mb-4">
                    <i class="fas fa-search"></i>
                    <input type="text" wire:model.live.debounce.500ms="searchStr" placeholder="Search resellers..."
                        class="form-control search-input" style="width: 100%; max-width: 400px;">
                </div>
                
                {{-- Bulk Action Bar --}}
                @if(count($selectedItems) > 0)
                <div class="bulk-action-bar">
                    <span class="selected-count"><strong>{{ count($selectedItems) }}</strong> reseller(s) selected</span>
                    <div class="btn-action-group">
                        <button class="btn btn-action" style="background: #1cc88a; color: white; border-color: #1cc88a;"
                            wire:confirm="Are you sure you want to enable {{ count($selectedItems) }} reseller(s)?"
                            wire:click="bulkEnable">
                            <i class="fas fa-check"></i>Enable
                        </button>
                        <button class="btn btn-action btn-action-disconnect"
                            wire:confirm="Are you sure you want to suspend {{ count($selectedItems) }} reseller(s)?"
                            wire:click="bulkDisable">
                            <i class="fas fa-ban"></i>Suspend
                        </button>
                        <button class="btn btn-action btn-action-delete"
                            wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} reseller(s) and ALL their vouchers?"
                            wire:click="bulkDelete">
                            <i class="fas fa-trash"></i>Delete
                        </button>
                    </div>
                </div>
                @endif
                {{-- Mobile Card View --}}
                <div class="mobile-card-view">
                    @forelse ($this->resellers as $reseller)
                        <div class="voucher-card-mobile {{ $reseller->status == 'suspended' ? 'border-danger' : '' }}">
                            <div class="card-header-mobile">
                                <div class="d-flex align-items-center">
                                    <input type="checkbox" wire:model.live="selectedItems"
                                        value="{{ $reseller->id }}" class="checkbox-standard mr-3">
                                    <div>
                                        <span class="voucher-code">ID: {{ $reseller->id }}</span>
                                        <div class="small text-muted">{{ $reseller->name }}</div>
                                    </div>
                                </div>
                                @if($reseller->status == 'suspended')
                                    <span class="badge status-suspended">
                                        <i class="fas fa-ban mr-1"></i>Suspended
                                    </span>
                                @else
                                    <span class="badge status-enabled">
                                        <i class="fas fa-check mr-1"></i>{{ ucfirst($reseller->status) }}
                                    </span>
                                @endif
                            </div>
                            <div class="card-body-mobile">
                                <div class="data-item">
                                    <span class="data-label">Vouchers</span>
                                    <span class="data-value">
                                        Available: {{ $reseller->available_vouchers }}<br>
                                        Active: {{ $reseller->active_vouchers }}
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Today/Yesterday</span>
                                    <span class="data-value">
                                        <span class="price-value">{{ number_format($reseller->earnToday, 2) }}</span> / {{ number_format($reseller->earnYesterday, 2) }}
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Week/Month</span>
                                    <span class="data-value">
                                        {{ number_format($reseller->earnThisWeek, 2) }} / {{ number_format($reseller->earnThisMonth, 2) }}
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Last/Total</span>
                                    <span class="data-value">
                                        {{ number_format($reseller->earnLastMonth, 2) }} / <span class="price-value font-weight-bold">{{ number_format($reseller->total_sales, 2) }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="card-actions-mobile">
                                <a href="{{ route('client.reseller.edit', $reseller->id) }}"
                                    class="btn btn-action btn-action-edit">
                                    <i class="fas fa-edit"></i>Edit
                                </a>
                                <button class="btn btn-action btn-action-delete"
                                    wire:click="delete({{ $reseller->id }})"
                                    wire:confirm.prompt="Are you sure?\nThis will delete all vouchers under this reseller\n\nType {{ $reseller->id }} to confirm|{{ $reseller->id }}">
                                    <i class="fas fa-trash"></i>Delete
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="table-empty-state">
                            <i class="fas fa-users"></i>
                            <p>No registered resellers</p>
                        </div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="table-responsive desktop-table-view">
                    <table class="table table-standard table-striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" wire:model.live="selectAll" class="checkbox-standard">
                                </th>
                                <th>ID</th>
                                <th>Details</th>
                                <th>Vouchers</th>
                                <th class="hide-mobile">Today/Yesterday</th>
                                <th class="hide-mobile">Week/Month</th>
                                <th class="hide-mobile">Last Month/Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->resellers as $reseller)
                                <tr class="{{ $reseller->status == 'suspended' ? 'table-danger' : '' }}">
                                    <td>
                                        <input type="checkbox" wire:model.live="selectedItems"
                                            value="{{ $reseller->id }}" class="checkbox-standard">
                                    </td>
                                    <td><span class="info-value font-weight-bold">{{ $reseller->id }}</span></td>
                                    <td>
                                        <span class="info-label">Name:</span> <span class="info-value">{{ $reseller->name }}</span><br />
                                        <span class="info-label">Status:</span> 
                                        @if($reseller->status == 'suspended')
                                            <span class="badge status-suspended">
                                                <i class="fas fa-ban mr-1"></i>Suspended
                                            </span>
                                        @else
                                            <span class="badge status-enabled">
                                                <i class="fas fa-check mr-1"></i>{{ ucfirst($reseller->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="info-label">Available:</span> <span class="info-value">{{ $reseller->available_vouchers }}</span><br />
                                        <span class="info-label">Active:</span> <span class="info-value">{{ $reseller->active_vouchers }}</span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span class="info-label">Today:</span> <span class="price-value">{{ number_format($reseller->earnToday, 2) }}</span><br />
                                        <span class="info-label">Yesterday:</span> <span class="info-value">{{ number_format($reseller->earnYesterday, 2) }}</span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span class="info-label">This Week:</span> <span class="info-value">{{ number_format($reseller->earnThisWeek, 2) }}</span><br />
                                        <span class="info-label">This Month:</span> <span class="info-value">{{ number_format($reseller->earnThisMonth, 2) }}</span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span class="info-label">Last Month:</span> <span class="info-value">{{ number_format($reseller->earnLastMonth, 2) }}</span><br />
                                        <span class="info-label">Total:</span> <span class="price-value font-weight-bold">{{ number_format($reseller->total_sales, 2) }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <a href="{{ route('client.reseller.edit', $reseller->id) }}"
                                                class="btn btn-action btn-action-edit">
                                                <i class="fas fa-edit"></i>Edit
                                            </a>
                                            <button class="btn btn-action btn-action-delete"
                                                wire:click="delete({{ $reseller->id }})"
                                                wire:confirm.prompt="Are you sure?\nThis will delete all vouchers under this reseller\n\nType {{ $reseller->id }} to confirm|{{ $reseller->id }}">
                                                <i class="fas fa-trash"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="table-empty-state">
                                        <i class="fas fa-users"></i>
                                        <p>No registered resellers</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    {{ $this->resellers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
