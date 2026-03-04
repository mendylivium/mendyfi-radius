<?php

namespace App\Livewire\Client\Profile;

use App\Models\User;
use Livewire\Component;
use App\Traits\BasicHelper;
use App\Models\FairUsePolicy;
use App\Models\HotspotProfile;
use App\Models\BindFairUsePolicy;
use Livewire\Attributes\Computed;

class EditProfile extends Component
{   
    use BasicHelper;

    public $profileId;
    public $profileName;
    public $profileDescription;
    public $profilePrice = 0;
    public $profileUptimeLimit = 0;
    public $profileValidity = 0;
    public $profileMaxDownload = 0;
    public $profileMaxUpload = 0;
    public $profileDataLimit = 0;
    public $policyId;

    public function save()
    {
        

        $this->validate([
            'profileName'           =>  'required|min:5|max:50',
            'profileDescription'    =>  'max:50',
            'profilePrice'          =>  'min:0|numeric',
            'profileUptimeLimit'    =>  'min:0|numeric',
            'profileValidity'       =>  'min:0|numeric',
            'profileMaxDownload'    =>  'min:0|numeric',
            'profileMaxUpload'      =>  'min:0|numeric',
            'profileDataLimit'      =>  'min:0|numeric'
        ]);

        HotspotProfile::where([
            'user_id'       =>  $this->user->id,
            'id'            =>  $this->profileId
        ])
        ->update([
            'name'          =>  $this->profileName,
            'description'   =>  $this->profileDescription,
            'price'         =>  $this->profilePrice,
            'uptime_limit'  =>  $this->profileUptimeLimit,
            'data_limit'    =>  $this->profileDataLimit,
            'max_download'  =>  $this->profileMaxDownload,
            'max_upload'    =>  $this->profileMaxUpload,
            'validity'      =>  $this->profileValidity

        ]);

        return redirect()->route('client.vouchers.profiles')
        ->with([
            'type'      =>  'success',
            'message'   =>  "Hotspot Profile \"{$this->profileName}\" modified!"
        ]);
    }

    public function addPolicy()
    {

        if($this->policyId == 0) {
            return $this->addError('policy_error', 'Please Select Policy');
        }

        $policy = FairUsePolicy::query()
        ->where([
            'id' => $this->policyId
        ])
        ->first();

        if(!$policy) {
            return $this->addError('policy_error', 'Policy is not Exist');
        }

        $exist = BindFairUsePolicy::query()
        ->where([
            'fair_user_policy_id' => $this->policyId,
            'hotspot_profile_id' => $this->profileId
        ])
        ->first();

        if($exist) {
            return $this->addError('policy_error', 'Policy already add this Policy');
        }

        // $bindedPolicy = BindFairUsePolicy::insert([
        //     'fair_user_policy_id' => $this->policyId,
        //     'hotspot_profile_id' => $this->profileId
        // ]);

        $bindedPolicy = new BindFairUsePolicy();

        $bindedPolicy->fair_user_policy_id = $this->policyId;
        $bindedPolicy->hotspot_profile_id = $this->profileId;

        $bindedPolicy->save();

        return $this->showFlash([
            'type' => 'success',
            'message' => "Policy #{$bindedPolicy->id} Binded!"
        ]);
    }

    public function unbindPolicy($id)
    {
        BindFairUsePolicy::query()
        ->where([
            'id' => $id
        ])
        ->delete();

        return $this->showFlash([
            'type' => 'warning',
            'message' => "Policy #$id Unbinded!"
        ]);
    }

    #[Computed()]
    public function availFairUserPolicies()
    {
        return FairUsePolicy::query()
        ->whereRaw('id NOT IN (SELECT id FROM bind_fair_use_policies WHERE bind_fair_use_policies.hotspot_profile_id = ?)', [$this->profileId])
        ->get();
    }

    #[Computed()]
    public function bindedFairUserPolicies()
    {
        return FairUsePolicy::query()
        ->leftJoin('bind_fair_use_policies','bind_fair_use_policies.fair_user_policy_id','fair_use_policies.id')
        ->where('bind_fair_use_policies.hotspot_profile_id', $this->profileId)
        ->paginate(10);
    }

    #[Computed()]
    public function profile()
    {
        return HotspotProfile::where([
            'user_id'   =>  $this->user->id,
            'id'        =>  $this->profileId
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
        $this->profileId = $id;
        

        if(!$this->profile) {
            return redirect()->route('client.vouchers.profiles')
            ->with([
                'type'      =>  'danger',
                'message'   =>  'Hotspot Profile not exist'
            ]);
        }

        $this->profileName          =   $this->profile->name;
        $this->profileDescription   =   $this->profile->description;
        $this->profilePrice         =   $this->profile->price;
        $this->profileUptimeLimit   =   $this->profile->uptime_limit;
        $this->profileDataLimit     =   $this->profile->data_limit;
        $this->profileMaxDownload   =   $this->profile->max_download;
        $this->profileMaxUpload     =   $this->profile->max_upload;
        $this->profileValidity      =   $this->profile->validity;

    }

    public function render()
    {
        return view('livewire.client.profile.edit-profile')
        ->layout('components.layouts.app',[
            'pageName' => 'Profile',
            'links' => ['Hotspot', 'Profile','Edit']
        ]);
    }
}
