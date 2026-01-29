@props(['logoPath' => null, 'companyName' => null])

<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Brand for mobile -->
    <div class="d-md-none">
        @if($logoPath)
            <img src="{{ Storage::url($logoPath) }}" alt="Logo" style="max-height: 30px;" class="mr-2">
        @endif
        <span class="font-weight-bold text-gray-800">{{ $companyName ?? env('APP_NAME') }}</span>
    </div>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Dark Mode Toggle -->
        <li class="nav-item">
            <div class="dark-mode-toggle" 
                x-data="{ 
                    darkMode: localStorage.getItem('darkMode') === 'true',
                    init() {
                        this.$watch('darkMode', val => { 
                            localStorage.setItem('darkMode', val); 
                            document.body.setAttribute('data-theme', val ? 'dark' : 'light');
                        });
                        if(this.darkMode) {
                            document.body.setAttribute('data-theme', 'dark');
                        }
                    }
                }">
                <button @click="darkMode = !darkMode" class="btn btn-link nav-link" title="Toggle Dark Mode">
                    <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                </button>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ auth()->user()->name }}</span>
                <img class="img-profile rounded-circle"
                    src="{{ auth()->user()->picture ?? asset('img/undraw_profile.svg') }}">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                @if(tenant())
                    <a class="dropdown-item" href="{{ route('client.settings.branding') }}">
                        <i class="fas fa-palette fa-sm fa-fw mr-2 text-gray-400"></i>
                        Branding
                    </a>
                    <a class="dropdown-item" href="{{ route('client.config') }}">
                        <i class="fas fa-cog fa-sm fa-fw mr-2 text-gray-400"></i>
                        Settings
                    </a>
                    <div class="dropdown-divider"></div>
                @endif
                <a class="dropdown-item" href="{{ route(tenant() ? 'logout' : 'admin.logout') }}">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>

</nav>
<!-- End of Topbar -->
