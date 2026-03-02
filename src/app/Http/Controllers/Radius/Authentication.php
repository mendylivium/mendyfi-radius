<?php

namespace App\Http\Controllers\Radius;

use App\Models\User;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\Reseller;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Illuminate\Http\Request;
use App\Models\FairUsePolicy;
use App\Traits\TelegramHelper;
use Illuminate\Support\Carbon;
use App\Models\HotspotVouchers;
use App\Models\BindFairUsePolicy;
use Illuminate\Support\Facades\DB;
use App\Models\ActiveFairUsePolicy;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\RateLimiter;

class Authentication extends Controller
{
    //
    use RadiusHelper, BasicHelper, TelegramHelper;

    private $publicApiToken;

    private $voucherCode;
    private $macAddress;
    private $serverName;
    private $chapChallenge;
    private $chapPassword;
    private $ipAddress;
    private $sessionId;
    private $nasIdentifier;
    private $nasRealm;
    private $routerIpPrivate;
    private $routerIpPublic;
    private $hotspotPassword;
    private $interim;
    private $nasType;
    private $radiusSecret = null;

    private $user = null;
    private $hotspotCustomer = null;

    private $domainStatus = '';
    private $radiusInterim = 1;

    public function gateway()
    {

        $this->nasRealm             =   $this->getAttribute('MS-CHAP-Domain') ?? $this->getAttribute('Mikrotik-Realm');
        $this->voucherCode          =   $this->getAttribute('User-Name');    
        $this->macAddress           =   $this->getAttribute('Calling-Station-Id');
        $this->serverName           =   $this->getAttribute('Called-Station-Id');
        $this->chapChallenge        =   $this->getAttribute('CHAP-Challenge');
        $this->chapPassword         =   $this->getAttribute('CHAP-Password');
        $this->ipAddress            =   $this->getAttribute('Framed-IP-Address');
        $this->sessionId            =   $this->getAttribute('Acct-Session-Id');
        $this->nasIdentifier        =   $this->getAttribute('NAS-Identifier');
        $this->routerIpPrivate      =   $this->getAttribute('NAS-IP-Address');
        $this->routerIpPublic       =   $this->getAttribute('Packet-SRC-IP-Address');
        $this->locationName         =   $this->getAttribute('WISPr-Location-Name');
        $this->hotspotPassword      =   $this->getAttribute('User-Password');
        $this->limitHotspot         =   $this->getAttribute('Mendify-Limit-Hotspot');
        $this->limitPPPoE           =   $this->getAttribute('Mendify-Limit-PPPoE');
        
        $this->interim              =   now();

        //Reject if PPPoE
        if($this->getAttribute('Framed-Protocol')) {
            return response()->json([
                'Reply-Message' => "Not Supported"
            ],401);
        }

        $this->publicApiToken = $this->getPublicToken($this->nasRealm);

        if(!$this->publicApiToken[1]) {
            $this->publicApiToken = $this->getPublicToken($this->nasIdentifier);
        }

        if(!$this->publicApiToken[1]) {
            return response()->json([
                'Reply-Message' => "Public Token not found " . $this->publicApiToken[0]
            ],401);
        }

        $domain = Domain::query()
        ->where([
            'username' => $this->publicApiToken[1]
        ])
        ->select('tenant_id','status')
        ->first();

        if(!$domain) {
            return response()->json([
                'Reply-Message' => "Public Token not Valid #1"
            ],401);
        }

        $this->domainStatus = $domain->status;


        $tenant = Tenant::query()
        ->where([
            'id' => $domain->tenant_id
        ])
        ->first();

        if(!$tenant) {
            return response()->json([
                'Reply-Message' => "Public Token not Valid #2"
            ],401);
        }


        $this->radiusInterim = $this->getAppSetting('RADIUS_INTERIM');

        tenancy()->initialize($tenant);

        $this->nasType = $this->publicApiToken[0];

        $this->user = User::where('username',$this->publicApiToken[1])
        ->first();


        if($this->user == null) {
            return response()->json([
                'Reply-Message' => "Public Token not Valid #3"
            ],401);
        }

        return $this->handleHotspotAuth();

    }


