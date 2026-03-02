@php
    $brandUser = auth()->user();
    $companyName = $brandUser->company_name ?? config('app.name', 'Hotspot Manager');
    $logoPath = $brandUser->logo_path ?? null;
    $faviconPath = $brandUser->favicon_path ?? null;
    $primaryColor = $brandUser->primary_color ?? '#2563eb';
    $secondaryColor = $brandUser->secondary_color ?? '#7c3aed';
    $accentColor = $brandUser->accent_color ?? '#059669';
    $sidebarColor = $brandUser->sidebar_color ?? '#1e293b';

    // Hex to oklch conversion for daisyUI 4 CSS variable overrides
    $hexToOklch = function(string $hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '50% 0 0';
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        $toLinear = fn($c) => $c <= 0.04045 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        $r = $toLinear($r); $g = $toLinear($g); $b = $toLinear($b);
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;
        $cbrt = fn($x) => $x >= 0 ? pow($x, 1/3) : -pow(-$x, 1/3);
        $l = $cbrt($l); $m = $cbrt($m); $s = $cbrt($s);
        $L = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $bv = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;
        $C = sqrt($a * $a + $bv * $bv);
        $H = ($C < 0.0001) ? 0 : atan2($bv, $a) * 180 / M_PI;
        if ($H < 0) $H += 360;
        return sprintf('%.4f%% %.6f %.6f', $L * 100, $C, $H);
    };
    $contrastOklch = function(string $hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return '100% 0 0';
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        return $luminance > 0.55 ? '20% 0 0' : '100% 0 0';
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="kt-light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-version" content="{{ config('app.version', '2.0.0') }}">
    <title>{{ $companyName }} | {{ $pageName ?? '' }}</title>

    @if($faviconPath)
        <link rel="icon" href="{{ Storage::url($faviconPath) }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ Storage::url($faviconPath) }}" type="image/x-icon">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    @if($brandUser)
    <style>
        [data-theme="kt-light"],
        [data-theme="kt-dark"] {
            --p: {{ $hexToOklch($primaryColor) }};
            --pc: {{ $contrastOklch($primaryColor) }};
            --s: {{ $hexToOklch($secondaryColor) }};
            --sc: {{ $contrastOklch($secondaryColor) }};
            --a: {{ $hexToOklch($accentColor) }};
            --ac: {{ $contrastOklch($accentColor) }};
        }
        .sidebar-brand {
            background: linear-gradient(180deg, {{ $sidebarColor }} 0%, {{ $sidebarColor }}dd 100%);
            color: rgba(255, 255, 255, 0.85);
        }
        .sidebar-brand a { color: inherit; }
        .sidebar-brand .border-base-300 { border-color: rgba(255, 255, 255, 0.1); }
        .sidebar-brand [data-menu-item]:hover { background: rgba(255, 255, 255, 0.1); }
    </style>
    @endif
</head>
<body class="min-h-screen bg-base-100 font-sans antialiased">

    <x-mary-main :full-width="false" with-nav>

        <x-slot:sidebar drawer="main-drawer" class="sidebar-brand lg:sidebar-brand border-r border-base-300">
            {{-- Logo / Brand --}}
            <div class="p-4 border-b border-white/10">
                <a href="{{ tenant() ? route('client.dashboard') : route('admin.dashboard') }}" class="flex items-center gap-3 no-underline">
                    <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        @if($logoPath)
                            <img src="{{ Storage::url($logoPath) }}" alt="Logo" class="w-7 h-7 object-contain rounded-lg">
                        @else
                            <x-mary-icon name="o-wifi" class="text-white w-5 h-5" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-white text-sm leading-tight truncate">{{ $companyName }}</p>
                        <p class="text-xs text-white/60">Management Console</p>
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
            <footer class="mt-auto border-t border-base-300 py-4 px-6 text-center text-xs text-base-content/40">
                <p>{{ $companyName }} &mdash; Powered by <span class="font-medium text-base-content/60">KareTech Solutions</span></p>
            </footer>
        </x-slot:content>

    </x-mary-main>

    <x-mary-toast />
    @livewireScripts
    @stack('scripts-bottom')
</body>
</html>
