<x-mary-menu activate-by-route class="mt-2 px-2">

    @if (!tenant())
        {{-- Admin navigation --}}
        <x-mary-menu-item title="Dashboard" icon="o-squares-2x2" link="{{ route('admin.dashboard') }}" />
        <x-mary-menu-item title="Config" icon="o-cog-6-tooth" link="{{ route('admin.config') }}" />
    @else
        {{-- Client navigation --}}
        <x-mary-menu-item title="Dashboard" icon="o-squares-2x2" link="{{ route('client.dashboard') }}" />

        <x-mary-menu-separator />

        {{-- Vouchers --}}
        <x-mary-menu-sub title="Vouchers" icon="o-ticket">
            <x-mary-menu-item title="Generate" icon="o-plus-circle" link="{{ route('client.voucher.generate') }}" />
            <x-mary-menu-item title="Generated" icon="o-list-bullet" link="{{ route('client.vouchers.list') }}" />
            <x-mary-menu-item title="Active" icon="o-check-circle" link="{{ route('client.vouchers.active') }}" />
            <x-mary-menu-item title="Used" icon="o-archive-box" link="{{ route('client.vouchers.used') }}" />
            <x-mary-menu-item title="Profiles" icon="o-adjustments-horizontal" link="{{ route('client.vouchers.profiles') }}" />
        </x-mary-menu-sub>

        {{-- Resellers --}}
        <x-mary-menu-sub title="Resellers" icon="o-users">
            <x-mary-menu-item title="List" icon="o-list-bullet" link="{{ route('client.reseller.list') }}" />
        </x-mary-menu-sub>

        {{-- Templates --}}
        <x-mary-menu-item title="Templates" icon="o-document-duplicate" link="{{ route('client.voucher.template') }}" />

        {{-- Fair Use Policy --}}
        <x-mary-menu-item title="Fair Use Policy" icon="o-shield-check" link="{{ route('client.fairuse.list') }}" />

        {{-- Sales --}}
        <x-mary-menu-item title="Sales" icon="o-chart-bar" link="{{ route('client.sales') }}" />

        <x-mary-menu-separator />

        {{-- Settings group --}}
        <x-mary-menu-item title="Configuration" icon="o-wrench-screwdriver" link="{{ route('client.config') }}" />
        <x-mary-menu-item title="Branding" icon="o-paint-brush" link="{{ route('client.settings.branding') }}" />
    @endif

</x-mary-menu>
