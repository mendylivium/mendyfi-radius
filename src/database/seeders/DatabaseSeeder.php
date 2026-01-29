<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use App\Services\TenantDefaultsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $admin = User::create([
            'name'          =>  'Administrator',
            'username'         =>  'admin',
            'password'      =>  Hash::make('admin@1234'),
            'api_public'    =>  Str::random(12),
            'api_secret'    =>  Str::random(12),
            'sessionToken'  =>  'ses_' . Str::random(20),
            'license_validity'  =>  now(),
            'mobile'        =>  '09123456789',
        ]);

        DB::table('app_settings')->insert([
            ['name' =>  'APP_NAME',     'value' =>  'Mendyfi'],
            ['name' =>  'INRO_TEXT',    'value' =>  'Centalize Hotspot System'],
            ['name' =>  'SUB_TEXT',     'value' =>  'Radius for your Hotspot'],
            ['name' =>  'RADIUS_INTERIM', 'value' => '1'],
        ]);

        // Seed default hotspot profiles and voucher templates
        TenantDefaultsSeeder::seed($admin->id);
    }
}
