<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Add Reseller</h2>
            <p class="text-sm text-base-content/60">Register a new voucher reseller account</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-mary-card shadow>
            <form wire:submit.prevent="addReseller" class="space-y-4">
                {{-- Full Name --}}
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Full Name</span>
                    </label>
                    <input type="text" wire:model="resellerName"
                        class="input input-bordered w-full"
                        placeholder="Enter Reseller Name" autocomplete="off">
                    @error('resellerName')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Address --}}
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Address</span>
                    </label>
                    <input type="text" wire:model="resellerAddress"
                        class="input input-bordered w-full"
                        placeholder="Enter Address" autocomplete="off">
                    @error('resellerAddress')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Contact Number --}}
                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Contact Number</span>
                        </label>
                        <label class="input input-bordered flex items-center gap-2 w-full">
                            <x-mary-icon name="o-device-phone-mobile" class="w-4 h-4 text-base-content/40" />
                            <input type="tel" wire:model="resellerMobile" class="grow"
                                placeholder="Enter Mobile No.">
                        </label>
                        @error('resellerMobile')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Contact Email --}}
                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Contact Email</span>
                        </label>
                        <label class="input input-bordered flex items-center gap-2 w-full">
                            <x-mary-icon name="o-envelope" class="w-4 h-4 text-base-content/40" />
                            <input type="email" wire:model="resellerEmail" class="grow"
                                placeholder="Enter Email">
                        </label>
                        @error('resellerEmail')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Status</span>
                    </label>
                    <select class="select select-bordered w-full" wire:model="resellerStatus">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                @error('otherError')
                    <p class="text-error text-xs">{{ $message }}</p>
                @enderror

                <div class="flex gap-2 mt-6">
                    <a href="{{ route('client.reseller.list') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Reseller</button>
                </div>
            </form>
        </x-mary-card>
    </div>
</div>
