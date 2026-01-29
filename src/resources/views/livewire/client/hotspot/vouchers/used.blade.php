<div class="row">
    <div class="col-12">
        <x-partials.flash />
    </div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header card-header-standard">
                <h6 class="card-title">
                    <i class="fas fa-history"></i>Used Vouchers
                    <span class="badge badge-secondary">{{ $this->vouchers->total() }}</span>
                </h6>
                <div class="card-actions">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control search-input"
                            placeholder="Search vouchers...">
                    </div>
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

                    <div class="col-md-2 d-flex align-items-end">
                        <button wire:click="clearFilters" class="btn btn-action btn-action-view">
                            <i class="fas fa-times"></i>Clear
                        </button>
                    </div>
                </div>

                {{-- Usage Stats Cards --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-gradient-info text-white">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs text-white-50">Total Used</div>
                                        <div class="h5 mb-0">{{ number_format($this->usageStats->total_used ?? 0) }} vouchers</div>
                                    </div>
                                    <i class="fas fa-ticket-alt fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-gradient-success text-white">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs text-white-50">Total Download</div>
                                        <div class="h5 mb-0">{{ $this->convertBytes($this->usageStats->total_download ?? 0) }}</div>
                                    </div>
                                    <i class="fas fa-download fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-gradient-warning text-white">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs text-white-50">Total Upload</div>
                                        <div class="h5 mb-0">{{ $this->convertBytes($this->usageStats->total_upload ?? 0) }}</div>
                                    </div>
                                    <i class="fas fa-upload fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bulk Action Bar --}}
                @if(count($selectedItems) > 0)
                <div class="bulk-action-bar">
                    <span class="selected-count"><strong>{{ count($selectedItems) }}</strong> voucher(s) selected</span>
                    <div class="btn-action-group">
                        <button class="btn btn-action btn-action-export" wire:click="bulkExport">
                            <i class="fas fa-download"></i>Export Selected
                        </button>
                        <button class="btn btn-action btn-action-delete"
                            wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} used voucher(s)?"
                            wire:click="bulkDelete">
                            <i class="fas fa-trash"></i>Delete Selected
                        </button>
                    </div>
                </div>
                @endif
                {{-- Mobile Card View --}}
                <div class="mobile-card-view">
                    @forelse ($this->vouchers as $voucher)
                        @php
                            $status = $this->getVoucherStatus($voucher);
                            $dataInfo = $this->getDataConsumed($voucher);
                            $duration = $this->getUsageDuration($voucher);
                        @endphp
                        <div class="voucher-card-mobile">
                            <div class="card-header-mobile">
                                <div class="d-flex align-items-center">
                                    <input type="checkbox" wire:model.live="selectedItems"
                                        value="{{ $voucher->id }}" class="checkbox-standard mr-3">
                                    <span class="voucher-code">{{ $voucher->code }}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-{{ $status['color'] }} mr-2">
                                        <i class="fas fa-{{ $status['icon'] }} mr-1"></i>{{ $status['status'] }}
                                    </span>
                                    <x-partials.profile-badge 
                                        :name="$voucher->profile_name" 
                                        :uptime-limit="$voucher->uptime_limit ?? 0"
                                        :data-limit="$voucher->data_limit ?? 0" />
                                </div>
                            </div>
                            <div class="card-body-mobile">
                                <div class="data-item">
                                    <span class="data-label">Session Info</span>
                                    <span class="data-value">
                                        <small>
                                            {{ $this->isRandomMac($voucher->mac_address) ? 'Random ' : '' }}MAC: {{ $voucher->mac_address ?? 'N/A' }}<br>
                                            IP: {{ $voucher->ip_address ?? 'N/A' }}<br>
                                            Router: {{ $voucher->router_ip ?? 'N/A' }}{{ $voucher->server_name ? " - {$voucher->server_name}" : '' }}
                                        </small>
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Data Consumed</span>
                                    <span class="data-value">
                                        @if($voucher->data_limit > 0)
                                            <span class="badge badge-info">
                                                {{ $this->convertBytes($dataInfo['consumed']) }}
                                            </span>
                                            <br><small>{{ round($dataInfo['percentage'] ?? 0) }}% of {{ $this->convertBytes($voucher->data_limit) }}</small>
                                        @else
                                            <small>
                                                ↓ {{ $this->convertBytes($voucher->session_download) }}<br>
                                                ↑ {{ $this->convertBytes($voucher->session_upload) }}
                                            </small>
                                        @endif
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Duration</span>
                                    <span class="data-value">
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-hourglass-half mr-1"></i>{{ $duration }}
                                        </span>
                                        @if($voucher->uptime_limit > 0)
                                        <br><small>Time left: {{ $this->convertSeconds($voucher->uptime_credit) }}</small>
                                        @endif
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Price</span>
                                    <span class="data-value">
                                        <x-partials.price-display :price="$voucher->price" />
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Dates</span>
                                    <span class="data-value">
                                        <small>
                                            Generated: {{ Illuminate\Support\Carbon::parse($voucher->generation_date)->format('M d, Y') }}<br>
                                            Used: {{ $voucher->used_date ? Illuminate\Support\Carbon::parse($voucher->used_date)->format('M d, Y h:i A') : 'N/A' }}<br>
                                            Expired: {{ $voucher->expire_date ? Illuminate\Support\Carbon::parse($voucher->expire_date)->format('M d, Y h:i A') : 'N/A' }}
                                        </small>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="table-empty-state">
                            <i class="fas fa-history"></i>
                            <p>No used voucher records found</p>
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
                                <th>Voucher Info</th>
                                <th>Status</th>
                                <th class="hide-mobile">Session Info</th>
                                <th>Data Consumed</th>
                                <th>Duration</th>
                                <th class="hide-mobile">Dates</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->vouchers as $voucher)
                                @php
                                    $status = $this->getVoucherStatus($voucher);
                                    $dataInfo = $this->getDataConsumed($voucher);
                                    $duration = $this->getUsageDuration($voucher);
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" wire:model.live="selectedItems"
                                            value="{{ $voucher->id }}" class="checkbox-standard">
                                    </td>
                                    <td>
                                        <span class="info-label">Code:</span> <span class="info-value font-weight-bold">{{ $voucher->code }}</span><br />
                                        <span class="info-label">Profile:</span> 
                                        <x-partials.profile-badge 
                                            :name="$voucher->profile_name" 
                                            :uptime-limit="$voucher->uptime_limit ?? 0"
                                            :data-limit="$voucher->data_limit ?? 0" /><br />
                                        <span class="info-label">Price:</span> <x-partials.price-display :price="$voucher->price" />
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $status['color'] }}">
                                            <i class="fas fa-{{ $status['icon'] }} mr-1"></i>{{ $status['status'] }}
                                        </span>
                                    </td>
                                    <td class="hide-mobile">
                                        <small>
                                            <span class="info-label">{{ $this->isRandomMac($voucher->mac_address) ? 'Random ' : '' }}MAC:</span> {{ $voucher->mac_address ?? 'N/A' }}<br>
                                            <span class="info-label">IP:</span> {{ $voucher->ip_address ?? 'N/A' }}<br>
                                            <span class="info-label">Router:</span> {{ $voucher->router_ip ?? 'N/A' }}{{ $voucher->server_name ? " - {$voucher->server_name}" : '' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($voucher->data_limit > 0)
                                            <div class="mb-1">
                                                <span class="badge badge-info">
                                                    {{ $this->convertBytes($dataInfo['consumed']) }}
                                                </span>
                                                <small class="info-value-muted">of {{ $this->convertBytes($voucher->data_limit) }}</small>
                                            </div>
                                            @if($dataInfo['percentage'] !== null)
                                            <div class="progress" style="height: 6px; width: 100px;">
                                                <div class="progress-bar bg-info" 
                                                    role="progressbar" 
                                                    style="width: {{ $dataInfo['percentage'] }}%">
                                                </div>
                                            </div>
                                            <small class="info-value-muted">{{ round($dataInfo['percentage']) }}% used</small>
                                            @endif
                                        @else
                                            <small class="info-value-muted">
                                                <span class="info-label">↓</span> {{ $this->convertBytes($voucher->session_download) }}<br>
                                                <span class="info-label">↑</span> {{ $this->convertBytes($voucher->session_upload) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-hourglass-half mr-1"></i>{{ $duration }}
                                        </span>
                                        @if($voucher->uptime_limit > 0)
                                        <br><small class="info-value-muted">
                                            Time left: {{ $this->convertSeconds($voucher->uptime_credit) }}
                                        </small>
                                        @endif
                                    </td>
                                    <td class="hide-mobile">
                                        <small>
                                            <span class="info-label">Generated:</span>
                                            {{ Illuminate\Support\Carbon::parse($voucher->generation_date)->format('M d, Y') }}<br>
                                            <span class="info-label">Used:</span>
                                            {{ $voucher->used_date ? Illuminate\Support\Carbon::parse($voucher->used_date)->format('M d, Y h:i A') : 'N/A' }}<br>
                                            <span class="info-label">Expired:</span>
                                            {{ $voucher->expire_date ? Illuminate\Support\Carbon::parse($voucher->expire_date)->format('M d, Y h:i A') : 'N/A' }}
                                        </small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="table-empty-state">
                                        <i class="fas fa-history"></i>
                                        <p>No used voucher records found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="info-value-muted small">
                        Showing {{ $this->vouchers->firstItem() ?? 0 }} to {{ $this->vouchers->lastItem() ?? 0 }} 
                        of {{ $this->vouchers->total() }} records
                    </div>
                    <div>
                        {{ $this->vouchers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
