<?php

namespace App\Livewire\Portal;

use App\Models\Domain;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\HotspotProfile;
use App\Models\HotspotVouchers;
use Livewire\Attributes\Computed;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class Tplink extends Component
{
    use WithPagination;
    protected $paginationTheme = 'tailwind';

    public $portalTitle = "Mendyfi";
    public $code, $password;
    public $tenantId = null;

    public function mount($tenant_id)
    {
    
        try {

            $domain = Domain::query()
            ->where([
                'username' => $tenant_id
            ])
            ->first();

            if(!$domain) {
                abort(404);
            }

            $tenant = tenancy()->initialize($domain->tenant_id); // or any other tenant-related operation

            $this->tenantId = $domain->tenant_id;

        } catch (TenantCouldNotBeIdentifiedById $e) {
            abort(404);
        }
        
    }

    public function verify()
    {
        $this->validate([
            'code' => 'required'
        ]);

        // $domain = Domain::query()
        // ->where([
        //     'domain' => request()->getHost()
        // ])
        // ->first();

        // if(!$domain) {
        //     return $this->addError('','Invalid Domain or Tenant not Found');
        // }

        tenancy()->initialize($this->tenantId);

        $code = HotspotVouchers::query()
        ->where([
            'code' => $this->code
        ])
        ->first();

        if(!$code) {
            return $this->addError('','Invalid Voucher Code');
        }
        
        return $this->dispatch('submit');

    }

    #[Computed()]
    public function rates()
    {
        tenancy()->initialize($this->tenantId);

        return HotspotProfile::query()
        ->where('price','>',0)
        ->paginate(10);
    }

    public function render()
    {
        return view('livewire.portal.tplink')
        ->layout('components.layouts.portal');
    }
}
