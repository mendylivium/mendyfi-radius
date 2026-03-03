<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function show(Request $request)
    {
        $user = auth()->user();

        // Parse filters (same as Sales Livewire component)
        $dateRange = $request->input('dateRange', 'all');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $profileFilter = $request->input('profileFilter');
        $resellerFilter = $request->input('resellerFilter');

        [$startDate, $endDate] = $this->getDateRange($dateRange, $dateFrom, $dateTo);

        $baseQuery = SalesRecord::query()
            ->where('sales_records.user_id', $user->id);

        if ($startDate) {
            $baseQuery->where('sales_records.transact_date', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->where('sales_records.transact_date', '<=', $endDate);
        }
        if (!empty($profileFilter)) {
            $baseQuery->where('sales_records.hotspot_profile_id', $profileFilter);
        }
        if (!empty($resellerFilter)) {
            $baseQuery->where('sales_records.reseller_id', $resellerFilter);
        }

        // Summary totals
        $summary = (clone $baseQuery)->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(amount), 0) as total_revenue,
            COALESCE(AVG(amount), 0) as avg_transaction
        ')->first();

        // Daily revenue trend
        $dailyRevenue = (clone $baseQuery)->selectRaw("
            DATE(transact_date) as date,
            SUM(amount) as revenue,
            COUNT(*) as transactions
        ")
            ->groupByRaw('DATE(transact_date)')
            ->orderByRaw('DATE(transact_date)')
            ->get();

        // Revenue by profile -- use profile_name column directly (hotspot_profile_id is NULL for all records)
        $revenueByProfile = (clone $baseQuery)
            ->selectRaw("
                COALESCE(sales_records.profile_name, 'Unknown') as profile_name,
                SUM(sales_records.amount) as revenue,
                COUNT(*) as transactions
            ")
            ->groupByRaw("COALESCE(sales_records.profile_name, 'Unknown')")
            ->orderByRaw('SUM(sales_records.amount) DESC')
            ->get();

        // Revenue by reseller
        $revenueByReseller = (clone $baseQuery)
            ->leftJoin('resellers', 'resellers.id', '=', 'sales_records.reseller_id')
            ->selectRaw("
                COALESCE(resellers.name, 'Direct Sales') as reseller_name,
                SUM(sales_records.amount) as revenue,
                COUNT(*) as transactions
            ")
            ->groupByRaw("COALESCE(resellers.name, 'Direct Sales')")
            ->orderByRaw('SUM(sales_records.amount) DESC')
            ->get();

        // Hourly distribution
        $hourlyDistribution = (clone $baseQuery)->selectRaw("
            EXTRACT(HOUR FROM transact_date)::int as hour,
            COUNT(*) as transactions,
            SUM(amount) as revenue
        ")
            ->groupByRaw('EXTRACT(HOUR FROM transact_date)')
            ->orderByRaw('EXTRACT(HOUR FROM transact_date)')
            ->get();

        // Recent transactions (last 100)
        $recentTransactions = (clone $baseQuery)
            ->leftJoin('hotspot_profiles', 'hotspot_profiles.id', '=', 'sales_records.hotspot_profile_id')
            ->leftJoin('resellers', 'resellers.id', '=', 'sales_records.reseller_id')
            ->select(
                'sales_records.code',
                'sales_records.amount',
                'sales_records.transact_date',
                'sales_records.mac_address',
                'sales_records.ip_address',
                'hotspot_profiles.name as profile_name',
                'resellers.name as reseller_name',
            )
            ->orderBy('sales_records.transact_date', 'DESC')
            ->limit(100)
            ->get();

        // Build filter description for header
        $filterDescription = $this->buildFilterDescription($dateRange, $startDate, $endDate, $profileFilter, $resellerFilter, $user);

        // Available filter options for the filter bar
        $profiles = DB::table('hotspot_profiles')->orderBy('name')->get(['id', 'name']);
        $resellers = DB::table('resellers')->orderBy('name')->get(['id', 'name']);

        return view('reports.sales-report', compact(
            'summary',
            'dailyRevenue',
            'revenueByProfile',
            'revenueByReseller',
            'hourlyDistribution',
            'recentTransactions',
            'filterDescription',
            'user',
            'profiles',
            'resellers',
            'dateRange',
            'profileFilter',
            'resellerFilter',
            'dateFrom',
            'dateTo',
        ));
    }

    private function getDateRange(string $dateRange, ?string $dateFrom, ?string $dateTo): array
    {
        $now = Carbon::now();

        return match ($dateRange) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'custom' => [
                $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
                $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    private function buildFilterDescription(string $dateRange, $startDate, $endDate, $profileFilter, $resellerFilter, $user): string
    {
        $parts = [];

        if ($dateRange !== 'all') {
            $label = str_replace('_', ' ', ucfirst($dateRange));
            if ($startDate && $endDate) {
                $parts[] = "{$label}: " . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
            } else {
                $parts[] = $label;
            }
        } else {
            $parts[] = 'All Time';
        }

        if ($profileFilter) {
            $profileName = DB::table('hotspot_profiles')->where('id', $profileFilter)->value('name');
            if ($profileName) $parts[] = "Profile: {$profileName}";
        }

        if ($resellerFilter) {
            $resellerName = DB::table('resellers')->where('id', $resellerFilter)->value('name');
            if ($resellerName) $parts[] = "Reseller: {$resellerName}";
        }

        return implode(' | ', $parts);
    }
}
