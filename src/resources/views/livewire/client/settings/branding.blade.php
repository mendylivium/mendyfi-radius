<div class="row">
    <div class="col-12">
        <x-partials.flash />
    </div>

    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-palette mr-2"></i>Branding Settings
                </h6>
            </div>
            <div class="card-body">
                <form wire:submit="save">
                    {{-- Company Name --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Company Name</label>
                        <input type="text" wire:model="company_name" class="form-control" 
                            placeholder="Your Company Name">
                        <small class="text-muted">This will appear in the header and voucher prints</small>
                        @error('company_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <hr>

                    {{-- Logo Upload --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Logo</label>
                        <div class="d-flex align-items-center">
                            @if($currentLogo)
                                <div class="mr-3">
                                    <img src="{{ Storage::url($currentLogo) }}" alt="Current Logo" 
                                        style="max-height: 60px; max-width: 200px;" class="rounded border">
                                </div>
                                <button type="button" wire:click="removeLogo" class="btn btn-sm btn-outline-danger mr-2">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            @endif
                            <div class="custom-file" style="max-width: 300px;">
                                <input type="file" wire:model="logo" class="custom-file-input" id="logoInput" accept="image/*">
                                <label class="custom-file-label" for="logoInput">
                                    {{ $logo ? $logo->getClientOriginalName() : 'Choose logo...' }}
                                </label>
                            </div>
                        </div>
                        @if($logo)
                            <div class="mt-2">
                                <small class="text-success"><i class="fas fa-check mr-1"></i>New logo selected - click Save to apply</small>
                                <br>
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" style="max-height: 60px;" class="mt-1 rounded border">
                            </div>
                        @endif
                        <small class="text-muted d-block mt-1">Recommended: PNG or JPG, max 2MB, transparent background preferred</small>
                        @error('logo') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Favicon Upload --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Favicon</label>
                        <div class="d-flex align-items-center">
                            @if($currentFavicon)
                                <div class="mr-3">
                                    <img src="{{ Storage::url($currentFavicon) }}" alt="Current Favicon" 
                                        style="width: 32px; height: 32px;" class="rounded border">
                                </div>
                                <button type="button" wire:click="removeFavicon" class="btn btn-sm btn-outline-danger mr-2">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            @endif
                            <div class="custom-file" style="max-width: 300px;">
                                <input type="file" wire:model="favicon" class="custom-file-input" id="faviconInput" accept="image/*">
                                <label class="custom-file-label" for="faviconInput">
                                    {{ $favicon ? $favicon->getClientOriginalName() : 'Choose favicon...' }}
                                </label>
                            </div>
                        </div>
                        @if($favicon)
                            <div class="mt-2">
                                <small class="text-success"><i class="fas fa-check mr-1"></i>New favicon selected</small>
                                <img src="{{ $favicon->temporaryUrl() }}" alt="Preview" style="width: 32px; height: 32px;" class="ml-2 rounded border">
                            </div>
                        @endif
                        <small class="text-muted d-block mt-1">Recommended: Square image (32x32 or 64x64), PNG format, max 512KB</small>
                        @error('favicon') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                    </div>

                    <hr>

                    {{-- Color Settings --}}
                    <h6 class="font-weight-bold text-gray-800 mb-3">
                        <i class="fas fa-fill-drip mr-2"></i>Brand Colors
                    </h6>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Primary Color</label>
                                <div class="input-group">
                                    <input type="color" wire:model.live="primary_color" class="form-control form-control-color" 
                                        style="width: 60px; height: 38px; padding: 2px;">
                                    <input type="text" wire:model.live="primary_color" class="form-control" 
                                        style="max-width: 100px;" placeholder="#4e73df">
                                </div>
                                <small class="text-muted">Buttons, links, accents</small>
                                @error('primary_color') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Secondary Color</label>
                                <div class="input-group">
                                    <input type="color" wire:model.live="secondary_color" class="form-control form-control-color" 
                                        style="width: 60px; height: 38px; padding: 2px;">
                                    <input type="text" wire:model.live="secondary_color" class="form-control" 
                                        style="max-width: 100px;" placeholder="#858796">
                                </div>
                                <small class="text-muted">Secondary elements</small>
                                @error('secondary_color') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Sidebar Color</label>
                                <div class="input-group">
                                    <input type="color" wire:model.live="sidebar_color" class="form-control form-control-color" 
                                        style="width: 60px; height: 38px; padding: 2px;">
                                    <input type="text" wire:model.live="sidebar_color" class="form-control" 
                                        style="max-width: 100px;" placeholder="#4e73df">
                                </div>
                                <small class="text-muted">Navigation sidebar</small>
                                @error('sidebar_color') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" wire:click="resetToDefault" class="btn btn-outline-secondary">
                            <i class="fas fa-undo mr-1"></i>Reset to Default
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Save Branding Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview Panel --}}
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-eye mr-2"></i>Live Preview
                </h6>
            </div>
            <div class="card-body p-0">
                {{-- Mini Sidebar Preview --}}
                <div class="d-flex" style="min-height: 250px;">
                    <div style="width: 60px; background: linear-gradient(180deg, {{ $sidebar_color }} 10%, {{ $this->adjustBrightness($sidebar_color, -20) }} 100%);" class="d-flex flex-column align-items-center py-3">
                        <div class="rounded-circle bg-white mb-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            @if($currentLogo)
                                <img src="{{ Storage::url($currentLogo) }}" style="max-width: 28px; max-height: 28px;">
                            @else
                                <i class="fas fa-wifi" style="color: {{ $primary_color }}; font-size: 14px;"></i>
                            @endif
                        </div>
                        <div class="w-75 bg-white rounded mb-2" style="height: 4px; opacity: 0.3;"></div>
                        <div class="w-75 bg-white rounded mb-2" style="height: 4px; opacity: 0.3;"></div>
                        <div class="w-75 bg-white rounded mb-2" style="height: 4px; opacity: 0.3;"></div>
                    </div>
                    <div class="flex-grow-1 p-3 bg-light">
                        <div class="bg-white rounded shadow-sm p-2 mb-2">
                            <small class="text-muted">{{ $company_name ?: 'Your Company' }}</small>
                        </div>
                        <button class="btn btn-sm mb-2" style="background-color: {{ $primary_color }}; color: white;">
                            Primary Button
                        </button>
                        <button class="btn btn-sm mb-2" style="background-color: {{ $secondary_color }}; color: white;">
                            Secondary
                        </button>
                        <div class="mt-2">
                            <span class="badge" style="background-color: {{ $primary_color }}; color: white;">Badge</span>
                            <a href="#" style="color: {{ $primary_color }};">Link Text</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Voucher Preview --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-ticket-alt mr-2"></i>Voucher Preview
                </h6>
            </div>
            <div class="card-body text-center">
                <div class="border rounded p-3" style="max-width: 200px; margin: 0 auto; background: linear-gradient(145deg, #fff 0%, #f8f9fa 100%);">
                    <div class="text-center mb-2" style="background: linear-gradient(135deg, {{ $primary_color }} 0%, {{ $this->adjustBrightness($primary_color, -20) }} 100%); color: white; padding: 8px; border-radius: 8px; margin: -12px -12px 12px -12px;">
                        @if($currentLogo)
                            <img src="{{ Storage::url($currentLogo) }}" style="max-height: 24px; max-width: 80px;" class="mb-1">
                        @endif
                        <div style="font-size: 11px; font-weight: bold;">{{ $company_name ?: 'Your Company' }}</div>
                    </div>
                    <div style="font-size: 10px; color: {{ $secondary_color }};">VOUCHER CODE</div>
                    <div style="font-size: 16px; font-weight: bold; font-family: monospace; letter-spacing: 1px; border: 1px solid #ddd; padding: 4px; border-radius: 4px; background: #f8f9fa;">ABC123</div>
                    <div class="mt-2" style="font-size: 11px;">
                        <span style="color: {{ $primary_color }}; font-weight: bold;">₱50.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts-bottom')
<script>
    // Update custom file label when file is selected
    document.addEventListener('livewire:navigated', function() {
        document.querySelectorAll('.custom-file-input').forEach(function(input) {
            input.addEventListener('change', function(e) {
                var fileName = e.target.files[0]?.name || 'Choose file...';
                var label = e.target.nextElementSibling;
                if (label) label.textContent = fileName;
            });
        });
    });
</script>
@endpush
