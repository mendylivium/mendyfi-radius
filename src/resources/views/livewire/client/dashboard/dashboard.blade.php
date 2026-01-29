<div class="row">
    {{-- Collapsible Low Stock Alert Banner --}}
    @if($this->lowStockProfiles->count() > 0)
    <div class="col-12 mb-3" x-data="{ expanded: false }">
        <div class="alert alert-warning alert-dismissible fade show shadow-sm mb-0" role="alert">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                <div class="d-flex align-items-start w-100">
                    <i class="fas fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center alert-collapsible" @click="expanded = !expanded">
                            <h6 class="alert-heading mb-0 font-weight-bold">
                                <i class="fas fa-boxes mr-1"></i>
                                ⚠️ {{ $this->lowStockProfiles->count() }} profile{{ $this->lowStockProfiles->count() > 1 ? 's' : '' }} low on stock
                            </h6>
                            <button type="button" class="btn btn-link btn-sm text-warning p-0 ml-2">
                                <i class="fas fa-chevron-down alert-toggle" :class="{ 'rotated': expanded }"></i>
                                <span x-text="expanded ? 'Hide Details' : 'View Details'"></span>
                            </button>
                        </div>
                        <div class="alert-details mt-2" x-show="expanded" x-collapse>
                            <p class="mb-0 small">
                                Profiles with less than {{ $lowStockThreshold }} vouchers:
                                <strong>
                                    @foreach($this->lowStockProfiles as $profile)
                                        {{ $profile->name }} ({{ $profile->stock }}){{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </strong>
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('client.voucher.generate') }}" class="btn btn-warning mt-2 mt-md-0 ml-md-3 w-100 w-md-auto">
                    <i class="fas fa-plus mr-1"></i>Generate Now
                </a>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Quick Stats Row --}}
    <div class="col-12 mb-3">
        <div class="quick-stats shadow-sm">
            <div class="quick-stat-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Vouchers generated today:</span>
                <span class="quick-stat-value">{{ $this->vouchersGeneratedToday }}</span>
            </div>
            <div class="quick-stat-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Sales today:</span>
                <span class="quick-stat-value">{{ $this->salesToday }}</span>
            </div>
            <div class="quick-stat-item">
                <i class="fas fa-users"></i>
                <span>Active sessions:</span>
                <span class="quick-stat-value">{{ $this->user->active_vouchers }}</span>
            </div>
        </div>
    </div>

    {{-- Earnings Cards --}}
    @php
        $todayChange = $this->calculatePercentChange($this->sales->earnToday, $this->sales->earnYesterday);
        $weekChange = $this->calculatePercentChange($this->sales->earnThisWeek, $this->sales->earnLastWeek);
        $monthChange = $this->calculatePercentChange($this->sales->earnThisMonth, $this->sales->earnLastMonth);
    @endphp

    <div class="col-12 col-sm-6 col-xl-3 mb-2">
        <div class="card border-left-info shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Earnings (Today)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($this->sales->earnToday, 2) }}
                        </div>
                        @if($todayChange != 0)
                        <div class="small mt-1 {{ $todayChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-{{ $todayChange >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                            {{ abs($todayChange) }}% vs yesterday
                        </div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 mb-2">
        <div class="card border-left-success shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Earnings (Yesterday)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($this->sales->earnYesterday, 2) }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 mb-2">
        <div class="card border-left-warning shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Earnings (This Week)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($this->sales->earnThisWeek, 2) }}</div>
                        @if($weekChange != 0)
                        <div class="small mt-1 {{ $weekChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-{{ $weekChange >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                            {{ abs($weekChange) }}% vs last week
                        </div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 mb-2">
        <div class="card border-left-danger shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Earnings (This Month)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($this->sales->earnThisMonth, 2) }}</div>
                        @if($monthChange != 0)
                        <div class="small mt-1 {{ $monthChange >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="fas fa-{{ $monthChange >= 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                            {{ abs($monthChange) }}% vs last month
                        </div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart Section --}}
    <div class="col-md-8">
        <div class="card mb-2">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Earnings Overview</h6>
            </div>
            <div class="card-body pt-0 px-1 text-xs">
                <div class="chart-area">
                    <canvas id="chartJs">Your browser does not support the canvas element.</canvas>
                </div>

            </div>
        </div>
    </div>

    {{-- Stock & Stats Section --}}
    <div class="col-md-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-2">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-boxes mr-1"></i>Voucher Stock
                        </h6>
                        <a href="{{ route('client.voucher.generate') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Generate
                        </a>
                    </div>
                    <div class="card-body pt-0 px-0 text-xs">
                        {{-- Mobile Card View for Stock --}}
                        <div class="mobile-card-view px-3">
                            @foreach ($this->vouchers as $voucher)
                                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid #f0f0f0;">
                                    <div class="flex-grow-1">
                                        <x-partials.profile-badge 
                                            :name="$voucher->name" 
                                            :uptime-limit="$voucher->uptime_limit ?? 0"
                                            :data-limit="$voucher->data_limit ?? 0"
                                            :validity="$voucher->validity ?? 0" />
                                        <div class="small text-muted">Stock: <strong>{{ $voucher->stock }}</strong></div>
                                    </div>
                                    <div class="ml-2">
                                        @if($voucher->stock == 0)
                                            <span class="badge status-out">
                                                <i class="fas fa-times-circle"></i> Out
                                            </span>
                                        @elseif($voucher->stock < $lowStockThreshold)
                                            <span class="badge status-low">
                                                <i class="fas fa-exclamation-circle"></i> Low
                                            </span>
                                        @else
                                            <span class="badge status-ok">
                                                <i class="fas fa-check-circle"></i> OK
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-end mt-2">
                                {{ $this->vouchers->links() }}
                            </div>
                        </div>

                        {{-- Desktop Table View for Stock --}}
                        <div class="table-responsive desktop-table-view">
                            <table class="table table-standard table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Profile</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->vouchers as $voucher)
                                        <tr>
                                            <td>
                                                <x-partials.profile-badge 
                                                    :name="$voucher->name" 
                                                    :uptime-limit="$voucher->uptime_limit ?? 0"
                                                    :data-limit="$voucher->data_limit ?? 0"
                                                    :validity="$voucher->validity ?? 0" />
                                            </td>
                                            <td class="text-center font-weight-bold">{{ $voucher->stock }}</td>
                                            <td class="text-center">
                                                @if($voucher->stock == 0)
                                                    <span class="badge status-out">
                                                        <i class="fas fa-times-circle"></i> Out
                                                    </span>
                                                @elseif($voucher->stock < $lowStockThreshold)
                                                    <span class="badge status-low">
                                                        <i class="fas fa-exclamation-circle"></i> Low
                                                    </span>
                                                @else
                                                    <span class="badge status-ok">
                                                        <i class="fas fa-check-circle"></i> OK
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end mt-2 px-2">
                                {{ $this->vouchers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-primary text-white shadow mb-2">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-xs font-weight-bold text-uppercase">Active</span>
                                <div class="h4 mb-0">{{ $this->user->active_vouchers }}</div>
                            </div>
                            <i class="fas fa-wifi fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-success text-white shadow mb-2">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-xs font-weight-bold text-uppercase">Available</span>
                                <div class="h4 mb-0">{{ $this->user->available_vouchers }}</div>
                            </div>
                            <i class="fas fa-ticket-alt fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts-bottom')
    <script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>
    <script>
        var salesGraph = null;
        $(function() {

            Chart.defaults.global.defaultFontFamily = 'Nunito',
                '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
            Chart.defaults.global.defaultFontColor = '#858796';

            function number_format(number, decimals, dec_point, thousands_sep) {
                number = (number + '').replace(',', '').replace(' ', '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

            $.ajax({
                url: "{{ route('api.sales') }}",
                type: 'POST',
                success: function(data) {
                    const ctx = $('#chartJs');

                    salesChart = new Chart(ctx, {
                        type: data.type,
                        data: {
                            labels: data.date_time,
                            datasets: [{
                                label: "Earnings",
                                lineTension: 0.3,
                                backgroundColor: "rgba(78, 115, 223, 0.05)",
                                borderColor: "rgba(78, 115, 223, 1)",
                                pointRadius: 3,
                                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                                pointBorderColor: "rgba(78, 115, 223, 1)",
                                pointHoverRadius: 3,
                                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                                pointHitRadius: 10,
                                pointBorderWidth: 2,
                                data: data.sales
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 25,
                                    top: 25,
                                    bottom: 0
                                }
                            },
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        min: 0,
                                        callback: function(value) {
                                            return number_format(value);
                                        }
                                    },
                                    gridLines: {
                                        color: "rgb(234, 236, 244)",
                                        zeroLineColor: "rgb(234, 236, 244)",
                                        drawBorder: false,
                                        borderDash: [2],
                                        zeroLineBorderDash: [2]
                                    }
                                }],
                                xAxes: [{
                                    gridLines: {
                                        display: false,
                                        drawBorder: false
                                    }
                                }]
                            },
                            legend: {
                                display: false
                            },
                            tooltips: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyFontColor: "#858796",
                                titleMarginBottom: 10,
                                titleFontColor: '#6e707e',
                                titleFontSize: 14,
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                intersect: false,
                                mode: 'index',
                                caretPadding: 10,
                                callbacks: {
                                    label: function(tooltipItem, chart) {
                                        var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
                                    }
                                }
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
