<?php

namespace App\Livewire\Client\Hotspot\Template;

use Validator;
use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Models\VoucherTemplate;
use Livewire\Attributes\Computed;

class EditTemplate extends Component
{
    use BasicHelper;

    public $sessionToken;
    protected $paginationTheme = 'tailwind';

    public $templateName;
    public $templateCss;
    public $templateHtml;
    public $templateId;

    public function save($name,$css,$html)
    {
        $validator = Validator::make([
            'name'  =>  $name,
            'css'   =>  $css,
            'html'  =>  $html
        ],[
            'name'  =>  'required|min:5|max:50',
            'css'  =>  'required|max:2950',
            'html'  =>  'required|max:2950'
        ]);

   

        if(!$this->user) {
            return $this->addError('otherError','Invalid Session');
        }

        VoucherTemplate::where([
            'user_id' => $this->user->id,
            'id'  => $this->templateId
        ])
        ->update([            
            'name'  =>  $name,
            'head'  =>  $css,
            'body'  =>  $html
        ]);

        return redirect()->route('client.voucher.template')
        ->with([
            'type'      =>  'success',
            'message'   =>  "Modified \"{$this->templateName}\" Successfully!"
        ]);

    }

    #[Computed()]
    public function template()
    {
        return VoucherTemplate::where([
            'user_id'   =>  $this->user->id,
            'id'    =>  $this->templateId
        ])
        ->first();
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function mount($id)
    {
        $this->templateId = $id;

        if(!$this->template) {
            return redirect()->route('client.voucher.template')
            ->with([
                'type'      =>  'danger',
                'message'   =>  "Invalid Session"
            ]);
        }

        $this->templateName =   $this->template->name;
        $this->templateCss  =   $this->template->head;
        $this->templateHtml =   $this->template->body;
        
    }

    public function render()
    {
        return view('livewire.client.hotspot.template.edit-template')
        ->layout('components.layouts.app',[
            'pageName' => 'Voucher Template',
            'links' => ['Hotspot', 'Voucher', 'Edit']
        ]);
    }
}
