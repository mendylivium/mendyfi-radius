<div class="row" wire:poll.{{ $autoRefresh ? '10s' : '999999s' }}="$refresh">
    <div class="col-12">
        <x-partials.flash />
    </div>
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header card-header-standard">
                <h6 class="card-title">
                    <i class="fas fa-wifi"></i>Active Hotspot Users
                    <span class="badge badge-primary">{{ $this->voucher->total() }}</span>
                </h6>
                <div class="card-actions">
                    <button wire:click="toggleAutoRefresh" 
                        class="btn btn-action {{ $autoRefresh ? 'btn-action-primary' : 'btn-action-view' }}"
                        title="{{ $autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF' }}">
                        <i class="fas fa-sync-alt {{ $autoRefresh ? 'fa-spin' : '' }}"></i>
                        {{ $autoRefresh ? 'Live' : 'Auto' }}
                    </button>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input wire:model.live="search" type="text" class="form-control search-input"
                            placeholder="Search active vouchers...">
                    </div>
                </div>
            </div>
            <div class="card-body">
                {{-- Bulk Action Bar --}}
                @if(count($selectedItems) > 0)
                <div class="bulk-action-bar">
                    <span class="selected-count"><strong>{{ count($selectedItems) }}</strong> voucher(s) selected</span>
                    <div class="btn-action-group">
                        <button class="btn btn-action btn-action-disconnect"
                            wire:confirm="Are you sure you want to disconnect {{ count($selectedItems) }} user(s)?"
                            wire:click="bulkDisconnect">
                            <i class="fas fa-ban"></i>Disconnect Selected
                        </button>
                        <button class="btn btn-action btn-action-delete"
                            wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} active voucher(s)?"
                            wire:click="bulkDelete">
                            <i class="fas fa-trash"></i>Delete Selected
                        </button>
                    </div>
                </div>
                @endif
                {{-- Mobile Card View --}}
                <div class="mobile-card-view">
                    @forelse ($this->voucher as $voucher)
                        @php
                            $timeRemaining = $this->getTimeRemaining($voucher);
                            $timePercentage = $this->getTimePercentage($voucher);
                            $dataPercentage = $this->getDataPercentage($voucher);
                            $timeColor = $this->getStatusColor($timePercentage);
                            $dataColor = $this->getStatusColor($dataPercentage);
                            
                            $worstPercentage = min($timePercentage ?? 100, $dataPercentage ?? 100);
                            $cardClass = '';
                            if ($worstPercentage <= 25) {
                                $cardClass = 'border-danger';
                            } elseif ($worstPercentage <= 50) {
                                $cardClass = 'border-warning';
                            }
                        @endphp
                        <div class="voucher-card-mobile {{ $cardClass }}">
                            <div class="card-header-mobile">
                                <div class="d-flex align-items-center">
                                    <input type="checkbox" wire:model.live="selectedItems"
                                        value="{{ $voucher->id }}" class="checkbox-standard mr-3">
                                    <span class="voucher-code">{{ $voucher->code }}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge status-active mr-2">Active</span>
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
                                    <span class="data-label">Time Remaining</span>
                                    <span class="data-value">
                                        @if($timePercentage !== null)
                                            <span class="badge badge-{{ $timeColor }}">
                                                <i class="fas fa-clock mr-1"></i>{{ $timeRemaining['text'] ?? 'N/A' }}
                                            </span>
                                            <br><small>{{ round($timePercentage) }}% remaining</small>
                                        @else
                                            <span class="badge badge-profile-unlimited">
                                                <i class="fas fa-infinity mr-1"></i>Unlimited
                                            </span>
                                        @endif
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Data Remaining</span>
                                    <span class="data-value">
                                        @if($voucher->data_limit > 0)
                                            <span class="badge badge-{{ $dataColor }}">
                                                <i class="fas fa-database mr-1"></i>{{ $this->convertBytes($voucher->data_credit) }}
                                            </span>
                                            <br><small>{{ round($dataPercentage ?? 0) }}% of {{ $this->convertBytes($voucher->data_limit) }}</small>
                                        @else
                                            <span class="badge badge-profile-unlimited">
                                                <i class="fas fa-infinity mr-1"></i>Unlimited
                                            </span>
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
                                    <span class="data-label">Transfer</span>
                                    <span class="data-value">
                                        <small>
                                            ↓ {{ $this->convertBytes($voucher->session_download) }}<br>
                                            ↑ {{ $this->convertBytes($voucher->session_upload) }}
                                        </small>
                                    </span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Dates</span>
                                    <span class="data-value">
                                        <small>
                                            Used: {{ $voucher->used_date ? Illuminate\Support\Carbon::parse($voucher->used_date)->format('M d, Y h:i A') : 'N/A' }}<br>
                                            Expires: {{ $voucher->expire_date ? Illuminate\Support\Carbon::parse($voucher->expire_date)->format('M d, Y h:i A') : 'N/A' }}
                                        </small>
                                    </span>
                                </div>
                            </div>
                            <div class="card-actions-mobile">
                                <button class="btn btn-action btn-action-disconnect"
                                    wire:confirm.prompt="Disconnect user?\n\nType {{ substr($voucher->id, -5) }} to confirm|{{ substr($voucher->id, -5) }}"
                                    wire:click="disconnect({{ $voucher->id }})">
                                    <i class="fas fa-ban"></i>Disconnect
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="table-empty-state">
                            <i class="fas fa-wifi"></i>
                            <p>No active vouchers/users found</p>
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
                                <th>Session</th>
                                <th>Time Remaining</th>
                                <th>Data Remaining</th>
                                <th class="hide-mobile">Dates</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->voucher as $voucher)
                                @php
                                    $timeRemaining = $this->getTimeRemaining($voucher);
                                    $timePercentage = $this->getTimePercentage($voucher);
                                    $dataPercentage = $this->getDataPercentage($voucher);
                                    $timeColor = $this->getStatusColor($timePercentage);
                                    $dataColor = $this->getStatusColor($dataPercentage);
                                    
                                    $worstPercentage = min($timePercentage ?? 100, $dataPercentage ?? 100);
                                    $rowClass = '';
                                    if ($worstPercentage <= 25) {
                                        $rowClass = 'table-danger';
                                    } elseif ($worstPercentage <= 50) {
                                        $rowClass = 'table-warning';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
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
                                    <td>
                                        <small>
                                            <span class="info-label">{{ $this->isRandomMac($voucher->mac_address) ? 'Random ' : '' }}MAC:</span> {{ $voucher->mac_address ?? 'N/A' }}<br>
                                            <span class="info-label">IP:</span> {{ $voucher->ip_address ?? 'N/A' }}<br>
                                            <span class="info-label">Router:</span> {{ $voucher->router_ip ?? 'N/A' }}{{ $voucher->server_name ? " - {$voucher->server_name}" : '' }}<br>
                                            <span class="info-label">↓</span> {{ $this->convertBytes($voucher->session_download) }} 
                                            <span class="info-label">↑</span> {{ $this->convertBytes($voucher->session_upload) }}
                                        </small>
                                    </td>
                                    <td style="min-width: 140px;">
                                        @if($timePercentage !== null)
                                            <div class="mb-1">
                                                <span class="badge badge-{{ $timeColor }}">
                                                    <i class="fas fa-clock mr-1"></i>{{ $timeRemaining['text'] ?? 'N/A' }}
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-{{ $timeColor }}" 
                                                    role="progressbar" 
                                                    style="width: {{ $timePercentage }}%"
                                                    aria-valuenow="{{ $timePercentage }}" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="info-value-muted">{{ round($timePercentage) }}% remaining</small>
                                        @else
                                            <span class="badge badge-profile-unlimited">
                                                <i class="fas fa-infinity mr-1"></i>Unlimited
                                            </span>
                                        @endif
                                    </td>
                                    <td style="min-width: 140px;">
                                        @if($voucher->data_limit > 0)
                                            <div class="mb-1">
                                                <span class="badge badge-{{ $dataColor }}">
                                                    <i class="fas fa-database mr-1"></i>{{ $this->convertBytes($voucher->data_credit) }}
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-{{ $dataColor }}" 
                                                    role="progressbar" 
                                                    style="width: {{ $dataPercentage }}%"
                                                    aria-valuenow="{{ $dataPercentage }}" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="info-value-muted">{{ round($dataPercentage ?? 0) }}% of {{ $this->convertBytes($voucher->data_limit) }}</small>
                                        @else
                                            <span class="badge badge-profile-unlimited">
                                                <i class="fas fa-infinity mr-1"></i>Unlimited
                                            </span>
                                        @endif
                                    </td>
                                    <td class="hide-mobile">
                                        <small>
                                            <span class="info-label">Used:</span>
                                            {{ $voucher->used_date ? Illuminate\Support\Carbon::parse($voucher->used_date)->format('M d, Y h:i A') : 'N/A' }}<br>
                                            <span class="info-label">Expires:</span>
                                            {{ $voucher->expire_date ? Illuminate\Support\Carbon::parse($voucher->expire_date)->format('M d, Y h:i A') : 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn btn-action btn-action-disconnect"
                                                wire:confirm.prompt="Disconnect user?\n\nType {{ substr($voucher->id, -5) }} to confirm|{{ substr($voucher->id, -5) }}"
                                                wire:click="disconnect({{ $voucher->id }})">
                                                <i class="fas fa-ban"></i>Disconnect
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="table-empty-state">
                                        <i class="fas fa-wifi"></i>
                                        <p>No active vouchers/users found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Legend --}}
                @if($this->voucher->count() > 0)
                <div class="mt-3 small">
                    <span class="mr-3"><span class="badge badge-success">●</span> &gt;50% remaining</span>
                    <span class="mr-3"><span class="badge badge-warning">●</span> 25-50% remaining</span>
                    <span><span class="badge badge-danger">●</span> &lt;25% remaining</span>
                </div>
                @endif
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="info-value-muted small">
                        Showing {{ $this->voucher->firstItem() ?? 0 }} to {{ $this->voucher->lastItem() ?? 0 }} 
                        of {{ $this->voucher->total() }} active users
                    </div>
                    <div>
                        {{ $this->voucher->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
