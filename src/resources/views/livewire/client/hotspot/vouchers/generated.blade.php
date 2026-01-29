<div>
    <div class="row">
        <div class="col-12">
            <x-partials.flash />
        </div>
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header card-header-standard">
                    <h6 class="card-title">
                        <i class="fas fa-ticket-alt"></i>Generated Vouchers
                        <span class="badge badge-primary">{{ $this->vouchers->total() }}</span>
                    </h6>
                    <div class="card-actions">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input wire:model.live="searchVC" type="text" class="form-control search-input"
                                placeholder="Search vouchers...">
                        </div>
                        <a class="btn btn-action btn-action-primary" href="{{ route('client.voucher.generate') }}">
                            <i class="fas fa-plus"></i>Generate
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Bulk Action Bar --}}
                    @if(count($selectedVouchers) > 0)
                    <div class="bulk-action-bar">
                        <span class="selected-count"><strong>{{ count($selectedVouchers) }}</strong> voucher(s) selected</span>
                        <div class="btn-action-group">
                            <button class="btn btn-action btn-action-print" x-on:click="showBulkVoucherPrint()">
                                <i class="fas fa-print"></i>Print Selected
                            </button>
                            <button class="btn btn-action btn-action-delete"
                                wire:confirm="Are you sure you want to delete {{ count($selectedVouchers) }} voucher(s)?"
                                wire:click="bulkDeleteVouchers">
                                <i class="fas fa-trash"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Skeleton loader --}}
                    <div wire:loading.delay class="skeleton-wrapper">
                        @for($i = 0; $i < 5; $i++)
                        <div class="skeleton-row">
                            <div class="skeleton-cell" style="flex: 0.5;"><div class="skeleton skeleton-badge"></div></div>
                            <div class="skeleton-cell" style="flex: 2;"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text short"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-text short"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-text medium"></div></div>
                            <div class="skeleton-cell" style="flex: 0.5;"><div class="skeleton skeleton-badge"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-badge"></div></div>
                        </div>
                        @endfor
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="mobile-card-view" wire:loading.remove>
                        @forelse ($this->vouchers as $voucher)
                            <div class="voucher-card-mobile">
                                <div class="card-header-mobile">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" wire:model.live="selectedVouchers"
                                            value="{{ $voucher->id }}" class="checkbox-standard mr-3">
                                        <span class="voucher-code">{{ $voucher->code }}</span>
                                    </div>
                                    <x-partials.profile-badge 
                                        :name="$voucher->profile_name" 
                                        :uptime-limit="$voucher->uptime_limit ?? 0"
                                        :data-limit="$voucher->data_limit ?? 0" />
                                </div>
                                <div class="card-body-mobile">
                                    @if(!empty($voucher->password))
                                    <div class="data-item">
                                        <span class="data-label">Password</span>
                                        <span class="data-value text-warning">{{ $voucher->password }}</span>
                                    </div>
                                    @endif
                                    <div class="data-item">
                                        <span class="data-label">Data</span>
                                        <span class="data-value">{{ $voucher->data_limit > 0 ? $this->convertBytes($voucher->data_limit) : 'Unlimited' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="data-label">Time</span>
                                        <span class="data-value">{{ $voucher->uptime_limit > 0 ? $this->convertSeconds($voucher->uptime_limit) : 'Unlimited' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="data-label">Price</span>
                                        <span class="data-value">
                                            <x-partials.price-display :price="$voucher->price" />
                                        </span>
                                    </div>
                                    <div class="data-item">
                                        <span class="data-label">Generated</span>
                                        <span class="data-value" style="font-size: 0.8rem;">{{ Illuminate\Support\Carbon::parse($voucher->generation_date)->setTimezone(env('APP_TIMEZONE'))->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="card-actions-mobile">
                                    <button class="btn btn-action btn-action-delete"
                                        wire:confirm.prompt="Delete voucher {{ $voucher->code }}?\n\nType {{ $voucher->id }} to confirm|{{ $voucher->id }}"
                                        wire:click="deleteVoucher({{ $voucher->id }})">
                                        <i class="fas fa-trash"></i>Delete
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="table-empty-state">
                                <i class="fas fa-ticket-alt"></i>
                                <p>No vouchers available</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Desktop Table View --}}
                    <div class="table-responsive desktop-table-view" wire:loading.remove>
                        <table class="table table-standard table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" wire:model.live="selectAllVouchers" class="checkbox-standard">
                                    </th>
                                    <th>Voucher Info</th>
                                    <th>Credit</th>
                                    <th class="hide-mobile">Generated</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->vouchers as $voucher)
                                    <tr>
                                        <td>
                                            <input type="checkbox" wire:model.live="selectedVouchers"
                                                value="{{ $voucher->id }}" class="checkbox-standard">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-start">
                                                <span class="badge status-available mr-2" style="padding: 4px 6px;">
                                                    <i class="fas fa-check-circle" style="margin: 0;"></i>
                                                </span>
                                                <div>
                                                    <span class="info-label">Code:</span> <span class="info-value font-weight-bold">{{ $voucher->code }}</span><br />
                                                    @if(!empty($voucher->password))
                                                    <span class="info-label">Password:</span> <span class="text-warning">{{ $voucher->password }}</span><br />
                                                    @endif
                                                    <span class="info-label">Profile:</span>
                                                    <x-partials.profile-badge 
                                                        :name="$voucher->profile_name" 
                                                        :uptime-limit="$voucher->uptime_limit ?? 0"
                                                        :data-limit="$voucher->data_limit ?? 0" />
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="info-label">Data:</span> <span class="info-value">
                                                {{ $voucher->data_limit > 0 ? $this->convertBytes($voucher->data_limit) : 'Unlimited' }}</span><br />
                                            <span class="info-label">Time:</span> <span class="info-value">
                                                {{ $voucher->uptime_limit > 0 ? $this->convertSeconds($voucher->uptime_limit) : 'Unlimited' }}</span>
                                        </td>
                                        <td class="hide-mobile">
                                            {{ Illuminate\Support\Carbon::parse($voucher->generation_date)->setTimezone(env('APP_TIMEZONE'))->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            <x-partials.price-display :price="$voucher->price" />
                                        </td>
                                        <td>
                                            <div class="btn-action-group">
                                                <button class="btn btn-action btn-action-delete"
                                                    wire:confirm.prompt="Are you sure?\n\nType {{ $voucher->id }} to confirm|{{ $voucher->id }}"
                                                    wire:click="deleteVoucher({{ $voucher->id }})">
                                                    <i class="fas fa-trash"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="table-empty-state">
                                            <i class="fas fa-ticket-alt"></i>
                                            <p>No vouchers available</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $this->vouchers->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Batches Section --}}
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header card-header-standard">
                    <h6 class="card-title">
                        <i class="fas fa-layer-group"></i>Vouchers by Batch
                        <span class="badge badge-secondary">{{ $this->batches->total() }}</span>
                    </h6>
                    <div class="card-actions">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input wire:model.live="searchBATCH" type="text" class="form-control search-input"
                                placeholder="Search batches...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Bulk Action Bar --}}
                    @if(count($selectedBatches) > 0)
                    <div class="bulk-action-bar">
                        <span class="selected-count"><strong>{{ count($selectedBatches) }}</strong> batch(es) selected</span>
                        <div class="btn-action-group">
                            <button class="btn btn-action btn-action-print" x-on:click="showBulkBatchPrint()">
                                <i class="fas fa-print"></i>Print Selected
                            </button>
                            <button class="btn btn-action btn-action-delete"
                                wire:confirm="Are you sure you want to delete {{ count($selectedBatches) }} batch(es)?"
                                wire:click="bulkDeleteBatches">
                                <i class="fas fa-trash"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Skeleton loader --}}
                    <div wire:loading.delay class="skeleton-wrapper">
                        @for($i = 0; $i < 3; $i++)
                        <div class="skeleton-row">
                            <div class="skeleton-cell" style="flex: 0.5;"><div class="skeleton skeleton-badge"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-text medium"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-text short"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-text medium"></div></div>
                            <div class="skeleton-cell" style="flex: 0.5;"><div class="skeleton skeleton-badge"></div></div>
                            <div class="skeleton-cell"><div class="skeleton skeleton-badge"></div></div>
                        </div>
                        @endfor
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="mobile-card-view" wire:loading.remove>
                        @forelse ($this->batches as $batch)
                            <div class="voucher-card-mobile">
                                <div class="card-header-mobile">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" wire:model.live="selectedBatches"
                                            value="{{ $batch->batch_code }}" class="checkbox-standard mr-3">
                                        <span class="voucher-code">#{{ $batch->batch_code }}</span>
                                    </div>
                                    <x-partials.profile-badge 
                                        :name="$batch->name" 
                                        :uptime-limit="$batch->uptime_limit ?? 0"
                                        :data-limit="$batch->data_limit ?? 0" />
                                </div>
                                <div class="card-body-mobile">
                                    <div class="data-item">
                                        <span class="data-label">Reseller</span>
                                        <span class="data-value">{{ $batch->reseller_name ?? 'Direct' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="data-label">Quantity</span>
                                        <span class="data-value"><span class="badge badge-secondary">{{ $batch->count }}</span></span>
                                    </div>
                                    <div class="data-item">
                                        <span class="data-label">Price Each</span>
                                        <span class="data-value"><x-partials.price-display :price="$batch->price" /></span>
                                    </div>
                                    <div class="data-item">
                                        <span class="data-label">Generated</span>
                                        <span class="data-value" style="font-size: 0.8rem;">{{ Illuminate\Support\Carbon::parse($batch->generation_date)->setTimezone(env('APP_TIMEZONE'))->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="card-actions-mobile">
                                    <button class="btn btn-action btn-action-print"
                                        x-on:click="showVoucherTemplate({{ $batch->batch_code }})">
                                        <i class="fas fa-print"></i>Print
                                    </button>
                                    <button class="btn btn-action btn-action-delete"
                                        wire:confirm.prompt="Delete batch?\n\nType {{ substr($batch->batch_code, -5) }} to confirm|{{ substr($batch->batch_code, -5) }}"
                                        wire:click="deleteBatch({{ $batch->batch_code }})">
                                        <i class="fas fa-trash"></i>Delete
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="table-empty-state">
                                <i class="fas fa-layer-group"></i>
                                <p>No voucher batches available</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Desktop Table View --}}
                    <div class="table-responsive desktop-table-view" wire:loading.remove>
                        <table class="table table-standard table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" wire:model.live="selectAllBatches" class="checkbox-standard">
                                    </th>
                                    <th>Batch Code</th>
                                    <th>Profile</th>
                                    <th class="hide-mobile">Reseller</th>
                                    <th class="hide-mobile">Generated</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->batches as $batch)
                                    <tr>
                                        <td>
                                            <input type="checkbox" wire:model.live="selectedBatches"
                                                value="{{ $batch->batch_code }}" class="checkbox-standard">
                                        </td>
                                        <td>
                                            <span class="font-weight-bold info-value">{{ $batch->batch_code }}</span>
                                        </td>
                                        <td>
                                            <x-partials.profile-badge 
                                                :name="$batch->name" 
                                                :uptime-limit="$batch->uptime_limit ?? 0"
                                                :data-limit="$batch->data_limit ?? 0" />
                                        </td>
                                        <td class="hide-mobile">
                                            @if($batch->reseller_name)
                                                <span class="badge badge-info">{{ $batch->reseller_name }}</span>
                                            @else
                                                <span class="info-value-muted">Direct</span>
                                            @endif
                                        </td>
                                        <td class="hide-mobile">
                                            {{ Illuminate\Support\Carbon::parse($batch->generation_date)->setTimezone(env('APP_TIMEZONE'))->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            <x-partials.price-display :price="$batch->price" />
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ $batch->count }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-action-group">
                                                <button class="btn btn-action btn-action-print"
                                                    x-on:click="showVoucherTemplate({{ $batch->batch_code }})">
                                                    <i class="fas fa-print"></i>Print
                                                </button>
                                                <button class="btn btn-action btn-action-delete"
                                                    wire:confirm.prompt="Are you sure?\n\nType {{ substr($batch->batch_code, -5) }} to confirm|{{ substr($batch->batch_code, -5) }}"
                                                    wire:click="deleteBatch({{ $batch->batch_code }})">
                                                    <i class="fas fa-trash"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="table-empty-state">
                                            <i class="fas fa-layer-group"></i>
                                            <p>No voucher batches available</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        {{ $this->batches->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Print Modal --}}
    <div class="modal fade" id="print-voucher-form" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-print mr-2"></i>Print Vouchers</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="info-label">Voucher Template</label>
                        <select id="print_template" class="form-control">
                            <option value="0">Default Template</option>
                            @foreach ($this->templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-action btn-action-view" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-action btn-action-primary" onclick="printBatchNow()">
                        <i class="fas fa-print"></i>Print Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts-bottom')
    <script>
        let selectedBatch = null;
        let printMode = 'single';

        function showVoucherTemplate(batch) {
            selectedBatch = batch;
            printMode = 'single';
            $('#print-voucher-form').modal('show');
        }

        function showBulkVoucherPrint() {
            printMode = 'bulk-vouchers';
            $('#print-voucher-form').modal('show');
        }

        function showBulkBatchPrint() {
            printMode = 'bulk-batches';
            $('#print-voucher-form').modal('show');
        }

        function printBatchNow() {
            const voucherTemplate = $('#print_template').val();
            if (printMode === 'single') {
                window.open('print?batch=' + selectedBatch + '&template=' + voucherTemplate);
            } else if (printMode === 'bulk-vouchers') {
                const selectedVouchers = @this.getSelectedVouchersForPrint();
                window.open('print?vouchers=' + selectedVouchers + '&template=' + voucherTemplate);
            } else if (printMode === 'bulk-batches') {
                const selectedBatches = @this.getSelectedBatchesForPrint();
                window.open('print?batches=' + selectedBatches + '&template=' + voucherTemplate);
            }
        }
    </script>
@endpush
