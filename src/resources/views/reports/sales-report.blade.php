<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - {{ $user->username }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1f2937;
            background: #f3f4f6;
            line-height: 1.5;
        }

        .toolbar {
            background: #1f2937;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .toolbar h1 { font-size: 16px; font-weight: 600; }
        .toolbar .actions { display: flex; gap: 8px; }

        .toolbar button {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .toolbar button:hover { background: #2563eb; }

        .toolbar .btn-back {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .toolbar .btn-back:hover { background: rgba(255,255,255,0.1); }

        .report {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .report-header {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .report-header h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .report-header .meta {
            font-size: 13px;
            color: #6b7280;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .stat-card .label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }

        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .chart-card.full { grid-column: 1 / -1; }

        .chart-card h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #374151;
        }

        .chart-container { position: relative; width: 100%; }
        .chart-container.tall { height: 300px; }
        .chart-container.short { height: 250px; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead th {
            background: #f9fafb;
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }

        thead th.text-right { text-align: right; }

        tbody td {
            padding: 6px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        tbody td.text-right { text-align: right; }
        tbody td.mono { font-family: 'SF Mono', 'Fira Code', monospace; font-weight: 600; }

        tbody tr:hover { background: #f9fafb; }

        .breakdown-table {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .breakdown-table h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #374151;
        }

        .footer {
            text-align: center;
            padding: 24px;
            font-size: 11px;
            color: #9ca3af;
        }

        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; font-size: 10pt; }
            .report { margin: 0; padding: 0; max-width: 100%; }
            .report-header, .stat-card, .chart-card, .breakdown-table {
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
            .chart-card { page-break-inside: avoid; }
            .breakdown-table { page-break-inside: avoid; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .chart-row { grid-template-columns: 1fr 1fr; }
            .footer { page-break-before: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
        }

        @media (max-width: 640px) {
            .stats-grid { grid-template-columns: 1fr; }
            .chart-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    {{-- Toolbar (hidden on print) --}}
    <div class="toolbar">
        <h1>Sales Report</h1>
        <div class="actions">
            <button class="btn-back" onclick="window.close()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Close
            </button>
            <button onclick="window.print()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / Save PDF
            </button>
        </div>
    </div>

    <div class="report">

        {{-- Header --}}
        <div class="report-header">
            <h2>Sales Report</h2>
            <div class="meta">
                {{ $filterDescription }} |
                Generated {{ now()->format('d M Y, h:i A') }} |
                {{ $user->username }}
            </div>
        </div>

        {{-- Summary stats --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Transactions</div>
                <div class="value">{{ number_format($summary->total_count) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Revenue</div>
                <div class="value">{{ number_format($summary->total_revenue, 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Average Transaction</div>
                <div class="value">{{ number_format($summary->avg_transaction, 2) }}</div>
            </div>
        </div>

        {{-- Charts row 1: Revenue trend (full width) --}}
        @if($dailyRevenue->count() > 1)
        <div class="chart-row">
            <div class="chart-card full">
                <h3>Revenue Trend</h3>
                <div class="chart-container tall">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>
        @endif

        {{-- Charts row 2: Profile + Reseller --}}
        <div class="chart-row">
            @if($revenueByProfile->count() > 0)
            <div class="chart-card">
                <h3>Revenue by Profile</h3>
                <div class="chart-container short">
                    <canvas id="profileChart"></canvas>
                </div>
            </div>
            @endif

            @if($revenueByReseller->count() > 0)
            <div class="chart-card">
                <h3>Revenue by Reseller</h3>
                <div class="chart-container short">
                    <canvas id="resellerChart"></canvas>
                </div>
            </div>
            @endif
        </div>

        {{-- Charts row 3: Hourly distribution --}}
        @if($hourlyDistribution->count() > 0)
        <div class="chart-row">
            <div class="chart-card full">
                <h3>Hourly Sales Distribution</h3>
                <div class="chart-container short">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
        @endif

        {{-- Profile breakdown table --}}
        @if($revenueByProfile->count() > 0)
        <div class="breakdown-table">
            <h3>Profile Breakdown</h3>
            <table>
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revenueByProfile as $row)
                    <tr>
                        <td>{{ $row->profile_name }}</td>
                        <td class="text-right">{{ number_format($row->transactions) }}</td>
                        <td class="text-right mono">{{ number_format($row->revenue, 2) }}</td>
                        <td class="text-right">{{ $summary->total_revenue > 0 ? number_format(($row->revenue / $summary->total_revenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Reseller breakdown table --}}
        @if($revenueByReseller->count() > 0)
        <div class="breakdown-table">
            <h3>Reseller Breakdown</h3>
            <table>
                <thead>
                    <tr>
                        <th>Reseller</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revenueByReseller as $row)
                    <tr>
                        <td>{{ $row->reseller_name }}</td>
                        <td class="text-right">{{ number_format($row->transactions) }}</td>
                        <td class="text-right mono">{{ number_format($row->revenue, 2) }}</td>
                        <td class="text-right">{{ $summary->total_revenue > 0 ? number_format(($row->revenue / $summary->total_revenue) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Recent transactions table --}}
        @if($recentTransactions->count() > 0)
        <div class="breakdown-table">
            <h3>Recent Transactions (Last {{ $recentTransactions->count() }})</h3>
            <table>
                <thead>
                    <tr>
                        <th>Voucher Code</th>
                        <th>Profile</th>
                        <th>Reseller</th>
                        <th>MAC Address</th>
                        <th class="text-right">Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $txn)
                    <tr>
                        <td class="mono">{{ $txn->code }}</td>
                        <td>{{ $txn->profile_name ?? '-' }}</td>
                        <td>{{ $txn->reseller_name ?? 'Direct' }}</td>
                        <td style="font-size:11px;">{{ $txn->mac_address ?? '-' }}</td>
                        <td class="text-right mono">{{ number_format($txn->amount, 2) }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($txn->transact_date)->format('d M Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="footer">
            Generated by Mendyfi Hotspot Manager | {{ now()->format('d M Y, h:i:s A') }}
        </div>
    </div>

    <script>
        const COLORS = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
        ];

        Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Revenue Trend
        @if($dailyRevenue->count() > 1)
        new Chart(document.getElementById('revenueTrendChart'), {
            type: 'line',
            data: {
                labels: @json($dailyRevenue->pluck('date')->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->format('d M'))),
                datasets: [{
                    label: 'Revenue',
                    data: @json($dailyRevenue->pluck('revenue')),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                }, {
                    label: 'Transactions',
                    data: @json($dailyRevenue->pluck('transactions')),
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 2,
                    borderWidth: 1.5,
                    borderDash: [4, 4],
                    yAxisID: 'y1',
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Revenue' },
                        grid: { color: 'rgba(0,0,0,0.04)' },
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: { display: true, text: 'Transactions' },
                        grid: { display: false },
                    },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });
        @endif

        // Revenue by Profile (doughnut)
        @if($revenueByProfile->count() > 0)
        new Chart(document.getElementById('profileChart'), {
            type: 'doughnut',
            data: {
                labels: @json($revenueByProfile->pluck('profile_name')),
                datasets: [{
                    data: @json($revenueByProfile->pluck('revenue')),
                    backgroundColor: COLORS.slice(0, {{ $revenueByProfile->count() }}),
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } }
                }
            }
        });
        @endif

        // Revenue by Reseller (horizontal bar)
        @if($revenueByReseller->count() > 0)
        new Chart(document.getElementById('resellerChart'), {
            type: 'bar',
            data: {
                labels: @json($revenueByReseller->pluck('reseller_name')),
                datasets: [{
                    label: 'Revenue',
                    data: @json($revenueByReseller->pluck('revenue')),
                    backgroundColor: COLORS.slice(0, {{ $revenueByReseller->count() }}),
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                    y: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
        @endif

        // Hourly Distribution
        @if($hourlyDistribution->count() > 0)
        (() => {
            const allHours = Array.from({length: 24}, (_, i) => i);
            const hourData = @json($hourlyDistribution->keyBy('hour'));
            const labels = allHours.map(h => {
                const suffix = h >= 12 ? 'PM' : 'AM';
                const hour12 = h === 0 ? 12 : (h > 12 ? h - 12 : h);
                return hour12 + suffix;
            });
            const data = allHours.map(h => hourData[h] ? parseFloat(hourData[h].revenue) : 0);
            const txnData = allHours.map(h => hourData[h] ? parseInt(hourData[h].transactions) : 0);

            new Chart(document.getElementById('hourlyChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 3,
                    }, {
                        label: 'Transactions',
                        data: txnData,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderRadius: 3,
                        yAxisID: 'y1',
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Revenue' }, grid: { color: 'rgba(0,0,0,0.04)' } },
                        y1: { position: 'right', beginAtZero: true, title: { display: true, text: 'Transactions' }, grid: { display: false } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { position: 'top' } }
                }
            });
        })();
        @endif
    </script>
</body>
</html>
