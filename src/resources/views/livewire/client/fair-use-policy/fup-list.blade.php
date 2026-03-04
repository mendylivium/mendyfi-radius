<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Fair Use Policies</h2>
            <p class="text-sm text-base-content/60">
                Manage bandwidth and session throttling rules
                <span class="badge badge-primary badge-sm ml-1">{{ $this->policies->count() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('client.fairuse.add') }}" class="btn btn-primary btn-sm">
                <x-mary-icon name="o-plus" class="w-4 h-4" />
                Add Policy
            </a>
        </div>
    </div>

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table table-sm" aria-label="Fair use policies">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th class="w-20">ID</th>
                        <th>Policy Name</th>
                        <th>Reset Interval</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->policies as $policy)
                        <tr>
                            <td>
                                <span class="font-bold text-base-content">{{ $policy->id }}</span>
                            </td>
                            <td>
                                <span class="badge badge-outline">{{ $policy->name }}</span>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1 badge badge-ghost">
                                    <x-mary-icon name="o-clock" class="w-3 h-3" />
                                    {{ $policy->resets_every }} Minutes
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('client.fairuse.edit', $policy->id) }}"
                                        class="btn btn-ghost btn-xs">
                                        <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                        Edit
                                    </a>
                                    <button class="btn btn-ghost btn-xs text-error"
                                        wire:click="delete({{ $policy->id }})"
                                        wire:confirm="Are you sure you want to delete this policy?">
                                        <x-mary-icon name="o-trash" class="w-4 h-4" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="flex flex-col items-center justify-center py-12 text-base-content/40">
                                    <x-mary-icon name="o-shield-check" class="w-12 h-12 mb-3" />
                                    <p class="text-sm">No fair use policies defined</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-mary-card>
</div>