    private function handleHotspotAuth()
    {
        //Check if the Code is MAC Address

        if($this->domainStatus != 'active') {
            return response()->json([
                'Reply-Message' => 'User Suspended',
                'Mendyfi-Secret' => $this->user->api_secret
            ],401);
        }

        if(RateLimiter::tooManyAttempts('radius_login'.$this->macAddress, 3)) {
            $seconds = RateLimiter::availableIn('radius_login'.$this->macAddress);
            return response()->json([
                'Reply-Message' => 'You may try again in '.$seconds.' seconds.',
                'Mendyfi-Secret' => $this->user->api_secret
            ],401);
        }

        RateLimiter::hit('radius_login'.$this->macAddress, 30);

        $this->hotspotCustomer = HotspotVouchers::leftJoin('hotspot_profiles','hotspot_profiles.id','hotspot_vouchers.hotspot_profile_id');

        if($this->isValidMacAddress($this->voucherCode)) {

            //Clean the Format
            $this->voucherCode = $this->convertMacAddress($this->voucherCode);

            $this->hotspotCustomer = $this->hotspotCustomer->where('hotspot_vouchers.mac_address',$this->voucherCode);
        } else {
            $this->hotspotCustomer = $this->hotspotCustomer->where('hotspot_vouchers.code',$this->voucherCode);
        }


        $this->hotspotCustomer = $this->hotspotCustomer->where('hotspot_vouchers.user_id', $this->user->id)
        ->select(
            'hotspot_vouchers.*',
            'hotspot_profiles.uptime_limit as profile_uptime_limit',
            'hotspot_profiles.data_limit as profile_data_limit',
            'hotspot_profiles.max_download',
            'hotspot_profiles.max_upload',
            'hotspot_profiles.validity'
        )
        ->first();

        if($this->hotspotCustomer == null) {
            return response()->json([
                'Reply-Message' => "Invalid Voucher Code",
                'Mendyfi-Secret' => $this->user->api_secret
            ],401);
        }

        if(!empty($this->hotspotCustomer->password)) {
            if(empty($this->hotspotPassword)) {
                return response()->json([
                    'Reply-Message' => "Password Required for this Voucher",
                    'Mendyfi-Secret' => $this->user->api_secret
                ],401);
            } else {
                if($this->hotspotPassword != $this->hotspotCustomer->password) {
                    return response()->json([
                        'Reply-Message' => "Password Invalid!",
                        'Mendyfi-Secret' => $this->user->api_secret
                    ],401);
                }
            }
        }

        if($this->hotspotCustomer->reseller_id != null) {
            $reseller = Reseller::query()
            ->where('id', $this->hotspotCustomer->reseller_id)
            ->select('status')
            ->first();

            if($reseller) {
                if($reseller->status != 'active') {
                    return response()->json([
                        'Reply-Message' => "Reseller is Suspended",
                        'Mendyfi-Secret' => $this->user->api_secret
                    ],401);
                }
            }
        }


        //Check if user is already connected by using MAC
        if(!empty($this->hotspotCustomer->mac_address) && $this->hotspotCustomer->mac_address != $this->macAddress) {
            if($this->hotspotCustomer->connected) {
                return response()->json([
                    'Reply-Message' => 'Voucher is already used in another Device',
                    'Mendyfi-Secret' => $this->user->api_secret
                ],401);
            }
        }

        // if($this->user->grace_expired) {
        //     if($this->hotspotCustomer->login_type == "voucher") {
        //         return response()->json([
        //             'Reply-Message' => "Expired Subscription"
        //         ],401);
        //     }
        // }

        if(!$this->hotspotCustomer->used_date) {

            //Check if Admin Has credits
            // if($this->user->credits <= 0) {
            //     return response()->json([
            //         'Reply-Message' => "No credits Available: Contact Admin"
            //     ],401);
            // }

            $this->hotspotCustomer->used_date     =   now();

            if($this->hotspotCustomer->profile_uptime_limit) {
                $this->hotspotCustomer->uptime_credit = $this->hotspotCustomer->profile_uptime_limit;
            }

            if($this->hotspotCustomer->profile_data_limit) {
                $this->hotspotCustomer->data_credit = $this->hotspotCustomer->profile_data_limit;
            }

            if(intval($this->hotspotCustomer->validity) > 0) {
                $this->hotspotCustomer->expire_date = Carbon::now()->addSeconds(intval($this->hotspotCustomer->validity));
            }
        } else {

            if($this->hotspotCustomer->time_credit != null && intval($this->hotspotCustomer->time_credit) <= 0) {
                return response()->json([
                    'Reply-Message' => 'Time limit is reached',
                    'Mendyfi-Secret' => $this->user->api_secret
                ],401);
            }

            if($this->hotspotCustomer->data_credit != null && intval($this->hotspotCustomer->data_credit) <= 2000000 * 0) {
                return response()->json([
                    'Reply-Message' => 'Data already consumed',
                    'Mendyfi-Secret' => $this->user->api_secret
                ],401);
            }

            if($this->hotspotCustomer->expire_date != null ) {
                if(Carbon::parse($this->hotspotCustomer->expire_date)->lessThan(Carbon::now())) {
                    return response()->json([
                        'Reply-Message' => 'Expired Voucher',
                        'Mendyfi-Secret' => $this->user->api_secret
                    ],401);
                }
            }
        }

        //Assign Voucher to Radius Attributes
        $this->hotspotCustomer->server_name     =   $this->serverName;
        $this->hotspotCustomer->mac_address     =   $this->macAddress;
        $this->hotspotCustomer->ip_address      =   $this->ipAddress;
        $this->hotspotCustomer->router_ip       =   $this->routerIpPrivate;
        $this->hotspotCustomer->interim         =   $this->interim;

        $responses = [];

        $responses['Port-Limit'] = 1;

        //This Radius Attribute is Removed, Omada Controller dont like it
        //$responses['Acct-Interim-Interval'] = $this->radiusInterim;

        //Data Limit
        if($this->hotspotCustomer->data_credit != null) {

            switch($this->nasType) {
                // case 'mikrotik':
                //     $responses['Mikrotik-Xmit-Limit'] = $this->hotspotCustomer->data_credit;
                // break;

                default:
                    $responses['Ascend-Xmit-Rate'] = $this->hotspotCustomer->data_credit;
                break;
            }
        }

        switch($this->nasType) {
            // case 'mikrotik':
            //     $responses['Mikrotik-Rate-Limit'] = "{$this->hotspotCustomer->max_upload}/{$this->hotspotCustomer->max_download}";
            // break;

            default:
                $responses['WISPr-Bandwidth-Max-Down']  =   $this->hotspotCustomer->max_download . "b";
                $responses['WISPr-Bandwidth-Max-Up']  =   $this->hotspotCustomer->max_upload . "b";
            break;
        }

        if($this->hotspotCustomer->uptime_credit != null) {
         
            $responses['Session-Timeout'] = intval($this->hotspotCustomer->uptime_credit);
            if($this->hotspotCustomer->expire_date != null) {
               
                $exTimeInSec = Carbon::now()->diffInSeconds($this->hotspotCustomer->expire_date);
                $responses['Session-Timeout'] = ($responses['Session-Timeout'] < $exTimeInSec ? $responses['Session-Timeout'] : $exTimeInSec);
            }
        } elseif($this->hotspotCustomer->expire_date != null) {
            $responses['Session-Timeout'] = Carbon::now()->diffInSeconds($this->hotspotCustomer->expire_date);
         
        }
        
        $activeFairUsePolicy = ActiveFairUsePolicy::query()
        ->whereRaw('client_id = ? AND type = ? AND NOW() < resets_on', [$this->hotspotCustomer->id, 'hotspot'])
        ->get();

        if($activeFairUsePolicy) {
            foreach($activeFairUsePolicy as $policy) {

                $bindFUP = BindFairUsePolicy::query()
                ->where([
                    'id' => $policy->bind_fair_use_policy_id
                ])
                ->first();

                if(!$bindFUP) {
                    continue;
                }

                $fairUsePolicy = FairUsePolicy::query()
                ->where([
                    'id' => $bindFUP->fair_user_policy_id
                ])
                ->first();

                if(!$fairUsePolicy) {
                    continue;
                }

                if(!$this->isActionFormat($fairUsePolicy->action)) {
                    continue;
                }                

                $attr = $this->parseAction($fairUsePolicy->action);

                foreach($attr as $key => $val) {
                    $responses[$key] = $val;
                }

                if(isset($responses['access']) && $responses['access'] == 'reject') {
                    return response()->json([
                        'Reply-Message' => "FUP#{$fairUsePolicy->id}: You`ve reached your Limit, back again after {$policy->resets_on}",
                        'Mendyfi-Secret' => $this->user->api_secret
                    ]);
                }
            }
        }

        $responses['Mendyfi-Secret'] = $this->user->api_secret;

        $this->hotspotCustomer->save();
        $this->user->save();
        return response()->json($responses);

    }
}
