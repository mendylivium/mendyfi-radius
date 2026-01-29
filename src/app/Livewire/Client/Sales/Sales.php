<?php

namespace App\Livewire\Client\Sales;

use App\Models\User;
use Livewire\Component;
use App\Models\Reseller;
use App\Models\SalesRecord;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Livewire\WithPagination;
use App\Models\HotspotProfile;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class Sales extends Component
{
    use BasicHelper;
    use WithPagination;
    use RadiusHelper;
    
    protected $paginationTheme = 'bootstrap';

    // Filters
    public $dateRange = 'all';
    public $dateFrom = '';
    public $dateTo = '';
    public $profileFilter = '';
    public $resellerFilter = '';
    public $search = '';

    public function updatedDateRange()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedProfileFilter()
    {
        $this->resetPage();
    }

    public function updatedResellerFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function applyDateFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->dateRange = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->profileFilter = '';
        $this->resellerFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    protected function getDateRange()
    {
        $now = Carbon::now();
        
        switch ($this->dateRange) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'yesterday':
                return [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()];
            case 'this_week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'last_week':
                return [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()];
            case 'this_month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'last_month':
                return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
            case 'custom':
                $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
                $to = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;
                return [$from, $to];
            default:
                return [null, null];
        }
    }

    #[Computed()]
    public function sales()
    {
        $query = SalesRecord::query()
            ->leftJoin('hotspot_profiles', 'hotspot_profiles.id', '=', 'sales_records.hotspot_profile_id')
            ->leftJoin('resellers', 'resellers.id', '=', 'sales_records.reseller_id')
            ->where('sales_records.user_id', $this->user->id)
            ->select(
                'sales_records.*',
                'hotspot_profiles.name as profile_name',
                'resellers.name as reseller_name'
            );

        // Apply date range filter
        [$dateFrom, $dateTo] = $this->getDateRange();
        if ($dateFrom) {
            $query->where('sales_records.transact_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('sales_records.transact_date', '<=', $dateTo);
        }

        // Apply profile filter
        if (!empty($this->profileFilter)) {
            $query->where('sales_records.hotspot_profile_id', $this->profileFilter);
        }

        // Apply reseller filter
        if (!empty($this->resellerFilter)) {
            $query->where('sales_records.reseller_id', $this->resellerFilter);
        }

        // Apply search filter
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('sales_records.code', 'like', $searchTerm)
                  ->orWhere('sales_records.mac_address', 'like', $searchTerm)
                  ->orWhere('sales_records.ip_address', 'like', $searchTerm);
            });
        }

        return $query->orderBy('sales_records.transact_date', 'DESC')
            ->paginate(20);
    }

    #[Computed()]
    public function salesTotals()
    {
        $query = SalesRecord::query()
            ->where('user_id', $this->user->id);

        // Apply same filters as sales query
        [$dateFrom, $dateTo] = $this->getDateRange();
        if ($dateFrom) {
            $query->where('transact_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('transact_date', '<=', $dateTo);
        }

        if (!empty($this->profileFilter)) {
            $query->where('hotspot_profile_id', $this->profileFilter);
        }

        if (!empty($this->resellerFilter)) {
            $query->where('reseller_id', $this->resellerFilter);
        }

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('code', 'like', $searchTerm)
                  ->orWhere('mac_address', 'like', $searchTerm)
                  ->orWhere('ip_address', 'like', $searchTerm);
            });
        }

        return $query->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(amount), 0) as total_amount
        ')->first();
    }

    #[Computed()]
    public function profiles()
    {
        return HotspotProfile::where('user_id', $this->user->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed()]
    public function resellers()
    {
        return Reseller::where('user_id', $this->user->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function exportCsv()
    {
        $query = SalesRecord::query()
            ->leftJoin('hotspot_profiles', 'hotspot_profiles.id', '=', 'sales_records.hotspot_profile_id')
            ->leftJoin('resellers', 'resellers.id', '=', 'sales_records.reseller_id')
            ->where('sales_records.user_id', $this->user->id)
            ->select(
                'sales_records.*',
                'hotspot_profiles.name as profile_name',
                'resellers.name as reseller_name'
            );

        // Apply same filters
        [$dateFrom, $dateTo] = $this->getDateRange();
        if ($dateFrom) {
            $query->where('sales_records.transact_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('sales_records.transact_date', '<=', $dateTo);
        }

        if (!empty($this->profileFilter)) {
            $query->where('sales_records.hotspot_profile_id', $this->profileFilter);
        }

        if (!empty($this->resellerFilter)) {
            $query->where('sales_records.reseller_id', $this->resellerFilter);
        }

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('sales_records.code', 'like', $searchTerm)
                  ->orWhere('sales_records.mac_address', 'like', $searchTerm)
                  ->orWhere('sales_records.ip_address', 'like', $searchTerm);
            });
        }

        $sales = $query->orderBy('sales_records.transact_date', 'DESC')->get();

        $csv = "Voucher Code,Profile,Reseller,MAC Address,IP Address,Router IP,Amount,Transaction Date\n";
        foreach ($sales as $sale) {
            $csv .= "\"{$sale->code}\",\"{$sale->profile_name}\",\"{$sale->reseller_name}\",\"{$sale->mac_address}\",\"{$sale->ip_address}\",\"{$sale->router_ip}\",\"{$sale->amount}\",\"{$sale->transact_date}\"\n";
        }

        $filename = 'sales_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('livewire.client.sales.sales')
        ->layout('components.layouts.app',[
            'pageName' => 'Sales',
            'links' => ['Sales', 'Records']
        ]);
    }
}
