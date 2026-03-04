<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-base-content">Backups</h2>
            <p class="text-sm text-base-content/60">Database backup management</p>
        </div>
        <div>
            <button
                class="btn btn-primary btn-sm"
                wire:click="runBackup"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="runBackup">
                    <x-mary-icon name="o-arrow-down-tray" class="w-4 h-4 inline-block mr-1" />
                    Run Backup Now
                </span>
                <span wire:loading wire:target="runBackup" class="flex items-center gap-2">
                    <span class="loading loading-spinner loading-xs"></span>
                    Running...
                </span>
            </button>
        </div>
    </div>

    {{-- In-progress alert --}}
    @if ($isRunning)
        <div class="alert alert-warning">
            <x-mary-icon name="o-clock" class="w-5 h-5 shrink-0" />
            <span class="text-sm">Backup in progress. Please wait...</span>
        </div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <x-mary-stat
            title="Central Backups"
            :value="count($this->centralBackups)"
            icon="o-circle-stack"
            class="shadow" />

        <x-mary-stat
            title="Tenant Backups"
            :value="count($this->tenantBackups)"
            icon="o-server-stack"
            class="shadow" />

        <x-mary-stat
            title="Total Size"
            :value="$this->totalSize"
            icon="o-arrow-down-tray"
            class="shadow" />

        <x-mary-stat
            title="Last Backup"
            :value="$this->lastBackupTime ?? 'Never'"
            icon="o-clock"
            class="shadow" />
    </div>

    {{-- Central Backups --}}
    <x-mary-card shadow>
        <x-slot:title>
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-circle-stack" class="w-4 h-4" />
                Central Backups
            </div>
        </x-slot:title>

        @if (count($this->centralBackups) === 0)
            <div class="py-8 text-center text-sm text-base-content/50">
                <x-mary-icon name="o-circle-stack" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                No central backups found
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 border-b border-base-200">
                            <th class="font-medium">Filename</th>
                            <th class="font-medium">Size</th>
                            <th class="font-medium">Date</th>
                            <th class="font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->centralBackups as $backup)
                            <tr class="border-b border-base-200 hover:bg-base-200/50">
                                <td class="text-sm font-mono">{{ $backup['name'] }}</td>
                                <td class="text-sm text-base-content/70">{{ $backup['size'] }}</td>
                                <td class="text-sm text-base-content/70">{{ $backup['date'] }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            class="btn btn-ghost btn-xs"
                                            wire:click="downloadBackup('central', '{{ $backup['name'] }}')"
                                            title="Download">
                                            <x-mary-icon name="o-arrow-down-tray" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            class="btn btn-ghost btn-error btn-xs"
                                            wire:click="deleteBackup('central', '{{ $backup['name'] }}')"
                                            wire:confirm="Delete {{ $backup['name'] }}? This cannot be undone."
                                            title="Delete">
                                            <x-mary-icon name="o-trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-mary-card>

    {{-- Tenant Backups --}}
    <x-mary-card shadow>
        <x-slot:title>
            <div class="flex items-center gap-2">
                <x-mary-icon name="o-server-stack" class="w-4 h-4" />
                Tenant Backups
            </div>
        </x-slot:title>

        @if (count($this->tenantBackups) === 0)
            <div class="py-8 text-center text-sm text-base-content/50">
                <x-mary-icon name="o-server-stack" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                No tenant backups found
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 border-b border-base-200">
                            <th class="font-medium">Tenant</th>
                            <th class="font-medium">Filename</th>
                            <th class="font-medium">Size</th>
                            <th class="font-medium">Date</th>
                            <th class="font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->tenantBackups as $backup)
                            <tr class="border-b border-base-200 hover:bg-base-200/50">
                                <td class="text-sm font-medium">{{ $backup['tenant'] ?? '-' }}</td>
                                <td class="text-sm font-mono">{{ $backup['name'] }}</td>
                                <td class="text-sm text-base-content/70">{{ $backup['size'] }}</td>
                                <td class="text-sm text-base-content/70">{{ $backup['date'] }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            class="btn btn-ghost btn-xs"
                                            wire:click="downloadBackup('tenants', '{{ $backup['name'] }}')"
                                            title="Download">
                                            <x-mary-icon name="o-arrow-down-tray" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            class="btn btn-ghost btn-error btn-xs"
                                            wire:click="deleteBackup('tenants', '{{ $backup['name'] }}')"
                                            wire:confirm="Delete {{ $backup['name'] }}? This cannot be undone."
                                            title="Delete">
                                            <x-mary-icon name="o-trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-mary-card>

</div>
