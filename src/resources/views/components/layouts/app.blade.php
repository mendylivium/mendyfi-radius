@php
    $brandUser = auth()->user();
    $primaryColor = $brandUser->primary_color ?? '#4e73df';
    $secondaryColor = $brandUser->secondary_color ?? '#858796';
    $sidebarColor = $brandUser->sidebar_color ?? '#4e73df';
    $companyName = $brandUser->company_name ?? env('APP_NAME');
    $logoPath = $brandUser->logo_path ?? null;
    $faviconPath = $brandUser->favicon_path ?? null;

    // Helper function to adjust color brightness
    $adjustBrightness = function($hex, $steps) {
        $hex = ltrim($hex, '#');
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    };

    $sidebarDark = $adjustBrightness($sidebarColor, -30);
    $primaryHover = $adjustBrightness($primaryColor, -15);
    $primaryLinkHover = $adjustBrightness($primaryColor, -20);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>{{ $companyName }} | {{ $pageName ?? '' }}</title>

    @if($faviconPath)
        <link rel="icon" href="{{ Storage::url($faviconPath) }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ Storage::url($faviconPath) }}" type="image/x-icon">
    @endif

    <!-- Custom fonts for this template-->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('css/sb-admin-2.min.css') }}?v={{ filemtime(public_path('css/sb-admin-2.min.css')) }}" rel="stylesheet">
    
    <!-- UI/UX Refinements -->
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">

    <!-- Mobile Sidebar Fix (inline to bypass CSS caching) -->
    <style>
    @media (max-width: 991px) {
        .sidebar { width: 0 !important; overflow: hidden !important; }
        .sidebar.toggled { width: 14rem !important; overflow: visible !important; overflow-y: auto !important; }
        .sidebar.toggled .nav-item .nav-link { display: block !important; width: 100% !important; text-align: left !important; padding: 0.75rem 1rem !important; }
        .sidebar.toggled .nav-item .nav-link i { font-size: 0.85rem !important; margin-right: 0.25rem !important; }
        .sidebar.toggled .nav-item .nav-link span { font-size: 0.85rem !important; display: inline !important; }
        .sidebar.toggled .nav-item .collapse { position: relative !important; left: 0 !important; top: 0 !important; z-index: 1 !important; animation: none !important; -webkit-animation: none !important; margin: 0 0.5rem !important; }
        .sidebar.toggled .nav-item .collapsing { display: block !important; transition: height 0.15s ease !important; position: relative !important; left: 0 !important; top: 0 !important; margin: 0 0.5rem !important; }
        .sidebar.toggled .nav-item .nav-link[data-toggle="collapse"]::after { width: 1rem; text-align: center; float: right; font-weight: 900; content: '\f107'; font-family: 'Font Awesome 5 Free'; display: inline-block !important; }
        .sidebar.toggled .nav-item .nav-link[data-toggle="collapse"].collapsed::after { content: '\f105'; }
        .sidebar.toggled .sidebar-brand .sidebar-brand-text { display: inline !important; }
        .sidebar.toggled .sidebar-heading { text-align: left !important; }

        /* Sub-menu items blend with sidebar (not white popup) */
        .sidebar.toggled .nav-item .collapse .collapse-inner,
        .sidebar.toggled .nav-item .collapsing .collapse-inner {
            background: rgba(255,255,255,0.1) !important;
            border-radius: 0.35rem !important;
            box-shadow: none !important;
            padding: 0.5rem 0 !important;
        }
        .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item,
        .sidebar.toggled .nav-item .collapsing .collapse-inner .collapse-item {
            color: rgba(255,255,255,0.85) !important;
            padding: 0.6rem 1rem !important;
            font-size: 0.82rem !important;
        }
        .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item:hover,
        .sidebar.toggled .nav-item .collapsing .collapse-inner .collapse-item:hover {
            background: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }
        .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item.active,
        .sidebar.toggled .nav-item .collapsing .collapse-inner .collapse-item.active {
            color: #fff !important;
            font-weight: 700;
            background: rgba(255,255,255,0.2) !important;
        }
    }
    </style>

    {{-- Dynamic Brand Colors --}}
    <style>
        :root {
            --brand-primary: {{ $primaryColor }};
            --brand-secondary: {{ $secondaryColor }};
            --brand-sidebar: {{ $sidebarColor }};
        }

        /* Override primary colors */
        .bg-gradient-primary {
            background-color: var(--brand-sidebar) !important;
            background-image: linear-gradient(180deg, var(--brand-sidebar) 10%, {{ $sidebarDark }} 100%) !important;
        }

        .sidebar .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }

        .sidebar .nav-item.active .nav-link,
        .sidebar .nav-item .nav-link:hover {
            color: #fff;
        }

        .btn-primary {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
        }

        .btn-primary:hover {
            background-color: {{ $primaryHover }} !important;
            border-color: {{ $primaryHover }} !important;
        }

        .text-primary {
            color: var(--brand-primary) !important;
        }

        a {
            color: var(--brand-primary);
        }

        a:hover {
            color: {{ $primaryLinkHover }};
        }

        .border-left-primary {
            border-left-color: var(--brand-primary) !important;
        }

        .badge-primary {
            background-color: var(--brand-primary) !important;
        }

        .page-item.active .page-link {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
        }

        .page-link {
            color: var(--brand-primary);
        }

        .card-header {
            border-bottom-color: var(--brand-primary);
        }

        /* Secondary color overrides */
        .text-secondary {
            color: var(--brand-secondary) !important;
        }

        .btn-secondary {
            background-color: var(--brand-secondary) !important;
            border-color: var(--brand-secondary) !important;
        }

        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        /* Brand logo in sidebar */
        .sidebar-brand-icon img {
            max-height: 40px;
            max-width: 40px;
        }
    </style>
    @stack('styles')
    @stack('scripts-top')
