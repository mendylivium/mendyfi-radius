<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Users</h2>
            <p class="text-sm text-base-content/60">Manage tenant accounts</p>
        </div>
        <a href="{{ route('admin.adduser') }}" class="btn btn-primary btn-sm gap-1.5">
            <x-mary-icon name="o-plus" class="w-4 h-4" />
            Add User
        </a>
    </div>

    {{-- Stat --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <x-mary-stat
            title="Total Users"
            :value="$this->info"
            icon="o-users"
            class="bg-base-100 shadow-sm"
            color="text-primary" />
    </div>

    {{-- Users table --}}
    <x-mary-card shadow>
        <x-slot:title>
            <div class="flex items-center justify-between w-full">
                <span>Tenant Accounts</span>
                <x-mary-input
                    placeholder="Search by username or domain..."
                    wire:model.live.debounce.500ms="searchStr"
                    icon="o-magnifying-glass"
                    class="w-72" />
            </div>
        </x-slot:title>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        <th>ID</th>
                        <th>Username</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->myTenants as $tenant)
                        <tr class="hover">
                            <td class="font-mono text-xs text-base-content/50">{{ $tenant->id }}</td>
                            <td class="font-medium">{{ $tenant->username }}</td>
                            <td class="text-sm text-base-content/70">{{ $tenant->domain }}</td>
                            <td>
                                @if($tenant->status === 'active')
                                    <span class="badge badge-success badge-sm">Active</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">{{ $tenant->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="{{ route('admin.edituser', $tenant->id) }}"
                                       class="btn btn-ghost btn-xs gap-1">
                                        <x-mary-icon name="o-pencil-square" class="w-3.5 h-3.5" />
                                        Edit
                                    </a>
                                    <x-mary-button
                                        icon="o-trash"
                                        class="btn-ghost btn-xs btn-error"
                                        tooltip="Delete tenant"
                                        wire:confirm.prompt="Delete user {{ $tenant->username }}?\n\nType {{ substr($tenant->id, -2) }} to confirm|{{ substr($tenant->id, -2) }}"
                                        wire:click="deleteTenant('{{ $tenant->id }}')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-base-content/40">
                                <x-mary-icon name="o-users" class="w-10 h-10 mx-auto mb-2" />
                                <p class="text-sm">No users found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex justify-end mt-4">
            {{ $this->myTenants->links() }}
        </div>
    </x-mary-card>
</div>
