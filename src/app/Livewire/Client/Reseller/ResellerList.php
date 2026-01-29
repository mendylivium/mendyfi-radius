<?php

namespace App\Livewire\Client\Reseller;

use Livewire\Component;
use App\Models\Reseller;
use App\Traits\BasicHelper;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Models\HotspotVouchers;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class ResellerList extends Component
{
    use BasicHelper;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    
    public $searchStr = "";

    // Multi-select properties
    public $selectedItems = [];
    public $selectAll = false;

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->resellers->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function updatedSelectedItems()
    {
        $this->selectAll = count($this->selectedItems) === $this->resellers->count();
    }

    public function bulkDelete()
    {
        if (empty($this->selectedItems)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No resellers selected!'
            ]);
        }

        // Delete associated vouchers first
        HotspotVouchers::whereIn('reseller_id', $this->selectedItems)
            ->where('user_id', $this->user->id)
            ->delete();

        // Delete resellers
        Reseller::whereIn('id', $this->selectedItems)
            ->where('user_id', $this->user->id)
            ->delete();

        $count = count($this->selectedItems);
        $this->selectedItems = [];
        $this->selectAll = false;

        $this->showFlash([
            'type' => 'warning',
            'message' => "{$count} reseller(s) and their vouchers deleted!"
        ]);
    }

    public function bulkEnable()
    {
        if (empty($this->selectedItems)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No resellers selected!'
            ]);
        }

        Reseller::whereIn('id', $this->selectedItems)
            ->where('user_id', $this->user->id)
            ->update(['status' => 'active']);

        $count = count($this->selectedItems);
        $this->selectedItems = [];
        $this->selectAll = false;

        $this->showFlash([
            'type' => 'success',
            'message' => "{$count} reseller(s) enabled!"
        ]);
    }

    public function bulkDisable()
    {
        if (empty($this->selectedItems)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No resellers selected!'
            ]);
        }

        Reseller::whereIn('id', $this->selectedItems)
            ->where('user_id', $this->user->id)
            ->update(['status' => 'suspended']);

        $count = count($this->selectedItems);
        $this->selectedItems = [];
        $this->selectAll = false;

        $this->showFlash([
            'type' => 'warning',
            'message' => "{$count} reseller(s) suspended!"
        ]);
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function delete($id)
    {
        Reseller::query()
        ->where([
            'user_id'   =>  $this->user->id,
            'id'        =>  $id
        ])
        ->delete();

        HotspotVouchers::query()
        ->where([
            'user_id'       =>  $this->user->id,
            'reseller_id'   =>  $id
        ])
        ->delete();

        $this->showFlash([
            'type'      =>  'warning',
            'message'   =>  "Reseller #{$id} Deleted!"
        ]);
    }

    #[Computed()]
    public function resellers()
    {
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $currentWeekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
        $currentWeekEnd = Carbon::now()->endOfWeek()->format('Y-m-d');
        $currentMonth = Carbon::now()->month;
        $lastMonth = Carbon::now()->subMonth()->month;

        return Reseller::query()
        ->select(
            '*',
            DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM sales_records WHERE reseller_id = resellers.id) as total_sales"),
            DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM sales_records WHERE reseller_id = resellers.id AND DATE(transact_date) = '{$today}') as earnToday"),
            DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM sales_records WHERE reseller_id = resellers.id AND DATE(transact_date) = '{$yesterday}') as earnYesterday"),
            DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM sales_records WHERE reseller_id = resellers.id AND DATE(transact_date) >= '{$currentWeekStart}' AND DATE(transact_date) <= '{$currentWeekEnd}' ) as earnThisWeek"),
            DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM sales_records WHERE reseller_id = resellers.id AND MONTH(transact_date) = '{$currentMonth}') as earnThisMonth"),
            DB::raw("(SELECT COALESCE(SUM(amount), 0) FROM sales_records WHERE reseller_id = resellers.id AND MONTH(transact_date) = '{$lastMonth}') as earnLastMonth"),
            DB::raw('(SELECT count(`id`) FROM hotspot_vouchers WHERE `connected` = true AND `reseller_id` = `resellers`.`id`) as active_vouchers'),
            DB::raw('(SELECT count(`id`) FROM hotspot_vouchers WHERE `used_date` IS NULL AND `reseller_id` = `resellers`.`id`) as available_vouchers')
        )
        ->where(function($query){
            $query->where('user_id', $this->user->id);

            if(!empty($this->searchStr)) {
                $searchTerm = '%' . $this->searchStr . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('id', '=', $this->searchStr)
                      ->orWhere('email', 'like', $searchTerm)
                      ->orWhere('mobile', 'like', $searchTerm)
                      ->orWhere('address_name', 'like', $searchTerm);
                });
            }
        })
        // ->where([
        //     'user_id'   =>  $this->user->id
        // ])
        ->orderBy('id','DESC')
        ->paginate(20);
    }

    public function render()
    {
        return view('livewire.client.reseller.reseller-list')
        ->layout('components.layouts.app',[
            'pageName' => 'Reseller',
            'links' => ['Reseller', 'List']
        ]);
    }
}
