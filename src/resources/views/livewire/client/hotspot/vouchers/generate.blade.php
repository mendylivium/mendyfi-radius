<div>
    <div class="row">
        <div class="col-12">
            <x-partials.flash />
        </div>
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header card-header-standard">
                    <h6 class="card-title">
                        <i class="fas fa-plus-circle"></i>Generate Hotspot Vouchers
                    </h6>
                    <div class="card-actions">
                        <a class="btn btn-action btn-action-view" href="{{ route('client.vouchers.list') }}">
                            <i class="fas fa-arrow-left"></i>Back to Vouchers
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="generate">

                        {{-- Reseller Selection --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label class="info-label">
                                        <i class="fas fa-user-tie mr-1"></i>Select Reseller (optional)
                                    </label>
                                    <div class="input-group">
                                        <select wire:model="resellerId" class="form-control">
                                            <option value="0">- None -</option>
                                            @foreach ($this->resellers as $reseller)
                                                <option value="{{ $reseller->id }}">{{ $reseller->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <a href="{{ route('client.reseller.list') }}" target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                    @error('resellerId')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Voucher Code Settings --}}
                        <h6 class="info-label text-uppercase mb-3">
                            <i class="fas fa-key mr-1"></i>Voucher Code Settings
                        </h6>
                        <div class="row">
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="info-label">Code Prefix</label>
                                    <input type="text" wire:model="voucherPrefix"
                                        class="form-control" placeholder="e.g. WIFI-" autocomplete="off">
                                    @error('voucherPrefix')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="info-label">Character Pattern</label>
                                    <select wire:model="voucherPattern" class="form-control">
                                        <option value="1">ABCD1234</option>
                                        <option value="2">abcd1234</option>
                                        <option value="3">ABCDEFGH</option>
                                        <option value="4">abcdefgh</option>
                                        <option value="5">abcdEFGH</option>
                                        <option value="6">12345678</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="info-label">Character Length</label>
                                    <input type="number" wire:model="voucherPatternLength"
                                        class="form-control" placeholder="e.g. 5" autocomplete="off">
                                    @error('voucherPatternLength')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Password Settings --}}
                        <h6 class="info-label text-uppercase mb-3 mt-2">
                            <i class="fas fa-lock mr-1"></i>Password Settings
                        </h6>
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="info-label">Password Pattern</label>
                                    <select wire:model="passwordPattern" class="form-control">
                                        <option value="0">NO PASSWORD</option>
                                        <option value="1">ABCD1234</option>
                                        <option value="2">abcd1234</option>
                                        <option value="3">ABCDEFGH</option>
                                        <option value="4">abcdefgh</option>
                                        <option value="5">abcdEFGH</option>
                                        <option value="6">12345678</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="info-label">Password Length</label>
                                    <input type="number" wire:model="voucherPasswordLength"
                                        class="form-control" placeholder="e.g. 5" autocomplete="off">
                                    @error('voucherPasswordLength')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Profile & Quantity --}}
                        <h6 class="info-label text-uppercase mb-3">
                            <i class="fas fa-cogs mr-1"></i>Profile & Quantity
                        </h6>
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="info-label">Profile</label>
                                    <div class="input-group">
                                        <select wire:model="voucherProfile" class="form-control">
                                            <option value="0">- Select Profile -</option>
                                            @foreach ($this->profiles as $profile)
                                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <a href="{{ route('client.vouchers.profiles') }}" target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                    @error('voucherProfile')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <label class="info-label">Quantity</label>
                                    <input type="number" wire:model="voucherQty"
                                        class="form-control" placeholder="e.g. 10" autocomplete="off">
                                    @error('voucherQty')
                                        <span class="text text-xs text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @error('otherError')
                            <span class="text text-xs text-danger">{{ $message }}</span>
                        @enderror

                        <hr class="my-3">

                        {{-- Actions --}}
                        <div class="d-flex flex-column flex-md-row justify-content-start">
                            <a class="btn btn-action btn-action-view mr-md-2 mb-2 mb-md-0" href="{{ route('client.vouchers.list') }}">
                                <i class="fas fa-times"></i>Cancel
                            </a>
                            <button class="btn btn-action btn-action-primary" type="submit">
                                <i class="fas fa-bolt"></i>Generate Vouchers
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
