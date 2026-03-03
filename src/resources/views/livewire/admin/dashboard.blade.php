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

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <x-mary-stat
            title="Total Tenants"
            :value="$this->totalCount"
            icon="o-users"
            class="bg-base-100 shadow-sm"
            color="text-primary" />
        <x-mary-stat
            title="Active"
            :value="$this->activeCount"
            icon="o-check-circle"
            class="bg-base-100 shadow-sm"
            color="text-success" />
        <x-mary-stat
            title="Suspended"
            :value="$this->suspendedCount"
            icon="o-no-symbol"
            class="bg-base-100 shadow-sm"
            color="text-error" />
        <x-mary-stat
            title="Domains"
            :value="$this->domainCount"
            icon="o-globe-alt"
            class="bg-base-100 shadow-sm"
            color="text-info" />
    </div>

    {{-- Users table --}}
    <x-mary-card shadow>
        <x-slot:title>
            {{-- Toolbar --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full">

                {{-- Left: title + bulk actions --}}
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="font-semibold shrink-0">Tenant Accounts</span>

                    @if(count($selected) > 0)
                        <span class="badge badge-neutral badge-sm">{{ count($selected) }} selected</span>
                        <x-mary-button
                            label="Suspend"
                            icon="o-no-symbol"
                            class="btn-warning btn-sm"
                            wire:click="bulkSuspend"
                            wire:confirm="Suspend {{ count($selected) }} selected user(s)?" />
                        <x-mary-button
                            label="Activate"
                            icon="o-check-circle"
                            class="btn-success btn-sm"
                            wire:click="bulkActivate"
                            wire:confirm="Activate {{ count($selected) }} selected user(s)?" />
                    @endif
                </div>

                {{-- Right: filters --}}
                <div class="flex items-center gap-2 shrink-0">
                    <select wire:model.live="statusFilter" class="select select-sm select-bordered w-36">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <x-mary-input
                        placeholder="Search..."
                        wire:model.live.debounce.500ms="searchStr"
                        icon="o-magnifying-glass"
                        class="w-48 sm:w-64" />
                </div>
            </div>
        </x-slot:title>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-base-content/70 text-xs uppercase">
                        {{-- Select-all checkbox --}}
                        <th class="w-8">
                            <input
                                type="checkbox"
                                class="checkbox checkbox-sm"
                                @if($this->myTenants->count() > 0)
                                    @checked(count($selected) === $this->myTenants->count())
                                @endif
                                wire:click="$toggle('selected')"
                                x-on:click="
                                    const boxes = $el.closest('table').querySelectorAll('tbody input[type=checkbox]');
                                    const ids = Array.from(boxes).map(b => b.value);
                                    $wire.selected = $el.checked ? ids : [];
                                " />
                        </th>

                        {{-- Sortable: Username --}}
                        <th>
                            <button
                                wire:click="sortBy('domains.username')"
                                class="flex items-center gap-1 hover:text-base-content transition-colors">
                                Username
                                @if($sortField === 'domains.username')
                                    <x-mary-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3 h-3" />
                                @else
                                    <x-mary-icon name="o-chevron-up-down" class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>

                        {{-- Sortable: Domain --}}
                        <th>
                            <button
                                wire:click="sortBy('domains.domain')"
                                class="flex items-center gap-1 hover:text-base-content transition-colors">
                                Domain
                                @if($sortField === 'domains.domain')
                                    <x-mary-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3 h-3" />
                                @else
                                    <x-mary-icon name="o-chevron-up-down" class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>

                        {{-- Sortable: Status --}}
                        <th>
                            <button
                                wire:click="sortBy('domains.status')"
                                class="flex items-center gap-1 hover:text-base-content transition-colors">
                                Status
                                @if($sortField === 'domains.status')
                                    <x-mary-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3 h-3" />
                                @else
                                    <x-mary-icon name="o-chevron-up-down" class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>

                        {{-- Sortable: Created --}}
                        <th class="hidden sm:table-cell">
                            <button
                                wire:click="sortBy('domains.created_at')"
                                class="flex items-center gap-1 hover:text-base-content transition-colors">
                                Created
                                @if($sortField === 'domains.created_at')
                                    <x-mary-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3 h-3" />
                                @else
                                    <x-mary-icon name="o-chevron-up-down" class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->myTenants as $tenant)
                        <tr class="hover" wire:key="tenant-{{ $tenant->id }}">
                            <td>
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    value="{{ $tenant->id }}"
                                    wire:model.live="selected" />
                            </td>
                            <td class="font-medium">
                                <a href="{{ route('admin.tenant.detail', $tenant->id) }}"
                                   class="hover:text-primary transition-colors">
                                    {{ $tenant->username }}
                                </a>
                            </td>
                            <td class="text-sm text-base-content/70">{{ $tenant->domain }}</td>
                            <td>
                                @if($tenant->status === 'active')
                                    <span class="badge badge-success badge-sm gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="badge badge-error badge-sm gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="hidden sm:table-cell text-sm text-base-content/60">
                                {{ $tenant->domain_created_at
                                    ? \Carbon\Carbon::parse($tenant->domain_created_at)->format('d M Y')
                                    : '—' }}
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
                            <td colspan="6" class="text-center py-12 text-base-content/40">
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
