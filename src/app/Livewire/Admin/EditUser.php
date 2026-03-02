<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Tenant;
use Livewire\Component;

class EditUser extends Component
{

    public $userName;
    public $password;
    public $domain;
    public $userStatus = 'active';
    public $tenantId = 0;


    public function mount($id)
    {

        $this->tenantId = $id;

        $tenant = Tenant::find($id);

        if(!$tenant) {
            return redirect()->route('admin.dashboard')
            ->with([
                'type' => 'warning',
                'message' => 'User not Found'
            ]);
        }

        $domain = $tenant->domains()->first();

        if(!$domain) {
            return redirect()->route('admin.dashboard')
            ->with([
                'type' => 'warning',
                'message' => 'User Invalid'
            ]);
        }

        $this->userName = $domain->username;
        $this->domain = $domain->domain;
        $this->userStatus = $domain->status;
    }

    public function editUser()
    {
        $this->validate([
            'domain' => 'required',
            'userStatus' => 'required|in:active,suspended',
            'password' => 'nullable|min:8|regex:/[0-9]/',
        ]);

        $tenant = Tenant::find($this->tenantId);

        if(!$tenant) {
            return redirect()->route('admin.dashboard')
            ->with([
                'type' => 'warning',
                'message' => 'User not Found'
            ]);
        }

        $domain = $tenant->domains()->first();

        if(!$domain) {
            return redirect()->route('admin.dashboard')
            ->with([
                'type' => 'warning',
                'message' => 'User Invalid'
            ]);
        }

        

        if(!empty($this->password)) {

            $hashedPassword = bcrypt($this->password);

            $domain->update([
                'domain' => $this->domain,
                'password' => $hashedPassword,
                'status' => $this->userStatus,
            ]);

            tenancy()->initialize($tenant);
            User::query()
            ->where([
                'username' => $this->userName
            ])->update([
                'password' => $hashedPassword
            ]);
            tenancy()->end();
        } else {
            $domain->update([
                'domain' => $this->domain,
                'status' => $this->userStatus,
            ]);
        }

        return redirect()->route('admin.dashboard')
        ->with([
            'type' => 'success',
            'message' => 'User Updated'
        ]);

    }

    public function render()
    {
        return view('livewire.admin.edit-user')
        ->layout('components.layouts.app',[
            'pageName' => 'Edit User',
            'links' => ['Users', 'Edit']
        ]);
    }
}
