<div class="space-y-4">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Branding</h2>
            <p class="text-sm text-base-content/60">Customise your company logo, favicon, and brand colours</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Left Column: Branding Form --}}
        <div class="lg:col-span-2">
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-swatch" class="w-4 h-4" />
                        Branding Settings
                    </div>
                </x-slot:title>

                <form wire:submit="save">

                    {{-- Company Name --}}
                    <div class="mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Company Name</span>
                        </label>
                        <input type="text" wire:model="company_name" class="input input-bordered w-full"
                            placeholder="Your Company Name">
                        <p class="text-xs text-base-content/60 mt-1">This will appear in the header and voucher prints</p>
                        @error('company_name')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="divider"></div>

                    {{-- Logo Upload --}}
                    <div class="mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Logo</span>
                        </label>

                        <div class="flex flex-wrap items-center gap-3">
                            @if($currentLogo)
                                <img src="{{ Storage::url($currentLogo) }}" alt="Current Logo"
                                    style="max-height: 60px; max-width: 200px;" class="rounded-lg border border-base-300" loading="lazy" width="200" height="60" alt="Company logo">
                                <button type="button" wire:click="removeLogo" class="btn btn-error btn-outline btn-sm">
                                    <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                                    Remove
                                </button>
                            @endif
                            <input type="file" wire:model="logo" class="file-input file-input-bordered w-full max-w-xs" id="logoInput" accept="image/*">
                        </div>

                        @if($logo)
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-success text-xs flex items-center gap-1">
                                    <x-mary-icon name="o-check-circle" class="w-4 h-4" />
                                    New logo selected -- click Save to apply
                                </span>
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" style="max-height: 60px;" class="rounded-lg border border-base-300" width="200" height="60">
                            </div>
                        @endif

                        <p class="text-xs text-base-content/60 mt-1">Recommended: PNG or JPG, max 2MB, transparent background preferred</p>
                        @error('logo')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Favicon Upload --}}
                    <div class="mb-4">
                        <label class="label">
                            <span class="label-text font-medium">Favicon</span>
                        </label>

                        <div class="flex flex-wrap items-center gap-3">
                            @if($currentFavicon)
                                <img src="{{ Storage::url($currentFavicon) }}" alt="Current Favicon"
                                    style="width: 32px; height: 32px;" class="rounded-lg border border-base-300" loading="lazy" width="32" height="32" alt="Favicon">
                                <button type="button" wire:click="removeFavicon" class="btn btn-error btn-outline btn-sm">
                                    <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                                    Remove
                                </button>
                            @endif
                            <input type="file" wire:model="favicon" class="file-input file-input-bordered w-full max-w-xs" id="faviconInput" accept="image/*">
                        </div>

                        @if($favicon)
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-success text-xs flex items-center gap-1">
                                    <x-mary-icon name="o-check-circle" class="w-4 h-4" />
                                    New favicon selected
                                </span>
                                <img src="{{ $favicon->temporaryUrl() }}" alt="Preview" style="width: 32px; height: 32px;" class="rounded-lg border border-base-300" loading="lazy" width="32" height="32" alt="Favicon">
                            </div>
                        @endif

                        <p class="text-xs text-base-content/60 mt-1">Recommended: Square image (32x32 or 64x64), PNG format, max 512KB</p>
                        @error('favicon')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="divider"></div>

                    {{-- Brand Colors --}}
                    <div class="mb-4">
                        <h6 class="font-bold text-base-content flex items-center gap-2 mb-3">
                            <x-mary-icon name="o-paint-brush" class="w-4 h-4" />
                            Brand Colors
                        </h6>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                            {{-- Primary Color --}}
                            <div>
                                <label class="label">
                                    <span class="label-text font-medium">Primary Color</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model.live="primary_color"
                                        class="w-12 h-10 rounded border border-base-300 cursor-pointer">
                                    <input type="text" wire:model.live="primary_color"
                                        class="input input-bordered flex-1" placeholder="#4e73df">
                                </div>
                                <p class="text-xs text-base-content/60 mt-1">Buttons, links, accents</p>
                                @error('primary_color')
                                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Secondary Color --}}
                            <div>
                                <label class="label">
                                    <span class="label-text font-medium">Secondary Color</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model.live="secondary_color"
                                        class="w-12 h-10 rounded border border-base-300 cursor-pointer">
                                    <input type="text" wire:model.live="secondary_color"
                                        class="input input-bordered flex-1" placeholder="#858796">
                                </div>
                                <p class="text-xs text-base-content/60 mt-1">Secondary elements</p>
                                @error('secondary_color')
                                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Sidebar Color --}}
                            <div>
                                <label class="label">
                                    <span class="label-text font-medium">Sidebar Color</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model.live="sidebar_color"
                                        class="w-12 h-10 rounded border border-base-300 cursor-pointer">
                                    <input type="text" wire:model.live="sidebar_color"
                                        class="input input-bordered flex-1" placeholder="#4e73df">
                                </div>
                                <p class="text-xs text-base-content/60 mt-1">Navigation sidebar</p>
                                @error('sidebar_color')
                                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Accent Color --}}
                            <div>
                                <label class="label">
                                    <span class="label-text font-medium">Accent Color</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model.live="accent_color"
                                        class="w-12 h-10 rounded border border-base-300 cursor-pointer">
                                    <input type="text" wire:model.live="accent_color"
                                        class="input input-bordered flex-1" placeholder="#059669">
                                </div>
                                <p class="text-xs text-base-content/60 mt-1">Highlights, notifications, special elements</p>
                                @error('accent_color')
                                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" wire:click="resetToDefault" class="btn btn-ghost">
                            <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                            Reset to Default
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <x-mary-icon name="o-photo" class="w-4 h-4" />
                            Save Branding Settings
                        </button>
                    </div>

                </form>
            </x-mary-card>
        </div>

        {{-- Right Column: Live Preview + Voucher Preview --}}
        <div class="space-y-4">

            {{-- Live Preview --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-eye" class="w-4 h-4" />
                        Live Preview
                    </div>
                </x-slot:title>

                {{-- Mini Sidebar Preview --}}
                <div class="flex rounded-lg overflow-hidden" style="min-height: 250px;">
                    <div style="width: 60px; background: linear-gradient(180deg, {{ $sidebar_color }} 10%, {{ $this->adjustBrightness($sidebar_color, -20) }} 100%);" class="flex flex-col items-center py-3 gap-2 shrink-0">
                        <div class="rounded-full bg-white flex items-center justify-center" style="width: 32px; height: 32px;">
                            @if($currentLogo)
                                <img src="{{ Storage::url($currentLogo) }}" style="max-width: 28px; max-height: 28px;">
                            @else
                                <span style="color: {{ $primary_color }}; font-size: 14px;">&#8226;&#8226;&#8226;</span>
                            @endif
                        </div>
                        <div class="w-3/4 bg-white rounded" style="height: 4px; opacity: 0.3;"></div>
                        <div class="w-3/4 bg-white rounded" style="height: 4px; opacity: 0.3;"></div>
                        <div class="w-3/4 bg-white rounded" style="height: 4px; opacity: 0.3;"></div>
                    </div>
                    <div class="flex-1 p-3 bg-base-200">
                        <div class="bg-base-100 rounded shadow-sm p-2 mb-2">
                            <span class="text-xs text-base-content/60">{{ $company_name ?: 'Your Company' }}</span>
                        </div>
                        <button class="btn btn-sm mb-2" style="background-color: {{ $primary_color }}; color: white; border: none;">
                            Primary Button
                        </button>
                        <button class="btn btn-sm mb-2" style="background-color: {{ $secondary_color }}; color: white; border: none;">
                            Secondary
                        </button>
                        <button class="btn btn-sm mb-2" style="background-color: {{ $accent_color }}; color: white; border: none;">
                            Accent
                        </button>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="badge text-white text-xs" style="background-color: {{ $primary_color }};">Badge</span>
                            <span class="badge text-white text-xs" style="background-color: {{ $accent_color }};">Accent</span>
                            <a href="#" class="text-xs" style="color: {{ $primary_color }};">Link Text</a>
                        </div>
                    </div>
                </div>
            </x-mary-card>

            {{-- Voucher Preview --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-ticket" class="w-4 h-4" />
                        Voucher Preview
                    </div>
                </x-slot:title>

                <div class="flex justify-center">
                    <div class="border border-base-300 rounded-lg p-3" style="max-width: 200px; width: 100%; background: linear-gradient(145deg, #fff 0%, #f8f9fa 100%);">
                        <div class="text-center mb-3" style="background: linear-gradient(135deg, {{ $primary_color }} 0%, {{ $this->adjustBrightness($primary_color, -20) }} 100%); color: white; padding: 8px; border-radius: 8px; margin: -12px -12px 12px -12px;">
                            @if($currentLogo)
                                <img src="{{ Storage::url($currentLogo) }}" style="max-height: 24px; max-width: 80px;" class="mb-1">
                            @endif
                            <div style="font-size: 11px; font-weight: bold;">{{ $company_name ?: 'Your Company' }}</div>
                        </div>
                        <div style="font-size: 10px; color: {{ $secondary_color }};" class="text-center">VOUCHER CODE</div>
                        <div class="text-center my-1" style="font-size: 16px; font-weight: bold; font-family: monospace; letter-spacing: 1px; border: 1px solid #ddd; padding: 4px; border-radius: 4px; background: #f8f9fa;">ABC123</div>
                        <div class="text-center mt-2" style="font-size: 11px;">
                            <span style="color: {{ $primary_color }}; font-weight: bold;">&#8369;50.00</span>
                        </div>
                    </div>
                </div>
            </x-mary-card>

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
