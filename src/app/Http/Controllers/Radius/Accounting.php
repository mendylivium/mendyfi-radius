<?php

namespace App\Http\Controllers\Radius;

use App\Models\User;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\SalesRecord;
use App\Traits\BasicHelper;
use App\Traits\RadiusHelper;
use Illuminate\Http\Request;
use App\Models\FairUsePolicy;
use App\Models\HotspotProfile;
use App\Traits\TelegramHelper;
use Illuminate\Support\Carbon;
use App\Models\HotspotVouchers;
use App\Models\BindFairUsePolicy;
use Illuminate\Support\Facades\DB;
use App\Models\ActiveFairUsePolicy;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class Accounting extends Controller
{
    use RadiusHelper, TelegramHelper, BasicHelper;

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
    private $action;
    private $statusType;
    private $downloadOctets;
    private $uploadOctets;
    private $sessionTime;

    private $user = null;
    private $hotspotCustomer = null;

    private $domainStatus = '';

    private $currentTenant = null;
    

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
        $this->statusType           =   $this->getAttribute('Acct-Status-Type');  
        $this->downloadOctets       =   $this->getAttribute('Acct-Output-Octets'); 
        $this->uploadOctets         =   $this->getAttribute('Acct-Input-Octets'); 
        $this->sessionTime          =   $this->getAttribute('Acct-Session-Time');
        
        $this->action               =   request()->action;

        $this->interim              =   now();


        if($this->statusType == "Accounting-On") {
            return response()->json([
                'Reply-Message' => 'Proceed'
            ],201);
        }


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
        ->select('tenant_id')
        ->first();

        if(!$domain) {
            return response()->json([
                'Reply-Message' => "Public Token not Valid #1"
            ],401);
        }

        $this->domainStatus = $domain->status;

        $this->currentTenant = Tenant::query()
        ->where([
            'id' => $domain->tenant_id
        ])
        ->first();

        if(!$this->currentTenant) {
            return response()->json([
                'Reply-Message' => "Public Token not Valid #2"
            ],401);
        }

        tenancy()->initialize($this->currentTenant);

        $this->nasType = $this->publicApiToken[0];

        $this->user = User::where('username',$this->publicApiToken[1])
        ->first();

        // $this->user = User::where('api_public',$this->publicApiToken[1])
        // ->first();

        if($this->user == null) {
            return response()->json([
                'Reply-Message' => "Public Token not Valid #3"
            ],401);
        }

        return $this->handleHotspotAcct();

    }

    public function handleHotspotAcct()
    {

        tenancy()->initialize($this->currentTenant);

        $this->hotspotCustomer = null;// HotspotVouchers::leftJoin('hotspot_profiles','hotspot_profiles.id','hotspot_vouchers.hotspot_profile_id');

        if($this->isValidMacAddress($this->voucherCode)) {

            //Clean the Format
            $this->voucherCode = $this->convertMacAddress($this->voucherCode);

            $this->hotspotCustomer = HotspotVouchers::where('mac_address',$this->voucherCode);
        } else {
            $this->hotspotCustomer = HotspotVouchers::where('code',$this->voucherCode);
        }


        $this->hotspotCustomer = $this->hotspotCustomer->where('user_id', $this->user->id)
        ->first();

        if($this->hotspotCustomer == null) {
            return response()->json([
                'Reply-Message' => "Error: Invalid Code",
                'Mendyfi-Secret' => $this->user->api_secret
            ],201);
        }

        if($this->interim != null && $this->hotspotCustomer->interim != null) {
            if(Carbon::parse($this->interim)->equalTo(Carbon::parse($this->hotspotCustomer->interim))) {
                return response()->json([
                    'Reply-Message' => "Error: Seems Duplicate Update",
                    'Mendyfi-Secret' => $this->user->api_secret
                ],201);
            }
        }

        $this->hotspotCustomer->interim = $this->interim;

        $consumedTime = 0;
        $consumedBytes = 0;

        //Compute the Actual Data Comsume
        $recentDataReading = $this->hotspotCustomer->recent_data_use;
        $consumedBytes = intval($this->downloadOctets) - intval($recentDataReading);

        //Compute the Actual Time use by user
        $recentTimeReading = $this->hotspotCustomer->recent_time_use;
        $consumedTime   =   intval($this->sessionTime) - intval($recentTimeReading);

        switch($this->statusType)
        {
        
            case "Stop":
                //Reset to Zero since STOPPED
                $this->hotspotCustomer->recent_data_use = 0;
                $this->hotspotCustomer->recent_time_use = 0;

                $this->hotspotCustomer->total_used_time += $consumedTime;
                $this->hotspotCustomer->total_used_data += $consumedBytes;

                //Check if user has Credit Time Limit
                if($this->hotspotCustomer->has_uptime_limit && $consumedTime > 0) {
                    $this->hotspotCustomer->uptime_credit -= $consumedTime;
                }

                //Check if user has Credit Data Limit
                if($this->hotspotCustomer->has_data_limit && $consumedBytes > 0) {
                    $this->hotspotCustomer->data_credit -= $consumedBytes;
                }

                //Set Status to Offline
                $this->hotspotCustomer->connected = false;
                $this->hotspotCustomer->save();

                return response()->json([
                    'Reply-Message' => "User Disconnected: Consumed Data:{$consumedBytes}, Consumed Time:{$consumedTime}",
                    'Mendyfi-Secret' => $this->user->api_secret
                ],201);

                break;

            case "Interim-Update":
                //Reset to Zero since STOPPED
                $this->hotspotCustomer->recent_data_use = $this->downloadOctets;
                $this->hotspotCustomer->recent_time_use = $this->sessionTime;

                $this->hotspotCustomer->session_upload = $this->uploadOctets;
                $this->hotspotCustomer->session_download = $this->downloadOctets;

                $this->hotspotCustomer->total_used_time += $consumedTime;
                $this->hotspotCustomer->total_used_data += $consumedBytes;

                $this->hotspotCustomer->fup_used_time += $consumedTime;
                $this->hotspotCustomer->fup_used_data += $consumedBytes;

                //Check if user has Credit Time Limit
                if($this->hotspotCustomer->uptime_credit != null && $consumedTime > 0) {
                    $this->hotspotCustomer->uptime_credit -= $consumedTime;
                }

                //Check if user has Credit Data Limit
                if($this->hotspotCustomer->data_credit != null && $consumedBytes > 0) {
                    $this->hotspotCustomer->data_credit -= $consumedBytes;
                }



                //CheckFairUse
                $bindFairUse = BindFairUsePolicy::query()
                ->where([
                    'hotspot_profile_id' => $this->hotspotCustomer->hotspot_profile_id,
                ])
                ->get();
                //$this->telegramSendMessage("2021159313:AAHEBoOLogYjLCpSwVeKPVmKKO4TIxa02vQ","-949707668",$this->hotspotCustomer->hotspot_profile_id);
                if($bindFairUse) {                    
                    
                    foreach($bindFairUse as $bindPolicy) {
                        
                        $hasActive = ActiveFairUsePolicy::query()
                        ->select('id')
                        ->whereRaw('bind_fair_use_policy_id = ? AND client_id = ? AND NOW() < resets_on', [$bindPolicy->id, $this->hotspotCustomer->id])
                        ->first();

                        if($hasActive) {
                            continue;
                        }
                  
                        $fairUse = FairUsePolicy::query()
                        ->where([
                            'id' => $bindPolicy->fair_user_policy_id
                        ])
                        ->first();

                        if(!$this->isActionFormat($fairUse->action)) {
                            continue;
                        }                

                        if(!$this->isConditionFormat($fairUse->condition)) {
                            continue;
                        }

                        $currentCondition = [
                            'total_uptime' => $this->hotspotCustomer->total_used_time,
                            'total_data' => $this->hotspotCustomer->total_used_data,
                            'fup_uptime' => $this->hotspotCustomer->fup_used_time,
                            'fup_data' => $this->hotspotCustomer->fup_used_data,
                            'hour' => Carbon::now()->format("H"),
                            'day' => Carbon::now()->format("d"),
                            'month' => Carbon::now()->format("m"),
                        ];

                        
                        if($this->analyzeConditions($fairUse->condition, $currentCondition)) {
                            
                            $attr = $this->parseAction($fairUse->action);

                            foreach($attr as $key => $val) {
                                $responses[$key] = $val;
                            }

                            if(isset($responses['access']) && $responses['access'] == 'reject') {

                                $this->radiusCoa('disconnect', [
                                    'User-Name' => $this->hotspotCustomer->code,
                                    'Framed-IP-Address' => $this->hotspotCustomer->ip_address
                                ], $this->user->api_secret, $this->hotspotCustomer->router_ip);    
                            } else {
                                $responses['User-Name'] = $this->hotspotCustomer->code;
                                $responses['Framed-IP-Address'] = $this->hotspotCustomer->ip_address;
                                $this->radiusCoa('coa', $responses, $this->user->api_secret, $this->hotspotCustomer->router_ip);
                            }

                            ActiveFairUsePolicy::insert([
                                'bind_fair_use_policy_id' => $bindPolicy->id,
                                'client_id' => $this->hotspotCustomer->id,
                                'type' => 'hotspot',
                                'resets_on' => now()->addMinutes($fairUse->resets_on)
                            ]);
                        
                        }
                    }
                }


                //Set Status to Online
                $this->hotspotCustomer->connected = true;
                $this->hotspotCustomer->save();
                
                return response()->json([
                    'Reply-Message' => "User Update: Consumed Data:{$consumedBytes}, Consumed Time:{$consumedTime}",
                    'Mendyfi-Secret' => $this->user->api_secret
                ],201);
                break;

            case "Start":
                
                $this->hotspotSales();
                //Set Status to Online
                $this->hotspotCustomer->connected = true;
                $this->hotspotCustomer->save();

                return response()->json([
                    'Reply-Message' => "Hotspot: {$this->hotspotCustomer->code} Connected",
                    'Mendyfi-Secret' => $this->user->api_secret
                ],201);
                break;
        }

    }

    private function hotspotSales()
    {
        tenancy()->initialize($this->currentTenant);

        if(!$this->hotspotCustomer->sales_recorded) {
            $profile = HotspotProfile::where([
                'id' => $this->hotspotCustomer->hotspot_profile_id
            ])
            ->select('price','name')
            ->first();

            if($profile) {
                SalesRecord::create([
                    'user_id'       =>  $this->hotspotCustomer->user_id,
                    'amount'        =>  $profile->price,
                    'code'          =>  $this->hotspotCustomer->code,
                    'mac_address'   =>  $this->hotspotCustomer->mac_address,
                    'ip_address'    =>  $this->hotspotCustomer->ip_address,
                    'server_name'   =>  $this->hotspotCustomer->server_name,
                    'router_name'   =>  $this->hotspotCustomer->router_name,
                    'profile_name'  =>  $profile->name,
                    'account_type'  =>  'hotspot',
                    'reseller_id'   =>  $this->hotspotCustomer->reseller_id ?? 0,
                    'transact_date' =>  now()
                ]);
            }

            $this->hotspotCustomer->sales_recorded = true;
        }
    }
}
