<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Traits\TelegramHelper;
use Illuminate\Console\Command;
use App\Models\ActiveFairUsePolicy;

class FupResetter extends Command
{

    use TelegramHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fup-resetter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $activeFairUse = ActiveFairUsePolicy::query()
        ->whereRaw('NOW() > resets_on')
        ->limit(10)
        ->get();

        if($activeFairUse) {
            foreach($activeFairUse as $policy) {
                $this->telegramSendMessage("2021159313:AAHEBoOLogYjLCpSwVeKPVmKKO4TIxa02vQ","-949707668","Removing FUP#{$policy->id}");
                User::query()
                ->where([
                    'id' => $policy->client_id
                ])->update([
                    'fup_total_data' => 0,
                    'fup_total_time' => 0,
                ]);

                $policy->delete();
            }
        }
    }
}
