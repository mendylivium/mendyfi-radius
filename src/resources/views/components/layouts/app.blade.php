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

    {{-- Accessibility: skip-to-content link for keyboard/screen reader users --}}
    <style>
        .skip-to-content {
            position: absolute;
            left: -9999px;
            top: 0;
            z-index: 9999;
            padding: 0.75rem 1.5rem;
            background: var(--p, oklch(50% 0.2 260));
            color: white;
            font-weight: 600;
            border-radius: 0 0 0.5rem 0;
        }
        .skip-to-content:focus {
            left: 0;
        }
    </style>

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

        /* Fix: daisyUI drawer height=100svh ignores sticky nav above it.
           Mary UI with-nav only adjusts sidebar overlay, not the drawer container.
           .drawer-content needs overflow-y:auto so the page content scrolls. */
        main .drawer {
            height: calc(100dvh - 53px) !important;
        }
        main .drawer .drawer-content {
            height: calc(100dvh - 53px) !important;
            overflow-y: auto !important;
        }

        /* ── Consistent form element rounding ──
           @tailwindcss/forms plugin overrides daisyUI border-radius.
           Force all form elements to use daisyUI's --rounded-btn (0.5rem). */
        .input, .select, .textarea, .file-input,
        input[type="text"], input[type="email"], input[type="password"],
        input[type="number"], input[type="search"], input[type="tel"],
        input[type="url"], input[type="date"], input[type="datetime-local"],
        select, textarea {
            border-radius: var(--rounded-btn, 0.5rem) !important;
        }

        /* ── Touch-friendly form elements (min 44px target) ── */
        .input, .select, .textarea, .file-input,
        .btn, button, [role="button"],
        input[type="text"], input[type="email"], input[type="password"],
        input[type="number"], select, textarea {
            min-height: 2.75rem; /* 44px */
        }
        .btn-xs, .btn-sm { min-height: 2rem; }

        /* ── Consistent card rounding ── */
        .card, .alert, .stats {
            border-radius: var(--rounded-box, 0.75rem) !important;
        }

        /* ── Mobile table improvements ── */
        @media (max-width: 640px) {
            .table { font-size: 0.8125rem; }
            .table th, .table td { padding: 0.5rem 0.375rem; }
            .badge { font-size: 0.6875rem; }
        }

        /* ── Mary UI input group fix: ensure icon prefix matches input rounding ── */
        .input-group, [data-mary-input] {
            border-radius: var(--rounded-btn, 0.5rem) !important;
            overflow: hidden;
        }

        /* ── Checkbox/radio touch targets ── */
        .checkbox, .radio, .toggle {
            width: 1.25rem;
            height: 1.25rem;
        }
        label:has(.checkbox), label:has(.radio), label:has(.toggle) {
            min-height: 2.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── Accessibility: focus-visible outlines ──
           Keyboard users see a clear ring; mouse users do not. */
        :focus-visible {
            outline: 2px solid oklch(65% 0.25 260);
            outline-offset: 2px;
            border-radius: 0.25rem;
        }
        :focus:not(:focus-visible) {
            outline: none;
        }

        /* ── Accessibility: color contrast improvements ──
           WCAG AA requires 4.5:1 for normal text.
           /40 opacity on light bg fails; bump to /55 minimum. */
        .text-base-content\/40 {
            color: oklch(from var(--bc) l c h / 0.55) !important;
        }

        /* ── Accessibility: respect reduced motion preference ── */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* ── Mobile font scaling: prevent iOS zoom on focus ── */
        @media (max-width: 640px) {
            input, select, textarea {
                font-size: 1rem !important; /* 16px prevents iOS auto-zoom */
            }
        }

        /* ── Accessibility: ensure links/interactive elements are distinguishable ── */
        a:not(.btn):not([class*="badge"]) {
            text-decoration-thickness: 1px;
            text-underline-offset: 2px;
        }

        /* ── Active/selection state for table rows ── */
        .table tr.active-row {
            background-color: oklch(from var(--p) l c h / 0.08);
        }
    </style>
    @endif
</head>
<body class="min-h-screen bg-base-100 font-sans antialiased">

    {{-- Accessibility: skip navigation for keyboard/screen reader users --}}
    <a href="#main-content" class="skip-to-content">Skip to main content</a>

    {{-- Top navbar: rendered outside <x-mary-main> because Main component has no navbar slot --}}
    <x-mary-nav sticky full-width class="shadow-sm" role="navigation" aria-label="Main navigation">
        <x-slot:brand>
            @include('components.partials.navigation-top')
        </x-slot:brand>
    </x-mary-nav>

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

        <x-slot:content class="!p-2 lg:!px-8 lg:!py-5">
            <div id="main-content" class="max-w-7xl mx-auto w-full" role="main">
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
