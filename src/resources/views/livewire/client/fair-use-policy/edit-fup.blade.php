<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Edit Policy</h2>
            <p class="text-sm text-base-content/60">Update conditions and actions for this fair use policy</p>
        </div>
    </div>

    <x-mary-card shadow>
        <form wire:submit.prevent="savePolicy">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="label">
                            <span class="label-text font-medium">Policy Name</span>
                        </label>
                        <input type="text" wire:model="policyName" class="input input-bordered w-full"
                            placeholder="Enter Policy Name" autocomplete="off">
                        @error('policyName')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Resets Every (Minutes)</span>
                        </label>
                        <input type="number" wire:model="resetsEvery" class="input input-bordered w-full"
                            placeholder="e.g. 60" autocomplete="off">
                        @error('resetsEvery')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-card title="Condition" shadow>
                        <textarea wire:model="condition" cols="30" rows="10"
                            class="textarea textarea-bordered w-full"></textarea>
                        @error('condition')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </x-mary-card>

                    <x-mary-card title="Action" shadow>
                        <textarea wire:model="action" cols="30" rows="10"
                            class="textarea textarea-bordered w-full"></textarea>
                        @error('action')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </x-mary-card>
                </div>

                @error('otherError')
                    <p class="text-error text-xs">{{ $message }}</p>
                @enderror

                <div class="flex gap-2 mt-6">
                    <a href="{{ route('client.fairuse.list') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </x-mary-card>
</div>
