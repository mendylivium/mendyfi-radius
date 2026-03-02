<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Create Hotspot Profile</h2>
            <p class="text-sm text-base-content/60">Define bandwidth limits, pricing, and time restrictions</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <x-mary-card shadow>
                <form wire:submit.prevent="create" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <label class="label">
                                <span class="label-text font-medium">Name</span>
                            </label>
                            <input type="text" wire:model="profileName"
                                class="input input-bordered w-full"
                                placeholder="Enter Profile Name" autocomplete="off">
                            @error('profileName')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label class="label">
                                <span class="label-text font-medium">Description</span>
                            </label>
                            <input type="text" wire:model="profileDescription"
                                class="input input-bordered w-full"
                                placeholder="Enter Description" autocomplete="off">
                            @error('profileDescription')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="divider text-xs text-base-content/40">Pricing & Time</div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Price --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Price</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-2 w-full">
                                <x-mary-icon name="o-currency-dollar" class="w-4 h-4 text-base-content/40" />
                                <input type="number" wire:model="profilePrice" class="grow" placeholder="0">
                            </label>
                            @error('profilePrice')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Uptime Limit --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Uptime Limit</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-2 w-full">
                                <x-mary-icon name="o-clock" class="w-4 h-4 text-base-content/40" />
                                <input type="number" wire:model="profileUptimeLimit" class="grow" placeholder="0">
                                <span class="text-xs text-base-content/40">sec</span>
                            </label>
                            @error('profileUptimeLimit')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Validity --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Validity</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-2 w-full">
                                <x-mary-icon name="o-clock" class="w-4 h-4 text-base-content/40" />
                                <input type="number" wire:model="profileValidity" class="grow" placeholder="0">
                                <span class="text-xs text-base-content/40">sec</span>
                            </label>
                            @error('profileValidity')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="divider text-xs text-base-content/40">Bandwidth & Data</div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Max Download --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Max Download Speed</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-2 w-full">
                                <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4 text-base-content/40" />
                                <input type="number" wire:model="profileMaxDownload" class="grow" placeholder="None">
                                <span class="text-xs text-base-content/40">B</span>
                            </label>
                            @error('profileMaxDownload')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Max Upload --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Max Upload Speed</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-2 w-full">
                                <x-mary-icon name="o-arrow-up-tray" class="w-4 h-4 text-base-content/40" />
                                <input type="number" wire:model="profileMaxUpload" class="grow" placeholder="None">
                                <span class="text-xs text-base-content/40">B</span>
                            </label>
                            @error('profileMaxUpload')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data Limit --}}
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Data Limit</span>
                            </label>
                            <label class="input input-bordered flex items-center gap-2 w-full">
                                <x-mary-icon name="o-signal" class="w-4 h-4 text-base-content/40" />
                                <input type="number" wire:model="profileDataLimit" class="grow" placeholder="None">
                                <span class="text-xs text-base-content/40">B</span>
                            </label>
                            @error('profileDataLimit')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @error('otherError')
                        <p class="text-error text-xs">{{ $message }}</p>
                    @enderror

                    <div class="flex gap-2 mt-6">
                        <a href="{{ route('client.vouchers.profiles') }}" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Profile</button>
                    </div>
                </form>
            </x-mary-card>
        </div>

        {{-- Conversions Sidebar --}}
        <div class="lg:col-span-1">
            <x-mary-card title="Conversions" shadow separator>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center py-1 border-b border-base-200">
                        <span class="font-medium text-base-content">1 MB</span>
                        <span class="text-base-content/60 font-mono text-xs">1,048,576 Bytes</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-base-200">
                        <span class="font-medium text-base-content">5 MB</span>
                        <span class="text-base-content/60 font-mono text-xs">5,242,880 Bytes</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-base-200">
                        <span class="font-medium text-base-content">10 MB</span>
                        <span class="text-base-content/60 font-mono text-xs">10,485,760 Bytes</span>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="font-medium text-base-content">1 Hour</span>
                        <span class="text-base-content/60 font-mono text-xs">3,600 Seconds</span>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
