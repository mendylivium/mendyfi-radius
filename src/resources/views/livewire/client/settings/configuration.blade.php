<div class="space-y-4">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Configuration</h2>
            <p class="text-sm text-base-content/60">RADIUS settings and account credentials</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Left Column: RADIUS Config + External Portal --}}
        <div class="space-y-4">

            {{-- RADIUS Configuration --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-cog-6-tooth" class="w-4 h-4" />
                        Configuration
                    </div>
                </x-slot:title>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">
                            <span class="label-text font-medium">RADIUS IP [PUBLIC / WAN]</span>
                        </label>
                        <input type="text" wire:model="radiusPublicIP" class="input input-bordered w-full bg-base-200 text-base-content/70" readonly>
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">RADIUS SECRET</span>
                        </label>
                        <input type="text" class="input input-bordered w-full bg-base-200 text-base-content/70" value="{{ $this->user->api_secret }}" readonly>
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Mikrotik Realm</span>
                        </label>
                        <input type="text" wire:model="mktikRealm" class="input input-bordered w-full bg-base-200 text-base-content/70" readonly>
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Other (NAS Identification)</span>
                        </label>
                        <input type="text" wire:model="otherRealm" class="input input-bordered w-full bg-base-200 text-base-content/70" readonly>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <x-mary-icon name="o-information-circle" class="w-5 h-5 shrink-0" />
                    <span class="text-sm">
                        <strong>Note:</strong> If you are behind CGNAT, the IP {{ $radiusPublicIP }} may not work.
                        You can use the local IP of this system instead.
                    </span>
                </div>

                <x-slot:actions>
                    @php
                        $rand = rand(1111, 9999);
                    @endphp
                    <button class="btn btn-warning btn-sm"
                        wire:confirm.prompt="This will reject devices that uses the old Config. \n\nPlease type {{ $rand }} tp proceed|{{ $rand }}"
                        wire:click="recreate">
                        <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                        Recreate
                    </button>
                </x-slot:actions>
            </x-mary-card>

            {{-- External Portal --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-globe-alt" class="w-4 h-4" />
                        External Portal
                    </div>
                </x-slot:title>

                <div>
                    <label class="label">
                        <span class="label-text font-medium">TPLINK-EAP Stand Alone</span>
                    </label>
                    <input type="text" class="input input-bordered w-full bg-base-200 text-base-content/70"
                        value="http://{{ $radiusPublicIP }}:8090/tplink-{{ $this->user->username }}" readonly>
                </div>
            </x-mary-card>

        </div>

        {{-- Right Column: Change Username + Change Password --}}
        <div class="space-y-4">

            {{-- Change Username --}}
            <form wire:submit.prevent="changeUserName">
                <x-mary-card shadow>
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-user" class="w-4 h-4" />
                            Change Username
                        </div>
                    </x-slot:title>

                    <div class="space-y-4">
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Username</span>
                            </label>
                            <input type="text" wire:model="userName" class="input input-bordered w-full">
                            @error('userName')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Current Password</span>
                            </label>
                            <input type="password" wire:model="currentPassword2" class="input input-bordered w-full">
                            @error('currentPassword2')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('otherError')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-slot:actions>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                            Change Username
                        </button>
                    </x-slot:actions>
                </x-mary-card>
            </form>

            {{-- Change Password --}}
            <form wire:submit.prevent="changePass">
                <x-mary-card shadow>
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-lock-closed" class="w-4 h-4" />
                            Change Password
                        </div>
                    </x-slot:title>

                    <div class="space-y-4">
                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Current Password</span>
                            </label>
                            <input type="password" wire:model="currentPassword" class="input input-bordered w-full">
                            @error('currentPassword')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">New Password</span>
                            </label>
                            <input type="password" wire:model="password" class="input input-bordered w-full">
                            @error('password')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Confirm New Password</span>
                            </label>
                            <input type="password" wire:model="password_confirmation" class="input input-bordered w-full">
                            @error('password_confirmation')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('otherError')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-slot:actions>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                            Change Password
                        </button>
                    </x-slot:actions>
                </x-mary-card>
            </form>

        </div>

    </div>

    {{-- Data Export --}}
    <x-mary-card shadow class="mt-4">
        <x-slot:title>
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4" />
                Data Export
            </div>
        </x-slot:title>

        <p class="text-sm text-base-content/70 mb-4">
            Export all your hotspot data (profiles, vouchers, resellers, templates, policies, and settings) as a JSON file.
            This can be used for backup or migration purposes.
        </p>

        <x-slot:actions>
            <button class="btn btn-primary btn-sm" wire:click="exportData">
                <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4" />
                Export My Data
            </button>
        </x-slot:actions>
    </x-mary-card>

</div>
