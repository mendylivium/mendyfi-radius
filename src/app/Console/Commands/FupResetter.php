<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
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
    protected $description = 'Reset expired fair use policies across all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Tenant::all()->each(function ($tenant) {
            $tenant->run(function () {
                $activeFairUse = ActiveFairUsePolicy::query()
                    ->whereRaw('NOW() > resets_on')
                    ->limit(10)
                    ->get();

                foreach ($activeFairUse as $policy) {
                    User::query()
                        ->where('id', $policy->client_id)
                        ->update([
                            'fup_total_data' => 0,
                            'fup_total_time' => 0,
                        ]);

                    $policy->delete();
                }
            });
        });
    }
}
