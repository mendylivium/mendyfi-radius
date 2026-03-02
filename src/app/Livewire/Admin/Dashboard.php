<?php

namespace App\Livewire\Admin;

use App\Models\Domain;
use App\Models\Tenant;
use Livewire\Component;
use App\Traits\BasicHelper;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class Dashboard extends Component
{
    use WithPagination;
    use BasicHelper;

    public string $searchStr = '';
    public string $statusFilter = 'all';
    public string $sortField = 'domains.created_at';
    public string $sortDirection = 'desc';
    public array $selected = [];

    // Reset pagination when filters change
    public function updatedSearchStr(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function bulkSuspend(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Domain::whereIn('tenant_id', $this->selected)->update(['status' => 'suspended']);

        $this->selected = [];
        $this->showFlash([
            'type' => 'warning',
            'message' => 'Selected users suspended.',
        ]);
    }

    public function bulkActivate(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Domain::whereIn('tenant_id', $this->selected)->update(['status' => 'active']);

        $this->selected = [];
        $this->showFlash([
            'type' => 'success',
            'message' => 'Selected users activated.',
        ]);
    }

    public function deleteTenant(string $id): void
    {
        $tenant = Tenant::find($id);

        if ($tenant) {
            $tenant->delete();
        }

        $this->showFlash([
            'type' => 'warning',
            'message' => 'User Deleted!',
        ]);
    }

    #[Computed()]
    public function totalCount(): int
    {
        return Tenant::whereHas('domains')->count();
    }

    // Keep backward-compatible alias used in the existing stat card
    #[Computed()]
    public function info(): int
    {
        return $this->totalCount;
    }

    #[Computed()]
    public function activeCount(): int
    {
        return Domain::where('status', 'active')->count();
    }

    #[Computed()]
    public function suspendedCount(): int
    {
        return Domain::where('status', 'suspended')->count();
    }

    #[Computed()]
    public function domainCount(): int
    {
        return Domain::count();
    }

    #[Computed()]
    public function myTenants()
    {
        $allowedSortFields = [
            'domains.username',
            'domains.domain',
            'domains.status',
            'domains.created_at',
        ];

        $sortField = in_array($this->sortField, $allowedSortFields, true)
            ? $this->sortField
            : 'domains.created_at';

        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Tenant::query()
            ->leftJoin('domains', 'tenants.id', 'domains.tenant_id')
            ->select('tenants.*', 'domains.domain', 'domains.username', 'domains.status', 'domains.created_at as domain_created_at')
            ->when($this->searchStr, function ($query) {
                $query->where(function ($q) {
                    $q->orWhere('domains.username', 'like', "%{$this->searchStr}%")
                      ->orWhere('domains.domain', 'like', "%{$this->searchStr}%");
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('domains.status', $this->statusFilter);
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('components.layouts.app', [
                'pageName' => 'My Users',
                'links' => ['Users', 'List'],
            ]);
    }
}
