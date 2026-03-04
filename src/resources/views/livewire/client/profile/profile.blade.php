<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Hotspot Profiles</h2>
            <p class="text-sm text-base-content/60">
                Manage bandwidth and time profiles for vouchers
                <span class="badge badge-primary badge-sm ml-1">{{ $this->profiles->total() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('client.vouchers.profile.create') }}" class="btn btn-primary btn-sm">
                <x-mary-icon name="o-plus" class="w-4 h-4" />
                Create Profile
            </a>
        </div>
    </div>

    <x-mary-card shadow>
        {{-- Bulk Action Bar --}}
        @if(count($selectedItems) > 0)
        <div class="flex items-center gap-2 p-3 bg-warning/10 border border-warning/20 rounded-xl mb-4">
            <x-mary-icon name="o-check" class="w-4 h-4 text-warning" />
            <span class="text-sm font-medium flex-1">
                <strong>{{ count($selectedItems) }}</strong> profile(s) selected
            </span>
            <button class="btn btn-error btn-sm"
                wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} profile(s) and ALL their vouchers?"
                wire:click="bulkDelete">
                <x-mary-icon name="o-trash" class="w-4 h-4" />
                Delete Selected
            </button>
        </div>
        @endif

        {{-- Mobile Card View --}}
        <div class="md:hidden space-y-3">
            @forelse ($this->profiles as $profile)
                <div class="border border-base-300 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between p-3 bg-base-200/50">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model.live="selectedItems"
                                value="{{ $profile->id }}" class="checkbox checkbox-sm">
                            <span class="font-semibold text-base-content">{{ $profile->name }}</span>
                        </div>
                        <x-partials.profile-badge
                            :name="$profile->name"
                            :uptime-limit="$profile->uptime_limit ?? 0"
                            :data-limit="$profile->data_limit ?? 0"
                            :validity="$profile->validity ?? 0" />
                    </div>
                    <div class="p-3 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Description</span>
                            <span class="text-base-content">{{ $profile->description ?: 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Price</span>
                            <span class="text-base-content"><x-partials.price-display :price="$profile->price" /></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Bandwidth</span>
                            <span class="text-base-content text-right text-xs">
                                ↑ {{ $profile->max_upload >= 1 ? "{$this->convertBytes($profile->max_upload)}ps" : 'Unlimited' }}<br>
                                ↓ {{ $profile->max_download >= 1 ? "{$this->convertBytes($profile->max_download)}ps" : 'Unlimited' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Data Limit</span>
                            <span class="text-base-content">{{ $profile->data_limit >= 1 ? "{$this->convertBytes($profile->data_limit)}" : 'Unlimited' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Time Limit</span>
                            <span class="text-base-content">{{ $profile->uptime_limit >= 1 ? "{$this->convertSeconds($profile->uptime_limit)}" : 'None' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/60">Validity</span>
                            <span class="text-base-content">{{ $profile->validity >= 1 ? "{$this->convertSeconds($profile->validity)}" : 'None' }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 p-3 bg-base-200/30 border-t border-base-300">
                        <a href="{{ route('client.vouchers.profile.edit', $profile->id) }}"
                            class="btn btn-ghost btn-sm flex-1">
                            <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                            Edit
                        </a>
                        <button class="btn btn-error btn-sm flex-1"
                            wire:confirm.prompt="Are you sure?\nThis will delete all vouchers with this profile\n\nType {{ $profile->id }} to confirm|{{ $profile->id }}"
                            wire:click="deleteProfile({{ $profile->id }})">
                            <x-mary-icon name="o-trash" class="w-4 h-4" />
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                    <x-mary-icon name="o-identification" class="w-12 h-12 mb-3" />
                    <p class="text-sm">No profiles created yet</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop Table View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="checkbox checkbox-sm">
                        </th>
                        <th>Profile Information</th>
                        <th>Bandwidth</th>
                        <th>Limits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->profiles as $profile)
                        <tr class="hover">
                            <td>
                                <input type="checkbox" wire:model.live="selectedItems"
                                    value="{{ $profile->id }}" class="checkbox checkbox-sm">
                            </td>
                            <td>
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <x-partials.profile-badge
                                            :name="$profile->name"
                                            :uptime-limit="$profile->uptime_limit ?? 0"
                                            :data-limit="$profile->data_limit ?? 0"
                                            :validity="$profile->validity ?? 0" />
                                    </div>
                                    <div class="text-xs text-base-content/50">{{ $profile->description ?: 'N/A' }}</div>
                                    <div class="text-xs"><x-partials.price-display :price="$profile->price" /></div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-base-content/50">Up:</span> {{ $profile->max_upload >= 1 ? "{$this->convertBytes($profile->max_upload)}ps" : 'Unlimited' }}</div>
                                    <div><span class="text-base-content/50">Down:</span> {{ $profile->max_download >= 1 ? "{$this->convertBytes($profile->max_download)}ps" : 'Unlimited' }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-base-content/50">Data:</span> {{ $profile->data_limit >= 1 ? "{$this->convertBytes($profile->data_limit)}" : 'Unlimited' }}</div>
                                    <div><span class="text-base-content/50">Uptime:</span> {{ $profile->uptime_limit >= 1 ? "{$this->convertSeconds($profile->uptime_limit)}" : 'None' }}</div>
                                    <div><span class="text-base-content/50">Validity:</span> {{ $profile->validity >= 1 ? "{$this->convertSeconds($profile->validity)}" : 'None' }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('client.vouchers.profile.edit', $profile->id) }}"
                                        class="btn btn-ghost btn-xs">
                                        <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                        Edit
                                    </a>
                                    <button class="btn btn-error btn-xs"
                                        wire:confirm.prompt="Are you sure?\nThis will delete all vouchers with this profile\n\nType {{ $profile->id }} to confirm|{{ $profile->id }}"
                                        wire:click="deleteProfile({{ $profile->id }})">
                                        <x-mary-icon name="o-trash" class="w-4 h-4" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center justify-center py-16 text-base-content/40">
                                    <x-mary-icon name="o-identification" class="w-12 h-12 mb-3" />
                                    <p class="text-sm">No profiles created yet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-end mt-4">
            {{ $this->profiles->links() }}
        </div>
    </x-mary-card>
</div>
