<div class="space-y-4">

    {{-- Page header --}}
    <div>
        <h2 class="text-xl font-bold text-base-content">Configurations</h2>
        <p class="text-sm text-base-content/60">RADIUS settings and account security</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- RADIUS settings --}}
        <x-mary-card title="RADIUS Settings" shadow separator>
            <div class="space-y-4">
                <x-mary-input
                    label="Interim Update (seconds)"
                    type="number"
                    wire:model="radiusInterim"
                    placeholder="e.g. 60"
                    icon="o-clock"
                    hint="How often the NAS sends accounting updates to RADIUS" />
                @error('radiusInterim')
                    <p class="text-error text-xs -mt-3">{{ $message }}</p>
                @enderror

                <div class="p-3 bg-base-200 rounded-lg text-sm text-base-content/70 space-y-1">
                    <p><strong>INTERIM UPDATE</strong>: Frequency at which your router, modem, or NAS sends accounting updates to the RADIUS server.</p>
                    <p class="text-xs text-base-content/50">If resources are limited, use a higher interval to reduce load when many users are connected.</p>
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button
                    label="Apply Update"
                    icon="o-arrow-path"
                    wire:click="editSettings"
                    class="btn-warning"
                    spinner="editSettings" />
            </x-slot:actions>
        </x-mary-card>

        {{-- Change password --}}
        <x-mary-card title="Change Password" shadow separator>
            <div class="space-y-3">
                <x-mary-input
                    label="Current Password"
                    type="password"
                    wire:model="currentPassword"
                    icon="o-lock-closed"
                    autocomplete="current-password" />
                @error('currentPassword')
                    <p class="text-error text-xs -mt-3">{{ $message }}</p>
                @enderror

                <x-mary-input
                    label="New Password"
                    type="password"
                    wire:model="password"
                    icon="o-lock-open"
                    autocomplete="new-password" />
                @error('password')
                    <p class="text-error text-xs -mt-3">{{ $message }}</p>
                @enderror

                <x-mary-input
                    label="Confirm New Password"
                    type="password"
                    wire:model="password_confirmation"
                    icon="o-lock-open"
                    autocomplete="new-password" />
                @error('password_confirmation')
                    <p class="text-error text-xs -mt-3">{{ $message }}</p>
                @enderror
            </div>
            <x-slot:actions>
                <x-mary-button
                    label="Change Password"
                    icon="o-arrow-path"
                    wire:click="changePass"
                    class="btn-warning"
                    spinner="changePass" />
            </x-slot:actions>
        </x-mary-card>

    </div>
</div>
