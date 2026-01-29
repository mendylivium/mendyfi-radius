<div class="row">
    <div class="col-12">
        <x-partials.flash />
    </div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header card-header-standard">
                <h6 class="card-title">
                    <i class="fas fa-chart-line"></i>Hotspot Sales Report
                    <span class="badge badge-success">{{ $this->sales->total() }} records</span>
                </h6>
                <div class="card-actions">
                    <button class="btn btn-action btn-action-export" wire:click="exportCsv">
                        <i class="fas fa-download"></i>Export CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                {{-- Filters Section --}}
                <div class="row mb-4 filter-row">
                    <div class="col-md-2">
                        <label class="info-label">Date Range</label>
                        <select wire:model.live="dateRange" class="form-control form-control-sm">
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
                    <div class="col-md-2">
                        <label class="info-label">From</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="info-label">To</label>
                        <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                    </div>
                    @endif

                    <div class="col-md-2">
                        <label class="info-label">Profile</label>
                        <select wire:model.live="profileFilter" class="form-control form-control-sm">
                            <option value="">All Profiles</option>
                            @foreach($this->profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="info-label">Reseller</label>
                        <select wire:model.live="resellerFilter" class="form-control form-control-sm">
                            <option value="">All Resellers</option>
                            @foreach($this->resellers as $reseller)
                                <option value="{{ $reseller->id }}">{{ $reseller->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="info-label">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="search" 
                            class="form-control form-control-sm" 
                            placeholder="Code, MAC, IP...">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button wire:click="clearFilters" class="btn btn-action btn-action-view">
                            <i class="fas fa-times"></i>Clear
                        </button>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-gradient-primary text-white">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs text-white-50">Total Transactions</div>
                                        <div class="h4 mb-0">{{ number_format($this->salesTotals->total_count ?? 0) }}</div>
                                    </div>
                                    <i class="fas fa-receipt fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-gradient-success text-white">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs text-white-50">Total Revenue</div>
                                        <div class="h4 mb-0">{{ number_format($this->salesTotals->total_amount ?? 0, 2) }}</div>
                                    </div>
                                    <i class="fas fa-money-bill-wave fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="mobile-card-view">
                    @forelse ($this->sales as $sale)
                        <div class="voucher-card-mobile">
                            <div class="card-header-mobile">
                                <span class="voucher-code">{{ $sale->code }}</span>
                                <div class="d-flex align-items-center">
                                    <x-partials.profile-badge 
                                        :name="$sale->profile_name ?? 'N/A'" 
                                        :uptime-limit="$sale->uptime_limit ?? 0"
                                        :data-limit="$sale->data_limit ?? 0" />
                                </div>
                            </div>
                            <div class="card-body-mobile">
                                <div class="data-item">
                                    <span class="data-label">Reseller</span>
                                    <span class="data-value">
                                        @if($sale->reseller_name)
                                            <span class="badge badge-info">{{ $sale->reseller_name }}</span>
                                        @else
                                            <span class="info-value-muted">Direct</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Session Info</span>
                                    <span class="data-value">
                                        <small>
                                            {{ $this->isRandomMac($sale->mac_address) ? 'Random ' : '' }}MAC: {{ $sale->mac_address ?? 'N/A' }}<br>
                                            IP: {{ $sale->ip_address ?? 'N/A' }}<br>
                                            Router: {{ $sale->router_ip ?? 'N/A' }}{{ $sale->server_name ? " - {$sale->server_name}" : '' }}
                                        </small>
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Price</span>
                                    <span class="data-value">
                                        <x-partials.price-display :price="$sale->amount" />
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Date</span>
                                    <span class="data-value" style="font-size: 0.8rem;">
                                        {{ Illuminate\Support\Carbon::parse($sale->transact_date)->format('M d, Y h:i A') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="table-empty-state">
                            <i class="fas fa-chart-line"></i>
                            <p>No sales records found</p>
                        </div>
                    @endforelse
                    
                    {{-- Mobile Page Total --}}
                    @if($this->sales->count() > 0)
                    <div class="card mt-3" style="background: #f8f9fc; border: 1px solid #e3e6f0;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="info-label">Page Total:</strong>
                                <span class="price-value font-weight-bold">{{ number_format($this->sales->sum('amount'), 2) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Desktop Table View --}}
                <div class="table-responsive desktop-table-view">
                    <table class="table table-standard table-striped">
                        <thead>
                            <tr>
                                <th>Voucher Code</th>
                                <th>Profile</th>
                                <th>Reseller</th>
                                <th class="hide-mobile">Session</th>
                                <th>Price</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->sales as $sale)
                                <tr>
                                    <td>
                                        <span class="info-value font-weight-bold">{{ $sale->code }}</span>
                                    </td>
                                    <td>
                                        <x-partials.profile-badge 
                                            :name="$sale->profile_name ?? 'N/A'" 
                                            :uptime-limit="$sale->uptime_limit ?? 0"
                                            :data-limit="$sale->data_limit ?? 0" />
                                    </td>
                                    <td>
                                        @if($sale->reseller_name)
                                            <span class="badge badge-info">{{ $sale->reseller_name }}</span>
                                        @else
                                            <span class="info-value-muted">Direct</span>
                                        @endif
                                    </td>
                                    <td class="hide-mobile">
                                        <small>
                                            <span class="info-label">{{ $this->isRandomMac($sale->mac_address) ? 'Random ' : '' }}MAC:</span> {{ $sale->mac_address ?? 'N/A' }}<br>
                                            <span class="info-label">IP:</span> {{ $sale->ip_address ?? 'N/A' }}<br>
                                            <span class="info-label">Router:</span> {{ $sale->router_ip ?? 'N/A' }}{{ $sale->server_name ? " - {$sale->server_name}" : '' }}
                                        </small>
                                    </td>
                                    <td class="text-right">
                                        <x-partials.price-display :price="$sale->amount" />
                                    </td>
                                    <td>
                                        {{ Illuminate\Support\Carbon::parse($sale->transact_date)->format('M d, Y h:i A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="table-empty-state">
                                        <i class="fas fa-chart-line"></i>
                                        <p>No sales records found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($this->sales->count() > 0)
                        <tfoot style="background: #f8f9fc;">
                            <tr>
                                <td colspan="4" class="text-right font-weight-bold info-label">Page Total:</td>
                                <td class="text-right"><span class="price-value font-weight-bold">{{ number_format($this->sales->sum('amount'), 2) }}</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="info-value-muted small">
                        Showing {{ $this->sales->firstItem() ?? 0 }} to {{ $this->sales->lastItem() ?? 0 }} 
                        of {{ $this->sales->total() }} entries
                    </div>
                    <div>
                        {{ $this->sales->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
