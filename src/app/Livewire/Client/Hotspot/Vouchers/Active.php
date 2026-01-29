<?php
namespace App\Livewire\Client\Hotspot\Vouchers;
use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Livewire\WithPagination;
use App\Models\HotspotVouchers;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class Active extends Component
{
    use WithPagination;
    use RadiusHelper;
    use BasicHelper;

    protected $paginationTheme = 'bootstrap';
    public $search = '';
    public $autoRefresh = false;

    // Multi-select properties
    public $selectedItems = [];
    public $selectAll = false;

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->voucher->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function updatedSelectedItems()
    {
        $this->selectAll = count($this->selectedItems) === $this->voucher->count();
    }

    public function bulkDisconnect()
    {
        if (empty($this->selectedItems)) {
            return $this->showFlash([
                'type' => 'warning',
                'message' => 'No vouchers selected!'
            ]);
        }

        $disconnected = 0;
        $failed = 0;

        foreach ($this->selectedItems as $id) {
            $hotspotUser = HotspotVouchers::where([
                'id' => $id,
                'connected' => true
            ])->first();

            if ($hotspotUser) {
                $coaResult = $this->radiusCoa('disconnect', [
                    'User-Name' => $hotspotUser->code,
                    'Framed-IP-Address' => $hotspotUser->ip_address,
                ],
                $this->user->api_secret,
                $hotspotUser->router_ip);

                if ($coaResult) {
                    $disconnected++;
                } else {
                    $failed++;
                }
            }
        }

        $this->selectedItems = [];
        $this->selectAll = false;

        $this->showFlash([
            'type' => $failed > 0 ? 'warning' : 'success',
            'message' => "{$disconnected} user(s) disconnected" . ($failed > 0 ? ", {$failed} failed" : "")
        ]);
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
            'message' => "{$count} active voucher(s) deleted!"
        ]);
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function getTimeRemaining($voucher)
    {
        if (!$voucher->expire_date) {
            return null;
        }

        $now = Carbon::now();
        $expiry = Carbon::parse($voucher->expire_date);

        if ($now->gt($expiry)) {
            return ['text' => 'Expired', 'seconds' => 0];
        }

        $diff = $now->diff($expiry);
        $totalSeconds = $expiry->timestamp - $now->timestamp;

        $parts = [];
        if ($diff->d > 0) $parts[] = $diff->d . 'd';
        if ($diff->h > 0) $parts[] = $diff->h . 'h';
        if ($diff->i > 0) $parts[] = $diff->i . 'm';
        if (empty($parts)) $parts[] = $diff->s . 's';

        return ['text' => implode(' ', $parts), 'seconds' => $totalSeconds];
    }

    public function getTimePercentage($voucher)
    {
        if (!$voucher->expire_date || !$voucher->used_date) {
            return null;
        }

        $start = Carbon::parse($voucher->used_date);
        $end = Carbon::parse($voucher->expire_date);
        $now = Carbon::now();

        $total = $end->timestamp - $start->timestamp;
        $remaining = $end->timestamp - $now->timestamp;

        if ($total <= 0) return 0;
        if ($remaining <= 0) return 0;

        return min(100, max(0, ($remaining / $total) * 100));
    }

    public function getDataPercentage($voucher)
    {
        if (!$voucher->data_limit || $voucher->data_limit <= 0) {
            return null;
        }

        $remaining = $voucher->data_credit ?? 0;
        return min(100, max(0, ($remaining / $voucher->data_limit) * 100));
    }

    public function getStatusColor($percentage)
    {
        if ($percentage === null) return 'secondary';
        if ($percentage > 50) return 'success';
        if ($percentage > 25) return 'warning';
        return 'danger';
    }

    #[Computed()]
    public function voucher()
    {
        return HotspotVouchers::leftJoin('hotspot_profiles','hotspot_profiles.id','hotspot_vouchers.hotspot_profile_id')
        ->where([
            'hotspot_vouchers.user_id' => $this->user->id,
            'hotspot_vouchers.connected' => true
        ])
        ->where('hotspot_vouchers.used_date','<>',null)
        ->when($this->search, function($query) {
            $query->where(function($query) {
                $query->where('hotspot_vouchers.code', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_vouchers.mac_address', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_vouchers.ip_address', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_vouchers.router_ip', 'like', '%' . $this->search . '%')
                      ->orWhere('hotspot_profiles.name', 'like', '%' . $this->search . '%');
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

    public function disconnect($id)
    {
        $hotspotUser = HotspotVouchers::query()
        ->where([
            'id' => $id,
            'connected' => true
        ])->first();

        if(!$hotspotUser) {
            return $this->showFlash([
                'type' => 'danger',
                'message' => 'User is not Active'
            ]);
        }

        $coaResult = $this->radiusCoa('disconnect',[
            'User-Name' => $hotspotUser->code,
            'Framed-IP-Address' => $hotspotUser->ip_address,
        ],
        $this->user->api_secret,
        $hotspotUser->router_ip);

        if(!$coaResult) {
            return $this->showFlash([
                'type' => 'danger',
                'message' => 'Fail to sent COA Request'
            ]);
        } else {
            return $this->showFlash([
                'type' => 'success',
                'message' => 'User Disconnected'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.client.hotspot.vouchers.active')
        ->layout('components.layouts.app',[
            'pageName' => 'Active Hotspot',
            'links' => ['Hotspot', 'Active Hotspot']
        ]);
    }
}
