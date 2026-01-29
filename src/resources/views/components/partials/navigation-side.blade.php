@props(['logoPath' => null, 'companyName' => null])

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ tenant() ? route('client.dashboard') : route('admin.dashboard') }}">
        <div class="sidebar-brand-icon {{ $logoPath ? '' : 'rotate-n-15' }}">
            @if($logoPath)
                <img src="{{ Storage::url($logoPath) }}" alt="Logo" style="max-height: 35px; max-width: 35px;">
            @else
                <i class="fas fa-wifi"></i>
            @endif
        </div>
        <div class="sidebar-brand-text mx-3">{{ $companyName ?? env('APP_NAME') }}</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">
    <!-- Nav Item - Dashboard -->


    @if (!tenant())
        <x-partials.nav-item route='admin.dashboard' label='Dashboard' icon='fas fa-fw fa-tachometer-alt' />
        <x-partials.nav-item route='admin.config' label='Config' icon='fas fa-fw fa-cog' />
    @else
        <x-partials.nav-item route='client.dashboard' label='Dashboard' icon='fas fa-fw fa-tachometer-alt' />
        <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Hotspot
        </div>
        <x-partials.nav-tree label="Vouchers" icon='fas fa-fw fa-ticket-alt' route="client.vouchers">
            <x-partials.nav-tree-item route='client.voucher.generate' label='Generate' />
            <x-partials.nav-tree-item route='client.vouchers.list' label='Generated' />
            <x-partials.nav-tree-item route='client.vouchers.active' label='Active' />
            <x-partials.nav-tree-item route='client.vouchers.used' label='Used' />
            <x-partials.nav-tree-item route='client.vouchers.profiles' label='Profile' />
        </x-partials.nav-tree>
        <x-partials.nav-tree label="Reseller" icon='fas fa-fw fa-users' route="client.reseller">
            <x-partials.nav-tree-item route='client.reseller.list' label='List' />
        </x-partials.nav-tree>
        <x-partials.nav-item route='client.voucher.template' label='Templates' icon='fas fa-fw fa-file-alt' />
        <x-partials.nav-item route='client.fairuse.list' label='Fair Use Policy' icon='fas fa-fw fa-list-ol' />
        <hr class="sidebar-divider">
        <x-partials.nav-item route='client.sales' label='Sales' icon='fas fa-fw fa-money-bill-alt' />

        <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Settings
        </div>
        <x-partials.nav-item route='client.config' label='Configuration' icon='fas fa-fw fa-cog' />
        <x-partials.nav-item route='client.settings.branding' label='Branding' icon='fas fa-fw fa-palette' />

        <!-- Divider -->
    @endif
    <hr class="sidebar-divider d-none d-md-block">
    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
