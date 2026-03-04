{{-- Mobile: drawer toggle --}}
<label for="main-drawer" class="lg:hidden mr-2 cursor-pointer">
    <x-mary-icon name="o-bars-3" class="w-6 h-6 text-base-content" />
</label>

{{-- Page title / breadcrumb spacer --}}
<div class="flex-1 min-w-0">
    <span class="text-sm font-medium text-base-content/70 hidden sm:inline">
        @yield('page-title', '')
    </span>
</div>

{{-- Right side controls --}}
<div class="flex items-center gap-1">

    {{-- Dark / light theme toggle --}}
    <x-mary-theme-toggle class="btn btn-ghost btn-sm btn-circle" />

    {{-- User avatar dropdown --}}
    <x-mary-dropdown class="ml-1">
        <x-slot:trigger>
            <button class="btn btn-ghost btn-sm gap-2 normal-case">
                @if(auth()->user()->picture ?? false)
                    <img src="{{ auth()->user()->picture }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                @else
                    <div class="w-7 h-7 bg-primary/10 rounded-full flex items-center justify-center">
                        <x-mary-icon name="o-user" class="w-4 h-4 text-primary" />
                    </div>
                @endif
                <span class="hidden md:inline text-sm font-medium text-base-content">
                    {{ auth()->user()->name ?? 'Account' }}
                </span>
                <x-mary-icon name="o-chevron-down" class="w-3 h-3 text-base-content/50" />
            </button>
        </x-slot:trigger>

        @if(tenant())
            <x-mary-menu-item title="Branding" icon="o-paint-brush" link="{{ route('client.settings.branding') }}" />
            <x-mary-menu-item title="Settings" icon="o-cog-6-tooth" link="{{ route('client.config') }}" />
            <x-mary-menu-separator />
            <x-mary-menu-item title="Sign Out" icon="o-arrow-right-on-rectangle" link="{{ route('logout') }}" />
        @else
            <x-mary-menu-separator />
            <x-mary-menu-item title="Sign Out" icon="o-arrow-right-on-rectangle" link="{{ route('admin.logout') }}" />
        @endif
    </x-mary-dropdown>

</div>
