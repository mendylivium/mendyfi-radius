<?php

use App\Livewire\Admin\Config;
use App\Livewire\Admin\AddUser;
use App\Livewire\Portal\Tplink;
use App\Livewire\Admin\EditUser;
use App\Livewire\Client\Auth\Login;
use App\Livewire\Client\Sales\Sales;

use App\Http\Controllers\TestControl;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SalesGraph;
use App\Livewire\Client\Profile\Profile;
use App\Http\Controllers\Api\VouchersInfo;
use App\Livewire\Admin\Auth as AdminLogin;
use App\Http\Controllers\Radius\Accounting;
use App\Livewire\Client\Profile\BindPolicy;
use App\Livewire\Client\Dashboard\Dashboard;
use App\Livewire\Client\Profile\EditProfile;
use App\Livewire\Client\FairUsePolicy\AddFup;
use App\Livewire\Client\Reseller\AddReseller;
use App\Livewire\Client\FairUsePolicy\EditFup;
use App\Livewire\Client\FairUsePolicy\FupList;
use App\Livewire\Client\Hotspot\Vouchers\Used;
use App\Livewire\Client\Profile\CreateProfile;
use App\Livewire\Client\Reseller\EditReseller;
use App\Livewire\Client\Reseller\ResellerList;
use App\Http\Controllers\Radius\Authentication;
use App\Livewire\Client\Settings\Configuration;
use App\Livewire\Client\Hotspot\Vouchers\Active;
use App\Livewire\Client\Hotspot\Vouchers\Generate;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Client\Hotspot\Vouchers\Generated;
use App\Livewire\Client\Hotspot\Template\EditTemplate;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use App\Livewire\Client\Hotspot\Template\CreateTemplate;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use App\Livewire\Client\Hotspot\Template\GeneratedTemplate;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


foreach (config('tenancy.central_domains') as $domain) {

    //if(request()->getPort() != 8090) break;

    

    Route::domain($domain)->group(function () {
        // your actual routes
        Route::group(['prefix' => 'auth'], function(){
            Route::get('login', AdminLogin::class)->name('admin.auth.login');
        });
        

        Route::get('test',[TestControl::class, 'index']);
            
        Route::group([
            'middleware' => ['web']], function(){
            Route::get('/', function(){
                return redirect()->route('admin.auth.login');
            });
            Route::middleware(['admin'])->group(function(){
                Route::get('dashboard',AdminDashboard::class)->name('admin.dashboard');
                Route::get('new-user',AddUser::class)->name('admin.adduser');
                Route::get('edit-user-{id}',EditUser::class)->name('admin.edituser');
                Route::get('config', Config::class)->name('admin.config');
        
                Route::get('out',function(){
                    auth()->logout();
                    return redirect()->route('admin.auth.login');
                })->name('admin.logout');
            });
            
        });
    });
}


Route::group(['prefix' => 'api'], function(){
    Route::group(['prefix' => 'radius'],function(){
        Route::match(['GET','POST'],'auth',[Authentication::class,'gateway']);
        Route::match(['GET','POST'],'accounting',[Accounting::class,'gateway']);
    });
});

Route::get('/tplink-{tenant_id}',Tplink::class);


Route::group([
    'middleware' => [
        'web',
        env('IDENTIFY_BY_PARAMATER', false) ? InitializeTenancyByRequestData::class : InitializeTenancyByDomain::class,
        env('IDENTIFY_BY_PARAMATER', false) ? 'web' : (app()->runningInConsole()? null : ((filter_var(request()->getHost(),FILTER_VALIDATE_IP)) ? 'web' : PreventAccessFromCentralDomains::class)),
    ]
],function () {
    Route::group(['prefix' => 'api'],function(){
        
        Route::match(['GET','POST'],'vouchers',[VouchersInfo::class,'print'])->name('api.vouchers.print');
        Route::match(['GET','POST'],'sales',[SalesGraph::class,'index'])->name('api.sales');
        Route::match(['GET','POST'],'profiles/{apiPublic}',[VouchersInfo::class,'getAllProfiles'])->name('api.profiles.all');
        Route::match(['GET','POST'],'profiles/{apiPublic}/{resellerId}',[VouchersInfo::class,'getResellerProfiles'])->name('api.profiles.reseller');
    });

    Route::group([ 'prefix' => 'client'],function(){
    
        Route::group(['prefix' => 'auth', 'middleware' => 'guest'],function(){
            Route::get('login',Login::class)->name('client.auth.login');
        });
    
        Route::group(['middleware' => 'auth'],function(){
            
            Route::get('logout',function(){
                auth()->logout();
                return redirect()->route('client.auth.login');
            })->name('logout');
    
            Route::get('dashboard',Dashboard::class)->name('client.dashboard');
    
            Route::group(['prefix' => 'hotspot'],function(){
                //Vouchers
                Route::group(['prefix' => 'vouchers'],function(){
                    Route::get('list',Generated::class)->name('client.vouchers.list');
                    Route::get('used',Used::class)->name('client.vouchers.used');
                    Route::get('active',Active::class)->name('client.vouchers.active');
                    Route::get('generate',Generate::class)->name('client.voucher.generate');
    
                    Route::get('print',[VouchersInfo::class,'template'])->name('client.voucher.print');
    
                    Route::get('templates',GeneratedTemplate::class)->name('client.voucher.template');
                    Route::get('create-template',CreateTemplate::class)->name('client.voucher.template.create');
                    Route::get('edit-template-{id}',EditTemplate::class)->name('client.voucher.template.edit');
                    
                    Route::group(['prefix' => 'profile'],function(){
                        Route::get('list',Profile::class)->name('client.vouchers.profiles');
                        Route::get('create',CreateProfile::class)->name('client.vouchers.profile.create');
                        Route::get('edit-{id}',EditProfile::class)->name('client.vouchers.profile.edit');
                        Route::get('bind-policy-{id}',App\Livewire\Client\Profile\BindPolicy::class)->name('client.vouchers.profile.bind-policy');
                    });      
                }); 
    
                Route::group(['prefix' => 'reseller'], function(){
                    Route::get('list',App\Livewire\Client\Reseller\ResellerList::class)->name('client.reseller.list');
                    Route::get('add',App\Livewire\Client\Reseller\AddReseller::class)->name('client.reseller.add');
                    Route::get('edit/{id}',App\Livewire\Client\Reseller\EditReseller::class)->name('client.reseller.edit');
                });
                
                Route::get('config', Configuration::class)->name('client.config');
                Route::get('sales', Sales::class)->name('client.sales');
            });
    
            Route::group(['prefix' => 'fair_use_policy'], function(){
                Route::get('list', App\Livewire\Client\FairUsePolicy\FupList::class)->name('client.fairuse.list');
                Route::get('add', App\Livewire\Client\FairUsePolicy\AddFup::class)->name('client.fairuse.add');
                Route::get('edit-{id}', App\Livewire\Client\FairUsePolicy\EditFup::class)->name('client.fairuse.edit');
            });

            Route::group(['prefix' => 'settings'], function(){
                Route::get('branding', App\Livewire\Client\Settings\Branding::class)->name('client.settings.branding');
            });
    
        });
        
    });

    Route::get('/', function () {
        return redirect()->route('client.auth.login');
        // return redirect('/client/auth/login');
    });

});