</head>

<body id="page-top">
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div id="wrapper">
        <x-partials.navigation-side :logo-path="$logoPath" :company-name="$companyName" />
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <x-partials.navigation-top :logo-path="$logoPath" :company-name="$companyName" />
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h6 class="small mb-4 text-gray-800">HOME
                        @foreach ($links ?? [] as $link)
                            / {{ strtoupper($link) }}
                        @endforeach
                    </h6>
                    {{ $slot }}
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; {{ date('Y') }} {{ $companyName }} | Powered by <a href="//fb.me/mendylivium" target="_blank">MendyFi</a></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sb-admin-2.min.js') }}?v={{ filemtime(public_path('js/sb-admin-2.min.js')) }}"></script>
    
    <!-- Mobile sidebar toggle fix (inline to bypass JS caching) -->
    <script>
    (function(){
        // On mobile, SB Admin 2 default: sidebar visible at 6.5rem, toggled=hidden.
        // We invert: default=hidden (CSS width:0), toggled=visible (14rem).
        // On page load for mobile, ensure sidebar starts without .toggled (hidden).
        if (window.innerWidth <= 991) {
            var sb = document.querySelector('.sidebar');
            if (sb) sb.classList.remove('toggled');
            document.body.classList.remove('sidebar-toggled');
        }
    })();
    </script>
    
    <!-- Mobile Responsiveness Helpers -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebarToggleTop = document.getElementById('sidebarToggleTop');
        
        function isMobile() {
            return window.innerWidth <= 991;
        }
        
        function closeSidebar() {
            if (sidebar) {
                // On mobile, SB Admin 2 logic is inverted: toggled = visible
                // We need to remove 'toggled' to hide
                sidebar.classList.remove('toggled');
                document.body.classList.remove('sidebar-toggled');
            }
            if (overlay) {
                overlay.classList.remove('show');
            }
        }
        
        function updateOverlay() {
            if (!isMobile() || !overlay) return;
            // On mobile: sidebar.toggled = visible
            if (sidebar && sidebar.classList.contains('toggled')) {
                overlay.classList.add('show');
            } else {
                overlay.classList.remove('show');
            }
        }
        
        // Watch for toggle button clicks
        [sidebarToggle, sidebarToggleTop].forEach(function(btn) {
            if (btn) {
                btn.addEventListener('click', function() {
                    setTimeout(updateOverlay, 50);
                });
            }
        });
        
        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                closeSidebar();
            });
        }
        
        // Close sidebar when a non-collapsible nav link is clicked on mobile
        document.querySelectorAll('.sidebar .nav-link:not([data-toggle="collapse"])').forEach(function(link) {
            link.addEventListener('click', function() {
                if (isMobile()) {
                    closeSidebar();
                }
            });
        });
        
        // Close sidebar when a collapse sub-item is clicked on mobile
        document.querySelectorAll('.sidebar .collapse-item').forEach(function(item) {
            item.addEventListener('click', function() {
                if (isMobile()) {
                    closeSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (!isMobile() && overlay) {
                overlay.classList.remove('show');
            }
        });
    });
    </script>
    @stack('scripts-bottom')
</body>

</html>
