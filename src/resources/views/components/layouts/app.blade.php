@php
    $brandUser = auth()->user();
    $companyName = $brandUser->company_name ?? config('app.name', 'Hotspot Manager');
    $logoPath = $brandUser->logo_path ?? null;
    $faviconPath = $brandUser->favicon_path ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="kt-light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $companyName }} | {{ $pageName ?? '' }}</title>

    @if($faviconPath)
        <link rel="icon" href="{{ Storage::url($faviconPath) }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ Storage::url($faviconPath) }}" type="image/x-icon">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-base-100 font-sans antialiased">

    <x-mary-main :full-width="false" with-nav>

        <x-slot:sidebar drawer="main-drawer" class="bg-base-200 lg:bg-base-100 border-r border-base-300">
            {{-- Logo / Brand --}}
            <div class="p-4 border-b border-base-300">
                <a href="{{ tenant() ? route('client.dashboard') : route('admin.dashboard') }}" class="flex items-center gap-3 no-underline">
                    <div class="w-9 h-9 bg-primary rounded-xl flex items-center justify-center flex-shrink-0">
                        @if($logoPath)
                            <img src="{{ Storage::url($logoPath) }}" alt="Logo" class="w-7 h-7 object-contain rounded-lg">
                        @else
                            <x-mary-icon name="o-wifi" class="text-primary-content w-5 h-5" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-base-content text-sm leading-tight truncate">{{ $companyName }}</p>
                        <p class="text-xs text-base-content/50">Management Console</p>
                    </div>
                </a>
            </div>

            @include('components.partials.navigation-side')
        </x-slot:sidebar>

        <x-slot:navbar>
            @include('components.partials.navigation-top')
        </x-slot:navbar>

        <x-slot:content>
            <div class="p-4 lg:p-6 max-w-7xl mx-auto w-full">
                @include('components.partials.flash')
                {{ $slot }}
            </div>
        </x-slot:content>

    </x-mary-main>

    @livewireScripts
    @stack('scripts-bottom')
</body>
</html>
