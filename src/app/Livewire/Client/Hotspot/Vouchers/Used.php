<?php
namespace App\Livewire\Client\Hotspot\Vouchers;

use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Models\HotspotVouchers;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Response;

class Used extends Component
{
    use BasicHelper;
    use WithPagination;
    use RadiusHelper;

    protected $paginationTheme = 'bootstrap';
    public $search = '';
    public $dateRange = 'all';
    public $dateFrom = '';
    public $dateTo = '';

    // Multi-select properties
    public $selectedItems = [];
    public $selectAll = false;

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->vouchers->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function updatedSelectedItems()
    {
        $this->selectAll = count($this->selectedItems) === $this->vouchers->count();
    }

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

    public function bulkDelete()
    {
        if (empty($this->selectedItems)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No vouchers selected!'
            ]);
        }

        HotspotVouchers::whereIn('id', $this->selectedItems)
            ->where('user_id', $this->user->id)
            ->delete();

        $count = count($this->selectedItems);
        $this->selectedItems = [];
        $this->selectAll = false;

        $this->showFlash([
            'type' => 'danger',
            'message' => "{$count} used voucher(s) deleted!"
        ]);
    }

    public function bulkExport()
    {
        if (empty($this->selectedItems)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No vouchers selected!'
            ]);
        }

        $vouchers = HotspotVouchers::leftJoin('hotspot_profiles', 'hotspot_profiles.id', 'hotspot_vouchers.hotspot_profile_id')
            ->whereIn('hotspot_vouchers.id', $this->selectedItems)
            ->where('hotspot_vouchers.user_id', $this->user->id)
            ->select(
                'hotspot_vouchers.code',
                'hotspot_vouchers.mac_address',
                'hotspot_vouchers.ip_address',
                'hotspot_vouchers.router_ip',
                'hotspot_vouchers.generation_date',
                'hotspot_vouchers.used_date',
                'hotspot_vouchers.expire_date',
                'hotspot_vouchers.data_credit',
                'hotspot_vouchers.uptime_credit',
                'hotspot_vouchers.session_download',
                'hotspot_vouchers.session_upload',
                'hotspot_profiles.name as profile_name',
                'hotspot_profiles.price',
                'hotspot_profiles.data_limit'
            )
            ->get();

        $csv = "Code,Profile,Price,MAC Address,IP Address,Router IP,Generated,Used Date,Expire Date,Data Used,Data Remaining,Time Remaining,Download,Upload\n";
        foreach ($vouchers as $v) {
            $dataUsed = $v->data_limit > 0 ? $this->convertBytes($v->data_limit - $v->data_credit) : 'N/A';
            $csv .= "\"{$v->code}\",\"{$v->profile_name}\",\"{$v->price}\",\"{$v->mac_address}\",\"{$v->ip_address}\",\"{$v->router_ip}\",\"{$v->generation_date}\",\"{$v->used_date}\",\"{$v->expire_date}\",\"{$dataUsed}\",\"{$this->convertBytes($v->data_credit)}\",\"{$this->convertSeconds($v->uptime_credit)}\",\"{$this->convertBytes($v->session_download)}\",\"{$this->convertBytes($v->session_upload)}\"\n";
        }

        $filename = 'used_vouchers_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function clearFilters()
    {
        $this->dateRange = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->search = '';
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getUsageDuration($voucher)
    {
        if (!$voucher->used_date) {
            return 'N/A';
        }

        $start = Carbon::parse($voucher->used_date);
        $end = $voucher->expire_date ? Carbon::parse($voucher->expire_date) : Carbon::now();
        
        // If not connected and expired, use expiry as end
        if (!$voucher->connected && $voucher->expire_date) {
            $end = Carbon::parse($voucher->expire_date);
        }

        $diff = $start->diff($end);
        
        $parts = [];
        if ($diff->d > 0) $parts[] = $diff->d . 'd';
        if ($diff->h > 0) $parts[] = $diff->h . 'h';
        if ($diff->i > 0) $parts[] = $diff->i . 'm';
        if (empty($parts)) $parts[] = '<1m';

        return implode(' ', $parts);
    }

    public function getDataConsumed($voucher)
    {
        if (!$voucher->data_limit || $voucher->data_limit <= 0) {
            return ['consumed' => $voucher->session_download + $voucher->session_upload, 'percentage' => null];
        }

        $consumed = $voucher->data_limit - ($voucher->data_credit ?? 0);
        $percentage = ($consumed / $voucher->data_limit) * 100;
        
        return ['consumed' => max(0, $consumed), 'percentage' => min(100, max(0, $percentage))];
    }

    public function getVoucherStatus($voucher)
    {
        if ($voucher->connected) {
            return ['status' => 'Active', 'color' => 'success', 'icon' => 'wifi'];
        }
        
        if ($voucher->expire_date && Carbon::parse($voucher->expire_date)->lt(Carbon::now())) {
            return ['status' => 'Expired', 'color' => 'secondary', 'icon' => 'clock'];
        }

        if ($voucher->data_limit > 0 && $voucher->data_credit <= 0) {
            return ['status' => 'Data Depleted', 'color' => 'warning', 'icon' => 'database'];
        }

        if ($voucher->uptime_limit > 0 && $voucher->uptime_credit <= 0) {
            return ['status' => 'Time Depleted', 'color' => 'warning', 'icon' => 'clock'];
        }

        return ['status' => 'Used', 'color' => 'info', 'icon' => 'check'];
    }

    #[Computed()]
    public function vouchers()
    {
        $query = HotspotVouchers::leftJoin('hotspot_profiles','hotspot_profiles.id','hotspot_vouchers.hotspot_profile_id')
            ->where([
                'hotspot_vouchers.user_id' => $this->user->id
            ])
            ->where('hotspot_vouchers.used_date','<>',null);

        // Apply date range filter
        [$dateFrom, $dateTo] = $this->getDateRange();
        if ($dateFrom) {
            $query->where('hotspot_vouchers.used_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('hotspot_vouchers.used_date', '<=', $dateTo);
        }

        // Apply search filter
        $query->when($this->search, function($query) {
            $query->where(function($query) {
                $query->where('hotspot_vouchers.code', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_vouchers.batch_code', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_profiles.name', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_vouchers.mac_address', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_vouchers.ip_address', 'like', '%' . $this->search . '%');
            });
        });

        return $query->select(
            'hotspot_vouchers.*',
            'hotspot_profiles.name as profile_name',
            'hotspot_profiles.price',
            'hotspot_profiles.uptime_limit',
            'hotspot_profiles.data_limit',
            'hotspot_profiles.max_download',
            'hotspot_profiles.max_upload',
            'hotspot_profiles.validity'
        )
        ->orderBy('hotspot_vouchers.used_date','DESC')
        ->paginate(15);
    }

    #[Computed()]
    public function usageStats()
    {
        $query = HotspotVouchers::where([
            'user_id' => $this->user->id
        ])
        ->where('used_date', '<>', null);

        // Apply same filters
        [$dateFrom, $dateTo] = $this->getDateRange();
        if ($dateFrom) {
            $query->where('used_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('used_date', '<=', $dateTo);
        }

        return $query->selectRaw('
            COUNT(*) as total_used,
            COALESCE(SUM(session_download), 0) as total_download,
            COALESCE(SUM(session_upload), 0) as total_upload
        ')->first();
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function render()
    {
        return view('livewire.client.hotspot.vouchers.used')
        ->layout('components.layouts.app',[
            'pageName' => 'Used Voucher',
            'links' => ['Hotspot', 'Used']
        ]);
    }
}
