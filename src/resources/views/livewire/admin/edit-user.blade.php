<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-base-content">Edit Tenant</h2>
            <p class="text-sm text-base-content/60">Update tenant account details</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm gap-1.5">
            <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
            Back
        </a>
    </div>

    <x-mary-card title="Tenant Details" shadow separator>
        <form wire:submit.prevent="editUser">
            <div class="space-y-4">
                <div>
                    <label class="label"><span class="label-text font-medium">Username</span></label>
                    <input disabled type="text" wire:model="userName" class="input input-bordered w-full input-disabled"
                        placeholder="Enter username" autocomplete="off">
                    @error('userName')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label"><span class="label-text font-medium">Password</span></label>
                    <input type="password" wire:model="password" class="input input-bordered w-full"
                        placeholder="Leave empty if no change" autocomplete="off">
                    <p class="text-xs text-base-content/50 mt-1">Leave empty to keep current password</p>
                    @error('password')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label"><span class="label-text font-medium">Domain Name</span></label>
                    <input type="text" wire:model="domain" class="input input-bordered w-full"
                        placeholder="e.g. tenant.hotspot.example.com">
                    @error('domain')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label"><span class="label-text font-medium">Status</span></label>
                    <select wire:model="userStatus" class="select select-bordered w-full">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    @error('userStatus')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @error('otherError')
                    <div class="alert alert-error">
                        <x-mary-icon name="o-exclamation-circle" class="w-5 h-5" />
                        <span class="text-sm">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <x-slot:actions>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </x-slot:actions>
        </form>
    </x-mary-card>
</div>
