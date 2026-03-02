<?php

namespace App\Livewire\Client\FairUsePolicy;

use Livewire\Component;
use App\Traits\BasicHelper;
use Livewire\WithPagination;
use App\Models\FairUsePolicy;
use Livewire\Attributes\Computed;

class FupList extends Component
{
    use BasicHelper;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    #[Computed()]
    public function policies()
    {
        return FairUsePolicy::query()
        ->paginate(10);
    }

    public function delete($id)
    {
        FairUsePolicy::query()
        ->where('id', $id)
        ->delete();

        return $this->showFlash([
            'type' => 'warning',
            'message' => "Fair Use Policy #$id has been deleted!"
        ]);
    }


    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function render()
    {
        return view('livewire.client.fair-use-policy.fup-list')
        ->layout('components.layouts.app',[
            'pageName' => 'Fair Use Policy',
            'links' => ['Fair Use Policy']
        ]);
    }
}
