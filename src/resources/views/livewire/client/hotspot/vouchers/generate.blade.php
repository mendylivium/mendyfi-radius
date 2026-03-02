<div class="space-y-4">
    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-base-content">Generate Vouchers</h2>
            <p class="text-sm text-base-content/60">Create new hotspot vouchers from a profile</p>
        </div>
        <a href="{{ route('client.vouchers.list') }}" class="btn btn-ghost btn-sm gap-1.5">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Back
        </a>
    </div>

    <x-mary-card title="Voucher Code Settings" shadow separator>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-mary-input
                label="Code Prefix"
                wire:model="voucherPrefix"
                placeholder="e.g. VC"
                icon="o-tag"
                hint="Alphanumeric prefix only" />
            @error('voucherPrefix')
                <p class="text-error text-xs -mt-3">{{ $message }}</p>
            @enderror

            <div>
                <label class="label"><span class="label-text font-medium">Character Pattern</span></label>
                <select wire:model="voucherPattern" class="select select-bordered w-full">
                    <option value="1">ABCD1234 (mixed upper+num)</option>
                    <option value="2">abcd1234 (mixed lower+num)</option>
                    <option value="3">ABCDEFGH (uppercase only)</option>
                    <option value="4">abcdefgh (lowercase only)</option>
                    <option value="5">abcdEFGH (mixed case)</option>
                    <option value="6">12345678 (numeric only)</option>
                </select>
            </div>

            <x-mary-input
                label="Character Length"
                type="number"
                wire:model="voucherPatternLength"
                min="3"
                max="12"
                icon="o-hashtag"
                hint="Min 3, max 12" />
            @error('voucherPatternLength')
                <p class="text-error text-xs -mt-3">{{ $message }}</p>
            @enderror
        </div>
    </x-mary-card>

    <x-mary-card title="Password Settings" shadow separator>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label"><span class="label-text font-medium">Password Pattern</span></label>
                <select wire:model="passwordPattern" class="select select-bordered w-full">
                    <option value="0">No Password</option>
                    <option value="1">ABCD1234 (mixed upper+num)</option>
                    <option value="2">abcd1234 (mixed lower+num)</option>
                    <option value="3">ABCDEFGH (uppercase only)</option>
                    <option value="4">abcdefgh (lowercase only)</option>
                    <option value="5">abcdEFGH (mixed case)</option>
                    <option value="6">12345678 (numeric only)</option>
                </select>
            </div>

            <x-mary-input
                label="Password Length"
                type="number"
                wire:model="voucherPasswordLength"
                min="4"
                max="16"
                icon="o-lock-closed"
                hint="Only used when a pattern is selected" />
            @error('voucherPasswordLength')
                <p class="text-error text-xs -mt-3">{{ $message }}</p>
            @enderror
        </div>
    </x-mary-card>

    <x-mary-card title="Profile & Quantity" shadow separator>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="label">
                    <span class="label-text font-medium">Profile</span>
                    <a href="{{ route('client.vouchers.profiles') }}" target="_blank" rel="noopener noreferrer"
                       class="label-text-alt link link-primary text-xs">Manage profiles</a>
                </label>
                <select wire:model="voucherProfile" class="select select-bordered w-full">
                    <option value="0">-- Select Profile --</option>
                    @foreach ($this->profiles as $profile)
                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                    @endforeach
                </select>
                @error('voucherProfile')
                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <x-mary-input
                label="Quantity"
                type="number"
                wire:model="voucherQty"
                min="1"
                max="1000"
                icon="o-hashtag"
                hint="Max 1000 per batch" />
            @error('voucherQty')
                <p class="text-error text-xs -mt-3">{{ $message }}</p>
            @enderror

            <div>
                <label class="label">
                    <span class="label-text font-medium">Reseller (optional)</span>
                    <a href="{{ route('client.reseller.list') }}" target="_blank" rel="noopener noreferrer"
                       class="label-text-alt link link-primary text-xs">Manage resellers</a>
                </label>
                <select wire:model="resellerId" class="select select-bordered w-full">
                    <option value="0">-- None (Direct) --</option>
                    @foreach ($this->resellers as $reseller)
                        <option value="{{ $reseller->id }}">{{ $reseller->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-slot:actions>
            <a href="{{ route('client.vouchers.list') }}" class="btn btn-ghost btn-sm">Cancel</a>
            <x-mary-button
                label="Generate Vouchers"
                icon="o-bolt"
                wire:click="generate"
                class="btn-primary"
                spinner="generate" />
        </x-slot:actions>
    </x-mary-card>
</div>
