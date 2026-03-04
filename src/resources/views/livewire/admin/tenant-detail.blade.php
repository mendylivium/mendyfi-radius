<div class="space-y-4">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <x-mary-icon name="o-arrow-left" class="w-4 h-4" />
                    Back
                </a>
                <span class="text-base-content/30">/</span>
                <h2 class="text-xl font-bold text-base-content truncate">
                    {{ $this->primaryDomain?->username ?? $this->tenantId }}
                </h2>
                @if($this->primaryDomain)
                    @if($this->primaryDomain->status === 'active')
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
                @endif
            </div>
            <p class="text-sm text-base-content/60 mt-0.5 pl-2">Tenant account details and domain management</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.edituser', $this->tenantId) }}" class="btn btn-ghost btn-sm gap-1.5">
                <x-mary-icon name="o-pencil-square" class="w-4 h-4" />
                Edit
            </a>
            @if($this->primaryDomain)
                <x-mary-button
                    label="{{ $this->primaryDomain->status === 'active' ? 'Suspend' : 'Activate' }}"
                    icon="{{ $this->primaryDomain->status === 'active' ? 'o-no-symbol' : 'o-check-circle' }}"
                    class="{{ $this->primaryDomain->status === 'active' ? 'btn-warning' : 'btn-success' }} btn-sm"
                    wire:click="toggleStatus"
                    wire:confirm="{{ $this->primaryDomain->status === 'active' ? 'Suspend this tenant? All domains will stop working.' : 'Activate this tenant?' }}" />
            @endif
        </div>
    </div>

    {{-- Stats row --}}
    @if(!empty($this->tenantStats))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <x-mary-stat
                title="Users"
                :value="$this->tenantStats['users']"
                icon="o-user"
                class="bg-base-100 shadow-sm"
                color="text-primary" />
            <x-mary-stat
                title="Profiles"
                :value="$this->tenantStats['profiles']"
                icon="o-server-stack"
                class="bg-base-100 shadow-sm"
                color="text-secondary" />
            <x-mary-stat
                title="Total Vouchers"
                :value="$this->tenantStats['vouchers_total']"
                icon="o-ticket"
                class="bg-base-100 shadow-sm"
                color="text-info" />
            <x-mary-stat
                title="Active Vouchers"
                :value="$this->tenantStats['vouchers_active']"
                icon="o-check-circle"
                class="bg-base-100 shadow-sm"
                color="text-success" />
        </div>
    @else
        <div class="alert alert-warning">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 shrink-0" />
            <span class="text-sm">Tenant database is not accessible. Stats unavailable — the database may not have been provisioned yet.</span>
        </div>
    @endif

    {{-- Main content: two-column on desktop --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- LEFT COLUMN --}}
        <div class="space-y-4">

            {{-- Tenant Info --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-shield-check" class="w-4 h-4 text-primary" />
                        <span class="font-semibold">Tenant Info</span>
                    </div>
                </x-slot:title>

                <div class="divide-y divide-base-300">

                    {{-- Tenant ID --}}
                    <div class="py-3 flex items-start justify-between gap-3">
                        <span class="text-sm text-base-content/60 shrink-0">Tenant ID</span>
                        <div class="flex items-center gap-2 min-w-0">
                            <code class="font-mono text-xs bg-base-200 px-2 py-1 rounded truncate max-w-[180px]">{{ $this->tenantId }}</code>
                            <button
                                class="btn btn-ghost btn-xs"
                                title="Copy Tenant ID"
                                x-data="{ copied: false }"
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $this->tenantId }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 1500);
                                ">
                                <span x-show="!copied"><x-mary-icon name="o-clipboard-document" class="w-3.5 h-3.5" /></span>
                                <span x-show="copied" class="text-success text-xs">Copied!</span>
                            </button>
                        </div>
                    </div>

                    {{-- Database --}}
                    <div class="py-3 flex items-start justify-between gap-3">
                        <span class="text-sm text-base-content/60 shrink-0">Database</span>
                        <code class="font-mono text-xs bg-base-200 px-2 py-1 rounded truncate max-w-[220px]">
                            {{ config('tenancy.database.prefix', 'mendyfi_tenant_') . $this->tenantId }}
                        </code>
                    </div>

                    {{-- Created --}}
                    <div class="py-3 flex items-start justify-between gap-3">
                        <span class="text-sm text-base-content/60 shrink-0">Created</span>
                        <span class="text-sm">
                            {{ $this->tenant?->created_at
                                ? \Carbon\Carbon::parse($this->tenant->created_at)->format('d M Y, H:i')
                                : '—' }}
                        </span>
                    </div>

                    {{-- Username --}}
                    <div class="py-3 flex items-start justify-between gap-3">
                        <span class="text-sm text-base-content/60 shrink-0">Username</span>
                        <span class="text-sm font-medium">{{ $this->primaryDomain?->username ?? '—' }}</span>
                    </div>

                    {{-- Status --}}
                    <div class="py-3 flex items-start justify-between gap-3">
                        <span class="text-sm text-base-content/60 shrink-0">Status</span>
                        @if($this->primaryDomain?->status === 'active')
                            <span class="badge badge-success badge-sm gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                Active
                            </span>
                        @elseif($this->primaryDomain)
                            <span class="badge badge-error badge-sm gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                Suspended
                            </span>
                        @else
                            <span class="text-sm text-base-content/40">—</span>
                        @endif
                    </div>

                    {{-- Resellers (from stats if available) --}}
                    @if(!empty($this->tenantStats))
                        <div class="py-3 flex items-start justify-between gap-3">
                            <span class="text-sm text-base-content/60 shrink-0">Resellers</span>
                            <span class="text-sm">{{ $this->tenantStats['resellers'] }}</span>
                        </div>
                        <div class="py-3 flex items-start justify-between gap-3">
                            <span class="text-sm text-base-content/60 shrink-0">Templates</span>
                            <span class="text-sm">{{ $this->tenantStats['templates'] }}</span>
                        </div>
                    @endif

                </div>
            </x-mary-card>

            {{-- API Credentials --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-key" class="w-4 h-4 text-warning" />
                        <span class="font-semibold">API Credentials</span>
                    </div>
                </x-slot:title>

                @if(!empty($this->tenantStats))
                    @php
                        $apiKeys = null;
                        try {
                            tenancy()->initialize($this->tenant);
                            $apiKeys = \Illuminate\Support\Facades\DB::table('users')
                                ->select('api_public', 'api_secret')
                                ->whereNotNull('api_public')
                                ->first();
                            tenancy()->end();
                        } catch (\Exception $e) {
                            tenancy()->end();
                        }
                    @endphp

                    @if($apiKeys)
                        <div class="space-y-3">
                            <div>
                                <label class="label py-1">
                                    <span class="label-text text-xs font-medium text-base-content/70">API Public Key</span>
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ $apiKeys->api_public }}"
                                        class="input input-bordered input-sm w-full font-mono text-xs bg-base-200" />
                                    <button
                                        class="btn btn-ghost btn-sm shrink-0"
                                        title="Copy public key"
                                        x-data="{ copied: false }"
                                        x-on:click="
                                            navigator.clipboard.writeText('{{ $apiKeys->api_public }}');
                                            copied = true;
                                            setTimeout(() => copied = false, 1500);
                                        ">
                                        <span x-show="!copied"><x-mary-icon name="o-clipboard-document" class="w-4 h-4" /></span>
                                        <span x-show="copied" class="text-success text-xs">Copied!</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="label py-1">
                                    <span class="label-text text-xs font-medium text-base-content/70">API Secret</span>
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="password"
                                        readonly
                                        value="{{ $apiKeys->api_secret }}"
                                        class="input input-bordered input-sm w-full font-mono text-xs bg-base-200" />
                                    <button
                                        class="btn btn-ghost btn-sm shrink-0"
                                        title="Copy secret"
                                        x-data="{ copied: false }"
                                        x-on:click="
                                            navigator.clipboard.writeText('{{ $apiKeys->api_secret }}');
                                            copied = true;
                                            setTimeout(() => copied = false, 1500);
                                        ">
                                        <span x-show="!copied"><x-mary-icon name="o-clipboard-document" class="w-4 h-4" /></span>
                                        <span x-show="copied" class="text-success text-xs">Copied!</span>
                                    </button>
                                </div>
                            </div>

                            <p class="text-xs text-base-content/50 mt-2">
                                These are managed by the tenant in their Configuration page.
                            </p>
                        </div>
                    @else
                        <div class="py-4 text-center text-base-content/40">
                            <x-mary-icon name="o-key" class="w-8 h-8 mx-auto mb-2" />
                            <p class="text-sm">No API credentials configured</p>
                            <p class="text-xs mt-1">The tenant can generate these from their Configuration page.</p>
                        </div>
                    @endif
                @else
                    <div class="py-4 text-center text-base-content/40">
                        <x-mary-icon name="o-exclamation-triangle" class="w-8 h-8 mx-auto mb-2" />
                        <p class="text-sm">Database unavailable</p>
                    </div>
                @endif
            </x-mary-card>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="space-y-4">

            {{-- Domains --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-mary-icon name="o-globe-alt" class="w-4 h-4 text-info" />
                            <span class="font-semibold">Domains</span>
                            <span class="badge badge-neutral badge-sm">{{ $this->tenant?->domains->count() ?? 0 }}</span>
                        </div>
                        @if(!$showAddDomain)
                            <x-mary-button
                                label="Add Domain"
                                icon="o-plus"
                                class="btn-ghost btn-sm"
                                wire:click="$set('showAddDomain', true)" />
                        @endif
                    </div>
                </x-slot:title>

                <div class="space-y-2">
                    @forelse($this->tenant?->domains ?? [] as $domain)
                        @php $isPrimary = $loop->first; @endphp
                        <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors"
                             wire:key="domain-{{ $domain->id }}">

                            <div class="flex items-center gap-2 min-w-0">
                                <x-mary-icon name="o-globe-alt" class="w-4 h-4 text-base-content/40 shrink-0" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-sm font-medium truncate">{{ $domain->domain }}</span>
                                        @if($isPrimary)
                                            <span class="badge badge-primary badge-xs">Primary</span>
                                        @endif
                                        @if($domain->status === 'active')
                                            <span class="badge badge-success badge-xs">Active</span>
                                        @else
                                            <span class="badge badge-error badge-xs">Suspended</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-base-content/40 mt-0.5">
                                        Added {{ \Carbon\Carbon::parse($domain->created_at)->format('d M Y') }}
                                    </p>
                                </div>
                            </div>

                            <x-mary-button
                                icon="o-trash"
                                class="btn-ghost btn-xs btn-error shrink-0"
                                tooltip="{{ ($this->tenant?->domains->count() <= 1) ? 'Cannot remove the only domain' : 'Remove domain' }}"
                                :disabled="$this->tenant?->domains->count() <= 1"
                                wire:click="removeDomain({{ $domain->id }})"
                                wire:confirm="Remove domain {{ $domain->domain }}? This cannot be undone." />
                        </div>
                    @empty
                        <div class="py-6 text-center text-base-content/40">
                            <x-mary-icon name="o-globe-alt" class="w-8 h-8 mx-auto mb-2" />
                            <p class="text-sm">No domains configured</p>
                        </div>
                    @endforelse

                    {{-- Inline add domain form --}}
                    @if($showAddDomain)
                        <div class="mt-3 p-3 border border-base-300 rounded-lg space-y-2">
                            <label class="label py-0">
                                <span class="label-text text-xs font-medium">New Domain</span>
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    wire:model="newDomain"
                                    placeholder="e.g. tenant2.hotspot.example.com"
                                    class="input input-bordered input-sm w-full"
                                    wire:keydown.enter="addDomain"
                                    autocomplete="off" />
                                <x-mary-button
                                    icon="o-plus"
                                    class="btn-primary btn-sm shrink-0"
                                    wire:click="addDomain" />
                                <x-mary-button
                                    icon="o-x-mark"
                                    class="btn-ghost btn-sm shrink-0"
                                    wire:click="$set('showAddDomain', false)" />
                            </div>
                            @error('newDomain')
                                <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-base-content/50">
                                The new domain will inherit credentials and status from the primary domain.
                            </p>
                        </div>
                    @endif
                </div>
            </x-mary-card>

            {{-- Quick Actions --}}
            <x-mary-card shadow>
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-bolt" class="w-4 h-4 text-accent" />
                        <span class="font-semibold">Quick Actions</span>
                    </div>
                </x-slot:title>

                <div class="space-y-2">

                    {{-- Edit tenant --}}
                    <a href="{{ route('admin.edituser', $this->tenantId) }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200 transition-colors group">
                        <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                            <x-mary-icon name="o-pencil-square" class="w-4 h-4 text-primary" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">Edit Tenant</p>
                            <p class="text-xs text-base-content/50">Change domain, password, or status</p>
                        </div>
                        <x-mary-icon name="o-chevron-right" class="w-4 h-4 text-base-content/30 group-hover:text-base-content/60 transition-colors" />
                    </a>

                    {{-- View as tenant --}}
                    @if($this->primaryDomain)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-base-200/50">
                            <div class="w-8 h-8 bg-info/10 rounded-lg flex items-center justify-center">
                                <x-mary-icon name="o-arrow-top-right-on-square" class="w-4 h-4 text-info" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium">Tenant Portal</p>
                                <a href="https://{{ $this->primaryDomain->domain }}/client/dashboard"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-xs text-info hover:underline truncate block">
                                    {{ $this->primaryDomain->domain }}/client/dashboard
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Reseller summary (only if stats available) --}}
                    @if(!empty($this->tenantStats))
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-base-200/50">
                            <div class="w-8 h-8 bg-secondary/10 rounded-lg flex items-center justify-center">
                                <x-mary-icon name="o-users" class="w-4 h-4 text-secondary" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Resellers</p>
                                <p class="text-xs text-base-content/50">
                                    {{ $this->tenantStats['resellers'] }} reseller{{ $this->tenantStats['resellers'] !== 1 ? 's' : '' }} configured
                                </p>
                            </div>
                        </div>
                    @endif

                </div>
            </x-mary-card>

        </div>
    </div>

</div>
