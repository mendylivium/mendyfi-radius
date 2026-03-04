<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Voucher Templates</h2>
            <p class="text-sm text-base-content/60">
                Manage printable voucher layouts
                <span class="badge badge-primary badge-sm ml-1">{{ $this->templates->total() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('client.voucher.template.create') }}" class="btn btn-primary btn-sm">
                <x-mary-icon name="o-plus" class="w-4 h-4" />
                Create Template
            </a>
        </div>
    </div>

    <x-mary-card shadow>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th>Template Name</th>
                        <th class="hidden sm:table-cell">Created On</th>
                        <th class="hidden sm:table-cell">Updated On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->templates as $template)
                        <tr>
                            <td>
                                <span class="font-bold text-base-content">{{ $template->name }}</span>
                            </td>
                            <td class="hidden sm:table-cell text-base-content/60 text-sm">
                                {{ $template->created_at->format('M d, Y h:i A') }}
                            </td>
                            <td class="hidden sm:table-cell text-base-content/60 text-sm">
                                {{ $template->updated_at->format('M d, Y h:i A') }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('client.voucher.template.edit', $template->id) }}"
                                        class="btn btn-ghost btn-xs">
                                        <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                                        Edit
                                    </a>
                                    <button class="btn btn-ghost btn-xs text-error"
                                        wire:confirm.prompt="Are you sure?\n\nType {{ $template->id }} to confirm|{{ $template->id }}"
                                        wire:click="deleteTemplate({{ $template->id }})">
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
                                    <x-mary-icon name="o-swatch" class="w-12 h-12 mb-3" />
                                    <p class="text-sm">No templates created yet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            {{ $this->templates->links() }}
        </div>
    </x-mary-card>
</div>
