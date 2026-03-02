<?php

namespace App\Livewire\Client\Hotspot\Template;

use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\VoucherTemplate as VCTemplate;

class GeneratedTemplate extends Component
{
    use WithPagination;
    use RadiusHelper;
    use BasicHelper;

    public $sessionToken;
    protected $paginationTheme = 'tailwind';

    public function deleteTemplate($id)
    {
        VCTemplate::where([
            'user_id'   => $this->user->id,
            'id'    =>  $id
        ])
        ->delete();
    }

    #[Computed()]
    public function templates()
    {
        return VCTemplate::where([
            'user_id' => $this->user->id
        ])
        ->paginate(20);
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function render()
    {
        return view('livewire.client.hotspot.template.generated-template')
        ->layout('components.layouts.app',[
            'pageName' => 'Voucher Template',
            'links' => ['Hotspot', 'Voucher', 'Template']
        ]);
    }
}
