<?php

namespace App\Livewire\Client\Hotspot\Template;

use Validator;
use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Livewire\WithPagination;
use App\Models\VoucherTemplate;
use Livewire\Attributes\Computed;

class CreateTemplate extends Component
{   
    use WithPagination;
    use RadiusHelper;
    use BasicHelper;

    public $sessionToken;
    protected $paginationTheme = 'tailwind';

    public $templateName;

    public $templateCss =<<<'HTML'
body {
    color: #000000;
    background-color: #FFFFFF;
    font-size: 14px;
    font-family: 'Helvetica', arial, sans-serif;
    margin: 0px;
    -webkit-print-color-adjust: exact;
}

table.voucher {
    display: inline-block;
    border: 2px solid black;
    margin: 2px;
}

@page {
    size: auto;
    margin-left: 7mm;
    margin-right: 3mm;
    margin-top: 9mm;
    margin-bottom: 3mm;
}

@media print {
    table {
        page-break-after: auto
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto
    }

    td {
        page-break-inside: avoid;
        page-break-after: auto
    }

    thead {
        display: table-header-group
    }

    tfoot {
        display: table-footer-group
    }
}

#num {
    float: right;
    display: inline-block;
}

.qrc {
    width: 30px;
    height: 30px;
    margin-top: 1px;
}

HTML;

    public $templateHtml = <<<'HTML'
<table class="voucher" style=" width: 160px;">
    <tbody>
        <tr style="background-color: black;">
            <td style="text-align: left; font-size: 14px; font-weight:bold;color: white;">
                <span x-text="voucher.profile"></span><span x-text="voucher.id" id="num"></span>
            </td>
        </tr>
        <tr>
            <td>
                <table style=" text-align: center; width: 150px;">
                    <tbody>
                        <tr style="color: black; font-size: 11px;">
                            <td>
                                <table style="width:100%;">
                                    <tr>
                                        <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=' + voucher.code" />
                                    </tr>
                                    <tr>
                                        <td>VOUCHER CODE</td>
                                    </tr>
                                    <tr style="color: black; font-size: 14px;">
                                        <td style="width:100%; border: 1px solid black; font-weight:bold;">
                                            <span x-text="voucher.code"></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"
                                            style="border: 1px solid black; font-weight:bold;">
                                            Price <span x-text="voucher.price"></span></td>
                                    </tr>
                                    <tr x-show="voucher.time_limit">
                                        <td colspan="2"
                                            style="border: 1px solid black; font-weight:bold;">
                                            Time Limit: <span x-text="voucher.time_limit"></span>
                                        </td>
                                    </tr>
                                    <tr x-show="voucher.validity">
                                        <td colspan="2"
                                            style="border: 1px solid black; font-weight:bold;">
                                            Validity: <span x-text="voucher.validity"></span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
HTML;

    public function create($name,$css,$html)
    {
        
        // $this->validate([
        //     'name'  =>  'required|min:5|max:50',
        //     'css'  =>  'required|max:2950',
        //     'html'  =>  'required|max:2950'
        // ]);

        $validator = Validator::make([
            'name'  =>  $name,
            'css'   =>  $css,
            'html'  =>  $html
        ],[
            'name'  =>  'required|min:5|max:50',
            'css'  =>  'required|max:2950',
            'html'  =>  'required|max:2950'
        ]);

        if($validator->fails()) {
            return $this->addError('otherError',$validator->messages()->first());
        }

        if(!$this->user) {
            return $this->addError('otherError','Invalid Session');
        }

        VoucherTemplate::create([
            'user_id' => $this->user->id,
            'name'  =>  $name,
            'head'  =>  $css,
            'body'  =>  $html
        ]);

        return redirect()->route('client.voucher.template')
            ->with([
                'type'      =>  'success',
                'message'   =>  "Create \"{$this->templateName}\" Successfully!"
            ]);

    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function render()
    {
        return view('livewire.client.hotspot.template.create-template')
        ->layout('components.layouts.app',[
            'pageName' => 'Voucher Template',
            'links' => ['Hotspot', 'Voucher', 'Create']
        ]);
    }
}
