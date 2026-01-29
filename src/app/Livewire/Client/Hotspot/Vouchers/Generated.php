<?php

namespace App\Livewire\Client\Hotspot\Vouchers;

use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Livewire\WithPagination;
use App\Models\HotspotVouchers;
use App\Models\VoucherTemplate;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class Generated extends Component
{
    use WithPagination;
    use RadiusHelper;
    use BasicHelper;

    protected $paginationTheme = 'bootstrap';
    public $searchVC = '',
    $searchBATCH = '';

    // Multi-select properties for vouchers
    public $selectedVouchers = [];
    public $selectAllVouchers = false;

    // Multi-select properties for batches
    public $selectedBatches = [];
    public $selectAllBatches = false;

    // Selected batch for printing
    public $selectedBatchForPrint = null;

    public function updatedSelectAllVouchers($value)
    {
        if ($value) {
            $this->selectedVouchers = $this->vouchers->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedVouchers = [];
        }
    }

    public function updatedSelectedVouchers()
    {
        $this->selectAllVouchers = count($this->selectedVouchers) === $this->vouchers->count();
    }

    public function updatedSelectAllBatches($value)
    {
        if ($value) {
            $this->selectedBatches = $this->batches->pluck('batch_code')->map(fn($code) => (string) $code)->toArray();
        } else {
            $this->selectedBatches = [];
        }
    }

    public function updatedSelectedBatches()
    {
        $this->selectAllBatches = count($this->selectedBatches) === $this->batches->count();
    }

    public function bulkDeleteVouchers()
    {
        if (empty($this->selectedVouchers)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No vouchers selected!'
            ]);
        }

        HotspotVouchers::whereIn('id', $this->selectedVouchers)
            ->where('user_id', $this->user->id)
            ->delete();

        $count = count($this->selectedVouchers);
        $this->selectedVouchers = [];
        $this->selectAllVouchers = false;

        $this->showFlash([
            'type' => 'danger',
            'message' => "{$count} voucher(s) deleted!"
        ]);
    }

    public function bulkDeleteBatches()
    {
        if (empty($this->selectedBatches)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No batches selected!'
            ]);
        }

        HotspotVouchers::whereIn('batch_code', $this->selectedBatches)
            ->where('user_id', $this->user->id)
            ->delete();

        $count = count($this->selectedBatches);
        $this->selectedBatches = [];
        $this->selectAllBatches = false;

        $this->showFlash([
            'type' => 'danger',
            'message' => "{$count} batch(es) deleted!"
        ]);
    }

    public function getSelectedVouchersForPrint()
    {
        return implode(',', $this->selectedVouchers);
    }

    public function getSelectedBatchesForPrint()
    {
        return implode(',', $this->selectedBatches);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function deleteBatch($batch)
    {
        HotspotVouchers::where([
            'user_id'           =>  $this->user->id,
            'batch_code'    =>  $batch
        ])
        ->delete();

        $this->showFLash([
            'type'      =>  'danger',
            'message'   =>  'Vouchers Deleted!'
        ]);
    }

    public function deleteVoucher($id)
    {
        HotspotVouchers::where([
            'id'    =>  $id
        ])
        ->delete();

        $this->showFlash([
            'type'      =>  'danger',
            'message'   =>  'Voucher has been Deleted!'
        ]);
    }

    #[Computed()]
    public function vouchers()
    {
        return HotspotVouchers::query()
        ->leftJoin('hotspot_profiles','hotspot_profiles.id','hotspot_vouchers.hotspot_profile_id')
        ->where([
            'hotspot_profiles.user_id'      =>  $this->user->id,
            'hotspot_vouchers.used_date'    =>  null,
        ])
        ->when($this->searchVC, function($query) {
            $query->where(function($query) {
            $query->where('hotspot_vouchers.code', 'like', '%' . $this->searchVC . '%')
                    ->orWhere('hotspot_vouchers.batch_code', 'like', '%' . $this->searchVC . '%')
                    ->orWhere('hotspot_profiles.name', 'like', '%' . $this->searchVC . '%');
            });
        })
        ->select(
            'hotspot_vouchers.*',
            'hotspot_profiles.name as profile_name',
            'hotspot_profiles.price',
            'hotspot_profiles.uptime_limit',
            'hotspot_profiles.data_limit',
            'hotspot_profiles.max_download',
            'hotspot_profiles.max_upload',
            'hotspot_profiles.validity'
        )
        ->orderBy('hotspot_vouchers.id','DESC')
        ->paginate(10);
    }

    #[Computed()]
    public function templates()
    {
        return VoucherTemplate::where([
            'user_id' => $this->user->id
        ])
        ->get();
    }

    #[Computed()]
    public function batches()
    {
        return HotspotVouchers::query()
        ->leftJoin('hotspot_profiles','hotspot_profiles.id','hotspot_vouchers.hotspot_profile_id')
        ->leftJoin('resellers','resellers.id','hotspot_vouchers.reseller_id')
        ->select(
            'hotspot_vouchers.generation_date',
            'hotspot_profiles.name',
            'hotspot_profiles.price',
            'hotspot_vouchers.batch_code',
            'resellers.name as reseller_name',
            DB::raw('count(*) as count')
        )
        ->where('hotspot_vouchers.batch_code','<>',null)
        ->where('hotspot_vouchers.user_id', $this->user->id)
        ->where('hotspot_vouchers.used_date',null)
        ->when($this->searchBATCH, function($query) {
            $query->where(function($query) {
            $query->where('hotspot_vouchers.batch_code', 'like', '%' . $this->searchBATCH . '%')
                    ->orWhere('hotspot_profiles.name', 'like', '%' . $this->searchBATCH . '%')
                    ->orWhere('resellers.name', 'like', '%' . $this->searchBATCH . '%');
            });
        })
        ->groupBy(
            'generation_date',
            'name',
            'price',
            'batch_code',
            'reseller_name'
        )
        ->orderBy('hotspot_vouchers.id','DESC')
        ->paginate(10,['*'],'batch');
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function render()
    {
        return view('livewire.client.hotspot.vouchers.generated')
        ->layout('components.layouts.app',[
            'pageName' => 'Generated',
            'links' => ['Hotspot', 'Generated Voucher']
        ]);
    }
}
