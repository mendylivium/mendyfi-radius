<?php

namespace App\Livewire\Admin;

use App\Models\Domain;
use App\Models\Tenant;
use Livewire\Component;
use App\Traits\BasicHelper;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class TenantDetail extends Component
{
    use BasicHelper;

    public string $tenantId;
    public string $newDomain = '';
    public bool $showAddDomain = false;

    public function mount(string $id)
    {
        $this->tenantId = $id;

        if (!Tenant::find($id)) {
            return redirect()->route('admin.dashboard')
                ->with(['type' => 'warning', 'message' => 'Tenant not found']);
        }
    }

    #[Computed()]
    public function tenant()
    {
        return Tenant::with('domains')->find($this->tenantId);
    }

    #[Computed()]
    public function primaryDomain()
    {
        return $this->tenant?->domains()->first();
    }

    #[Computed()]
    public function tenantStats(): array
    {
        $tenant = $this->tenant;
        if (!$tenant) return [];

        try {
            tenancy()->initialize($tenant);

            $stats = [
                'users'           => DB::table('users')->count(),
                'profiles'        => DB::table('hotspot_profiles')->count(),
                'vouchers_total'  => DB::table('vouchers')->count(),
                'vouchers_active' => DB::table('vouchers')->where('status', 'active')->count(),
                'resellers'       => DB::table('resellers')->count(),
                'templates'       => DB::table('voucher_templates')->count(),
            ];

            tenancy()->end();
            return $stats;
        } catch (\Exception $e) {
            tenancy()->end();
            return [];
        }
    }

    public function toggleStatus(): void
    {
        $domain = $this->primaryDomain;
        if (!$domain) return;

        $newStatus = $domain->status === 'active' ? 'suspended' : 'active';
        $domain->update(['status' => $newStatus]);

        $this->showFlash([
            'type'    => $newStatus === 'active' ? 'success' : 'warning',
            'message' => 'Tenant ' . ($newStatus === 'active' ? 'activated' : 'suspended') . '.',
        ]);
    }

    public function addDomain(): void
    {
        $this->validate([
            'newDomain' => 'required|unique:domains,domain|regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i',
        ]);

        $primary = $this->primaryDomain;
        if (!$primary) return;

        $this->tenant->domains()->create([
            'domain'   => $this->newDomain,
            'username' => $primary->username,
            'password' => $primary->password,
            'status'   => $primary->status,
        ]);

        $this->newDomain    = '';
        $this->showAddDomain = false;

        $this->showFlash([
            'type'    => 'success',
            'message' => 'Domain added successfully.',
        ]);
    }

    public function removeDomain(int $domainId): void
    {
        $domains = $this->tenant->domains;

        if ($domains->count() <= 1) {
            $this->showFlash([
                'type'    => 'error',
                'message' => 'Cannot remove the only domain.',
            ]);
            return;
        }

        Domain::where('id', $domainId)
            ->where('tenant_id', $this->tenantId)
            ->delete();

        $this->showFlash([
            'type'    => 'warning',
            'message' => 'Domain removed.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.tenant-detail')
            ->layout('components.layouts.app', [
                'pageName' => 'Tenant Details',
                'links'    => ['Users', 'Details'],
            ]);
    }
}
