<div class="w-full max-w-md mx-auto">

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <x-mary-icon name="o-shield-check" class="text-primary-content w-8 h-8" />
        </div>
        <h1 class="text-2xl font-bold text-base-content">Admin Login</h1>
        <p class="text-sm text-base-content/60 mt-1">Hotspot Manager Administration</p>
    </div>

    {{-- Login form --}}
    <x-mary-card shadow>
        <form wire:submit.prevent="login" class="space-y-4">
            <div>
                <label class="label"><span class="label-text font-medium">Username</span></label>
                <input type="text" wire:model="username" class="input input-bordered w-full"
                    placeholder="Enter username">
                @error('username')
                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="label"><span class="label-text font-medium">Password</span></label>
                <input type="password" wire:model="password" class="input input-bordered w-full"
                    placeholder="Enter password">
                @error('password')
                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full gap-2">
                <span wire:loading class="loading loading-spinner loading-sm"></span>
                <span wire:loading.remove>Login</span>
                <span wire:loading>Signing in...</span>
            </button>
        </form>
    </x-mary-card>

    {{-- Footer --}}
    <p class="text-center text-xs text-base-content/40 mt-6">
        Powered by <span class="font-medium text-base-content/60">KareTech Solutions</span>
    </p>

</div>
