<?php

namespace App\Livewire\Client\Settings;

use Livewire\Component;
use App\Traits\BasicHelper;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Storage;

class Branding extends Component
{
    use BasicHelper;
    use WithFileUploads;

    public $company_name;
    public $primary_color;
    public $secondary_color;
    public $sidebar_color;
    public $logo;
    public $favicon;
    public $currentLogo;
    public $currentFavicon;

    public function mount()
    {
        $user = auth()->user();
        $this->company_name = $user->company_name ?? '';
        $this->primary_color = $user->primary_color ?? '#4e73df';
        $this->secondary_color = $user->secondary_color ?? '#858796';
        $this->sidebar_color = $user->sidebar_color ?? '#4e73df';
        $this->currentLogo = $user->logo_path;
        $this->currentFavicon = $user->favicon_path;
    }

    public function updatedLogo()
    {
        $this->validate([
            'logo' => 'image|max:2048', // 2MB max
        ]);
    }

    public function updatedFavicon()
    {
        $this->validate([
            'favicon' => 'image|max:512', // 512KB max
        ]);
    }

    public function save()
    {
        $this->validate([
            'company_name' => 'nullable|string|max:255',
            'primary_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'sidebar_color' => 'required|regex:/^#[a-fA-F0-9]{6}$/',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:512',
        ]);

        $user = auth()->user();

        // Handle logo upload
        if ($this->logo) {
            // Delete old logo if exists
            if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
                Storage::disk('public')->delete($user->logo_path);
            }
            
            $logoPath = $this->logo->store('branding/logos', 'public');
            $user->logo_path = $logoPath;
            $this->currentLogo = $logoPath;
        }

        // Handle favicon upload
        if ($this->favicon) {
            // Delete old favicon if exists
            if ($user->favicon_path && Storage::disk('public')->exists($user->favicon_path)) {
                Storage::disk('public')->delete($user->favicon_path);
            }
            
            $faviconPath = $this->favicon->store('branding/favicons', 'public');
            $user->favicon_path = $faviconPath;
            $this->currentFavicon = $faviconPath;
        }

        $user->company_name = $this->company_name;
        $user->primary_color = $this->primary_color;
        $user->secondary_color = $this->secondary_color;
        $user->sidebar_color = $this->sidebar_color;
        $user->save();

        // Reset file inputs
        $this->logo = null;
        $this->favicon = null;

        $this->showFlash([
            'type' => 'success',
            'message' => 'Branding settings saved successfully!'
        ]);
    }

    public function removeLogo()
    {
        $user = auth()->user();
        
        if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
            Storage::disk('public')->delete($user->logo_path);
        }
        
        $user->logo_path = null;
        $user->save();
        $this->currentLogo = null;

        $this->showFlash([
            'type' => 'warning',
            'message' => 'Logo removed successfully!'
        ]);
    }

    public function removeFavicon()
    {
        $user = auth()->user();
        
        if ($user->favicon_path && Storage::disk('public')->exists($user->favicon_path)) {
            Storage::disk('public')->delete($user->favicon_path);
        }
        
        $user->favicon_path = null;
        $user->save();
        $this->currentFavicon = null;

        $this->showFlash([
            'type' => 'warning',
            'message' => 'Favicon removed successfully!'
        ]);
    }

    public function resetToDefault()
    {
        $this->primary_color = '#4e73df';
        $this->secondary_color = '#858796';
        $this->sidebar_color = '#4e73df';
        
        $user = auth()->user();
        $user->primary_color = $this->primary_color;
        $user->secondary_color = $this->secondary_color;
        $user->sidebar_color = $this->sidebar_color;
        $user->save();

        $this->showFlash([
            'type' => 'info',
            'message' => 'Colors reset to default!'
        ]);
    }

    #[Computed()]
    public function user()
    {
        return auth()->user();
    }

    public function render()
    {
        return view('livewire.client.settings.branding')
            ->layout('components.layouts.app', [
                'pageName' => 'Branding',
                'links' => ['Settings', 'Branding']
            ]);
    }
}
