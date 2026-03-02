<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Edit Hotspot Profile</h2>
            <p class="text-sm text-base-content/60">Update bandwidth limits, pricing, and time restrictions</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <x-mary-card shadow>
                <form wire:submit.prevent="save" class="space-y-4">
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

                    <div class="divider text-xs text-base-content/40">Fair Use Policy</div>

                    {{-- FUP Bind Row --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Attach Policy</span>
                            </label>
                            <div class="flex gap-2">
                                <select wire:model="policyId" class="select select-bordered flex-1">
                                    <option value="0">-- None --</option>
                                    @foreach ($this->availFairUserPolicies as $policy)
                                        <option value="{{ $policy->id }}">{{ $policy->name }}</option>
                                    @endforeach
                                </select>
                                <a href="{{ route('client.fairuse.list') }}" target="_blank" rel="noopener noreferrer"
                                    class="btn btn-ghost btn-square" title="Manage Policies">
                                    <x-mary-icon name="o-arrow-up-tray" class="w-4 h-4" />
                                </a>
                                <button type="button" class="btn btn-success btn-square" wire:click="addPolicy()">
                                    <x-mary-icon name="o-plus" class="w-4 h-4" />
                                </button>
                            </div>
                            @error('policy_error')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Bound FUP Policies Table --}}
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr class="text-base-content/70 text-xs uppercase">
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Resets</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->bindedFairUserPolicies as $policy)
                                    <tr class="hover">
                                        <td class="text-xs font-mono text-base-content/60">{{ $policy->id }}</td>
                                        <td class="text-sm">{{ $policy->name }}</td>
                                        <td class="text-sm">{{ $policy->resets_every }}</td>
                                        <td>
                                            <button type="button" class="btn btn-error btn-xs"
                                                wire:click="unbindPolicy({{ $policy->id }})">
                                                <x-mary-icon name="o-trash" class="w-3 h-3" />
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="flex flex-col items-center justify-center py-8 text-base-content/40">
                                                <x-mary-icon name="o-shield-check" class="w-8 h-8 mb-2" />
                                                <p class="text-xs">No policy attached to this profile</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @error('otherError')
                        <p class="text-error text-xs">{{ $message }}</p>
                    @enderror

                    <div class="flex gap-2 mt-6">
                        <a href="{{ route('client.vouchers.profiles') }}" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
