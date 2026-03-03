<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Generated Vouchers</h2>
            <p class="text-sm text-base-content/60">Unused vouchers ready to use
                <span class="badge badge-primary badge-sm ml-1">{{ $this->vouchers->total() }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-outline btn-sm gap-1.5" wire:click="exportCsv">
                <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4" />
                <span class="hidden sm:inline">Export CSV</span>
            </button>
            <a href="{{ route('client.voucher.generate') }}" class="btn btn-primary btn-sm gap-1.5">
                <x-mary-icon name="o-plus" class="w-4 h-4" />
                Generate
            </a>
        </div>
    </div>

    {{-- Vouchers card --}}
    <x-mary-card shadow separator>
        <x-slot:title>
            <div class="flex items-center justify-between w-full">
                <span>Individual Vouchers</span>
                <x-mary-input
                    placeholder="Search vouchers..."
                    wire:model.live="searchVC"
                    icon="o-magnifying-glass"
                    class="w-56" />
            </div>
        </x-slot:title>

        {{-- Bulk toolbar --}}
        @if(count($selectedVouchers) > 0)
        <div class="flex items-center gap-2 p-3 bg-warning/10 border border-warning/20 rounded-xl mb-4">
            <span class="text-sm font-medium">{{ count($selectedVouchers) }} selected</span>
            <button class="btn btn-ghost btn-sm gap-1"
                onclick="showBulkVoucherPrint()">
                <x-mary-icon name="o-printer" class="w-4 h-4" />
                Print
            </button>
            <x-mary-button
                label="Delete"
                icon="o-trash"
                wire:click="bulkDeleteVouchers"
                wire:confirm="Delete {{ count($selectedVouchers) }} voucher(s)? This cannot be undone."
                class="btn-error btn-sm" />
        </div>
        @endif

        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="table table-sm" aria-label="Individual vouchers">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-10">
                            <input type="checkbox" wire:model.live="selectAllVouchers" class="checkbox checkbox-sm" aria-label="Select all vouchers" />
                        </th>
                        <th>Voucher</th>
                        <th>Credit</th>
                        <th class="hidden md:table-cell">Generated</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->vouchers as $voucher)
                        <tr class="hover">
                            <td>
                                <input type="checkbox" wire:model.live="selectedVouchers"
                                    value="{{ $voucher->id }}" class="checkbox checkbox-sm" />
                            </td>
                            <td>
                                <div class="flex items-start gap-2">
                                    <span class="badge badge-success badge-xs mt-1">unused</span>
                                    <div>
                                        <div class="font-mono font-bold text-sm">{{ $voucher->code }}</div>
                                        @if(!empty($voucher->password))
                                            <div class="text-xs text-warning">Pass: {{ $voucher->password }}</div>
                                        @endif
                                        <div class="text-xs text-base-content/60">{{ $voucher->profile_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs">
                                <div>Data: {{ $voucher->data_limit > 0 ? $this->convertBytes($voucher->data_limit) : 'Unlimited' }}</div>
                                <div>Time: {{ $voucher->uptime_limit > 0 ? $this->convertSeconds($voucher->uptime_limit) : 'Unlimited' }}</div>
                            </td>
                            <td class="hidden md:table-cell text-xs text-base-content/60">
                                {{ \Illuminate\Support\Carbon::parse($voucher->generation_date)->setTimezone(env('APP_TIMEZONE'))->format('M d, Y H:i') }}
                            </td>
                            <td>
                                <x-partials.price-display :price="$voucher->price" />
                            </td>
                            <td>
                                <x-mary-button
                                    icon="o-trash"
                                    class="btn-ghost btn-xs btn-error"
                                    tooltip="Delete"
                                    wire:confirm.prompt="Delete voucher {{ $voucher->code }}?\n\nType {{ $voucher->id }} to confirm|{{ $voucher->id }}"
                                    wire:click="deleteVoucher({{ $voucher->id }})" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-base-content/40">
                                <x-mary-icon name="o-ticket" class="w-10 h-10 mx-auto mb-2" />
                                <p class="text-sm">No vouchers available</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex justify-end mt-4">
            {{ $this->vouchers->links() }}
        </div>
    </x-mary-card>

    {{-- Batches card --}}
    <x-mary-card shadow separator>
        <x-slot:title>
            <div class="flex items-center justify-between w-full">
                <span>Vouchers by Batch
                    <span class="badge badge-ghost badge-sm ml-1">{{ $this->batches->total() }}</span>
                </span>
                <x-mary-input
                    placeholder="Search batches..."
                    wire:model.live="searchBATCH"
                    icon="o-magnifying-glass"
                    class="w-56" />
            </div>
        </x-slot:title>

        {{-- Batch bulk toolbar --}}
        @if(count($selectedBatches) > 0)
        <div class="flex items-center gap-2 p-3 bg-warning/10 border border-warning/20 rounded-xl mb-4">
            <span class="text-sm font-medium">{{ count($selectedBatches) }} batch(es) selected</span>
            <button class="btn btn-ghost btn-sm gap-1" onclick="showBulkBatchPrint()">
                <x-mary-icon name="o-printer" class="w-4 h-4" />
                Print
            </button>
            <x-mary-button
                label="Delete"
                icon="o-trash"
                wire:click="bulkDeleteBatches"
                wire:confirm="Delete {{ count($selectedBatches) }} batch(es)? This cannot be undone."
                class="btn-error btn-sm" />
        </div>
        @endif

        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="table table-sm" aria-label="Voucher batches">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-10">
                            <input type="checkbox" wire:model.live="selectAllBatches" class="checkbox checkbox-sm" aria-label="Select all batches" />
                        </th>
                        <th>Batch Code</th>
                        <th>Profile</th>
                        <th class="hidden md:table-cell">Reseller</th>
                        <th class="hidden md:table-cell">Generated</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->batches as $batch)
                        <tr class="hover">
                            <td>
                                <input type="checkbox" wire:model.live="selectedBatches"
                                    value="{{ $batch->batch_code }}" class="checkbox checkbox-sm" />
                            </td>
                            <td class="font-mono font-bold text-sm">{{ $batch->batch_code }}</td>
                            <td>
                                <x-partials.profile-badge
                                    :name="$batch->name"
                                    :uptime-limit="$batch->uptime_limit ?? 0"
                                    :data-limit="$batch->data_limit ?? 0" />
                            </td>
                            <td class="hidden md:table-cell">
                                @if($batch->reseller_name)
                                    <span class="badge badge-info badge-sm">{{ $batch->reseller_name }}</span>
                                @else
                                    <span class="text-xs text-base-content/50">Direct</span>
                                @endif
                            </td>
                            <td class="hidden md:table-cell text-xs text-base-content/60">
                                {{ \Illuminate\Support\Carbon::parse($batch->generation_date)->setTimezone(env('APP_TIMEZONE'))->format('M d, Y H:i') }}
                            </td>
                            <td>
                                <x-partials.price-display :price="$batch->price" />
                            </td>
                            <td>
                                <span class="badge badge-ghost">{{ $batch->count }}</span>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <x-mary-button
                                        icon="o-printer"
                                        class="btn-ghost btn-xs"
                                        tooltip="Print batch"
                                        x-on:click="showVoucherTemplate({{ $batch->batch_code }})" />
                                    <x-mary-button
                                        icon="o-trash"
                                        class="btn-ghost btn-xs btn-error"
                                        tooltip="Delete batch"
                                        wire:confirm.prompt="Delete batch {{ $batch->batch_code }}?\n\nType {{ substr($batch->batch_code, -5) }} to confirm|{{ substr($batch->batch_code, -5) }}"
                                        wire:click="deleteBatch({{ $batch->batch_code }})" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-base-content/40">
                                <x-mary-icon name="o-squares-2x2" class="w-10 h-10 mx-auto mb-2" />
                                <p class="text-sm">No voucher batches available</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex justify-end mt-4">
            {{ $this->batches->links() }}
        </div>
    </x-mary-card>

    {{-- Print template modal (Alpine-driven, no jQuery) --}}
    <div
        x-data="{
            open: false,
            selectedBatch: null,
            printMode: 'single',
            open(mode, batch) {
                this.printMode = mode;
                this.selectedBatch = batch ?? null;
                this.open = true;
            },
            printNow() {
                const tpl = document.getElementById('print_template').value;
                if (this.printMode === 'single') {
                    window.open('print?batch=' + this.selectedBatch + '&template=' + tpl);
                } else if (this.printMode === 'bulk-vouchers') {
                    const ids = @this.getSelectedVouchersForPrint();
                    window.open('print?vouchers=' + ids + '&template=' + tpl);
                } else if (this.printMode === 'bulk-batches') {
                    const ids = @this.getSelectedBatchesForPrint();
                    window.open('print?batches=' + ids + '&template=' + tpl);
                }
                this.open = false;
            }
        }"
        @show-voucher-template.window="open('single', $event.detail)"
        @show-bulk-voucher-print.window="open('bulk-vouchers', null)"
        @show-bulk-batch-print.window="open('bulk-batches', null)"
    >
        <div x-show="open" class="modal modal-open" x-cloak role="dialog" aria-modal="true" aria-label="Print vouchers">
            <div class="modal-box max-w-sm">
                <h3 class="font-bold text-lg mb-4">Print Vouchers</h3>
                <div class="form-control mb-4">
                    <label class="label"><span class="label-text font-medium">Voucher Template</span></label>
                    <select id="print_template" class="select select-bordered w-full">
                        <option value="0">Default Template</option>
                        @foreach ($this->templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-action">
                    <button class="btn btn-ghost" @click="open = false">Cancel</button>
                    <button class="btn btn-primary gap-1.5" @click="printNow()">
                        <x-mary-icon name="o-printer" class="w-4 h-4" />
                        Print Now
                    </button>
                </div>
            </div>
            <div class="modal-backdrop" @click="open = false"></div>
        </div>
    </div>
</div>

@push('scripts-bottom')
<script>
    // Legacy helpers for inline onclick="showVoucherTemplate()" calls
    function showVoucherTemplate(batch) {
        window.dispatchEvent(new CustomEvent('show-voucher-template', { detail: batch }));
    }
    function showBulkVoucherPrint() {
        window.dispatchEvent(new CustomEvent('show-bulk-voucher-print'));
    }
    function showBulkBatchPrint() {
        window.dispatchEvent(new CustomEvent('show-bulk-batch-print'));
    }
</script>
@endpush
