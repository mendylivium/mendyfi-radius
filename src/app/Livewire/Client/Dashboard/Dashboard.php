<?php

namespace App\Livewire\Client\Dashboard;

use App\Models\User;
use Livewire\Component;
use App\Models\SalesRecord;
use App\Traits\BasicHelper;
use Livewire\WithPagination;
use App\Models\HotspotProfile;
use Illuminate\Support\Carbon;
use App\Models\HotspotVouchers;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Dashboard extends Component
{
    
    use WithPagination;
    use BasicHelper;
    protected $paginationTheme = 'bootstrap';

    public $lowStockThreshold = 10;

    #[Computed()]
    public function user()
    {
        return auth()->user()->addSelect(
            '*',
            DB::raw('(SELECT count(`id`) FROM hotspot_vouchers WHERE `connected` = true AND `user_id` = `users`.`id`) as active_vouchers'),
            DB::raw('(SELECT count(`id`) FROM hotspot_vouchers WHERE `used_date` IS NULL AND `user_id` = `users`.`id`) as available_vouchers')
        )->first();
    }

    #[Computed()]
    public function sales()
    {
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $currentWeekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
        $currentWeekEnd = Carbon::now()->endOfWeek()->format('Y-m-d');
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
        $currentMonth = Carbon::now()->month;
        $lastMonth = Carbon::now()->subMonth()->month;
        $lastMonthYear = Carbon::now()->subMonth()->year;

        return SalesRecord::query()
        ->where('user_id',$this->user->id)
        ->selectRaw('
        COALESCE(SUM(CASE WHEN DATE(transact_date) = ? THEN amount ELSE 0 END), 0) as earnToday,
        COALESCE(SUM(CASE WHEN DATE(transact_date) = ? THEN amount ELSE 0 END), 0) as earnYesterday,
        COALESCE(SUM(CASE WHEN DATE(transact_date) >= ? AND DATE(transact_date) <= ? THEN amount ELSE 0 END), 0) as earnThisWeek,
        COALESCE(SUM(CASE WHEN DATE(transact_date) >= ? AND DATE(transact_date) <= ? THEN amount ELSE 0 END), 0) as earnLastWeek,
        COALESCE(SUM(CASE WHEN MONTH(transact_date) = ? AND YEAR(transact_date) = YEAR(NOW()) THEN amount ELSE 0 END), 0) as earnThisMonth,
        COALESCE(SUM(CASE WHEN MONTH(transact_date) = ? AND YEAR(transact_date) = ? THEN amount ELSE 0 END), 0) as earnLastMonth',
        [$today, $yesterday, $currentWeekStart, $currentWeekEnd, $lastWeekStart, $lastWeekEnd, $currentMonth, $lastMonth, $lastMonthYear])
        ->first();
    }

    #[Computed()]
    public function vouchers()
    {
        return HotspotProfile::where([
            'user_id' => $this->user->id,
        ])
        ->select(
            'id',
            'name',
            DB::raw("(SELECT count(`id`) FROM `hotspot_vouchers` WHERE `hotspot_profile_id` = `hotspot_profiles`.`id` AND `used_date` is NULL) as `stock`"),
        )
        ->paginate(10);
    }

    #[Computed()]
    public function lowStockProfiles()
    {
        return HotspotProfile::where([
            'user_id' => $this->user->id,
        ])
        ->select(
            'id',
            'name',
            DB::raw("(SELECT count(`id`) FROM `hotspot_vouchers` WHERE `hotspot_profile_id` = `hotspot_profiles`.`id` AND `used_date` is NULL) as `stock`"),
        )
        ->having('stock', '<', $this->lowStockThreshold)
        ->orderBy('stock', 'ASC')
        ->get();
    }

    #[Computed()]
    public function vouchersGeneratedToday()
    {
        return HotspotVouchers::where('user_id', $this->user->id)
            ->whereDate('generation_date', Carbon::today())
            ->count();
    }

    #[Computed()]
    public function salesToday()
    {
        return SalesRecord::where('user_id', $this->user->id)
            ->whereDate('transact_date', Carbon::today())
            ->count();
    }

    public function calculatePercentChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        return view('livewire.client.dashboard.dashboard')
        ->layout('components.layouts.app',[
            'pageName' => 'Dashboard',
            'links' => ['Dashboard']
        ]);
    }
}
