<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TenantDefaultsSeeder
{
    /**
     * Seed all default data for a new tenant.
     */
    public static function seed(int $userId): void
    {
        self::seedHotspotProfiles($userId);
        self::seedVoucherTemplates($userId);
    }

    /**
     * Seed default hotspot profiles for new tenants.
     */
    public static function seedHotspotProfiles(int $userId): void
    {
        $now = now();
        $profiles = [
            // Hourly plans (unlimited speed)
            ['name' => '1 Hour', 'description' => 'Plan valid for 1 hour.', 'price' => 0.00, 'uptime_limit' => 3600, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 3600],
            ['name' => '2 Hours', 'description' => 'Plan valid for 2 hours.', 'price' => 0.00, 'uptime_limit' => 7200, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 7200],
            ['name' => '3 Hours', 'description' => 'Plan valid for 3 hours.', 'price' => 0.00, 'uptime_limit' => 10800, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 10800],
            ['name' => '4 Hours', 'description' => 'Plan valid for 4 hours.', 'price' => 0.00, 'uptime_limit' => 14400, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 14400],
            ['name' => '5 Hours', 'description' => 'Plan valid for 5 hours.', 'price' => 0.00, 'uptime_limit' => 18000, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 18000],
            ['name' => '6 Hours', 'description' => 'Plan valid for 6 hours.', 'price' => 0.00, 'uptime_limit' => 21600, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 21600],
            ['name' => '7 Hours', 'description' => 'Plan valid for 7 hours.', 'price' => 0.00, 'uptime_limit' => 25200, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 25200],
            ['name' => '8 Hours', 'description' => 'Plan valid for 8 hours.', 'price' => 0.00, 'uptime_limit' => 28800, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 28800],
            ['name' => '9 Hours', 'description' => 'Plan valid for 9 hours.', 'price' => 0.00, 'uptime_limit' => 32400, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 32400],
            ['name' => '10 Hours', 'description' => 'Plan valid for 10 hours.', 'price' => 0.00, 'uptime_limit' => 36000, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 36000],
            ['name' => '11 Hours', 'description' => 'Plan valid for 11 hours.', 'price' => 0.00, 'uptime_limit' => 39600, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 39600],
            ['name' => '12 Hours', 'description' => 'Plan valid for 12 hours.', 'price' => 0.00, 'uptime_limit' => 43200, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 43200],
            // Daily plans (unlimited data)
            ['name' => '1 Day', 'description' => '24-hour unlimited access.', 'price' => 0.00, 'uptime_limit' => 86400, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 86400],
            ['name' => '2 Days', 'description' => '48-hour unlimited access.', 'price' => 0.00, 'uptime_limit' => 172800, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 172800],
            ['name' => '3 Days', 'description' => '72-hour unlimited access.', 'price' => 0.00, 'uptime_limit' => 259200, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 259200],
            ['name' => '4 Days', 'description' => '96-hour unlimited access.', 'price' => 0.00, 'uptime_limit' => 345600, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 345600],
            ['name' => '5 Days', 'description' => '120-hour unlimited access.', 'price' => 0.00, 'uptime_limit' => 432000, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 432000],
            ['name' => '6 Days', 'description' => '144-hour unlimited access.', 'price' => 0.00, 'uptime_limit' => 518400, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 518400],
            ['name' => '1 Week', 'description' => '7-day unlimited access.', 'price' => 0.00, 'uptime_limit' => 604800, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 604800],
            ['name' => '1 Month', 'description' => '30-day unlimited access.', 'price' => 0.00, 'uptime_limit' => 2592000, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 2592000],
            ['name' => 'Unlimited', 'description' => 'Unlimited access without time restrictions.', 'price' => 0.00, 'uptime_limit' => 0, 'data_limit' => 0, 'max_download' => 0, 'max_upload' => 0, 'validity' => 0],
        ];

        foreach ($profiles as $profile) {
            DB::table('hotspot_profiles')->insert(array_merge($profile, [
                'user_id' => $userId,
                'reseller_id' => null,
                'total_uptime' => null,
                'total_data' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Seed default voucher templates for new tenants.
     */
    public static function seedVoucherTemplates(int $userId): void
    {
        $now = now();

        // Template 1: Fancy Template (without QR)
        $fancyTemplateHead = 'body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 10px;
    background-color: #f0f4f8;
}
.voucher {
    width: 180px;
    background-color: white;
    border: 1px solid #007bff;
    border-radius: 8px;
    margin: 5px;
    display: inline-block;
    overflow: hidden;
}
.header {
    background-color: #007bff;
    color: white;
    padding: 8px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}
.content {
    padding: 8px;
    font-size: 11px;
}
.code {
    color: #007bff;
    font-weight: bold;
    font-size: 14px;
    margin: 5px 0;
    padding: 3px;
    border: 1px dashed #007bff;
    border-radius: 4px;
    text-align: center;
}
.label {
    font-weight: bold;
    color: #555;
}
.value {
    float: right;
}
.info-row {
    margin-bottom: 3px;
    clear: both;
}
@media print {
    body {
        margin: 0;
        background-color: white;
    }
    .voucher {
        page-break-inside: avoid;
        border: 1px solid #007bff;
    }
    .header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
        background-color: #007bff !important;
        color: white !important;
    }
}';

        $fancyTemplateBody = '<div class="voucher">
    <div class="header">
        <span x-text="voucher.profile"></span>
        <span x-text="\'#\' + voucher.id"></span>
    </div>
    <div class="content">
        <div class="label">VOUCHER CODE</div>
        <div class="code" x-text="voucher.code"></div>
        <div class="info-row">
            <span class="label">Price:</span>
            <span class="value" x-text="voucher.price"></span>
        </div>
        <div class="info-row">
            <span class="label">Time Limit:</span>
            <span class="value" x-text="voucher.time_limit"></span>
        </div>
        <div class="info-row">
            <span class="label">Validity:</span>
            <span class="value" x-text="voucher.validity"></span>
        </div>
    </div>
</div>';

        DB::table('voucher_templates')->insert([
            'user_id' => $userId,
            'name' => 'Modern Blue',
            'head' => $fancyTemplateHead,
            'body' => $fancyTemplateBody,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Template 2: Fancy Template with QR
        $fancyTemplateQRHead = 'body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 10px;
    background-color: #f0f4f8;
}
.voucher {
    width: 180px;
    background-color: white;
    border: 1px solid #007bff;
    border-radius: 8px;
    margin: 5px;
    display: inline-block;
    overflow: hidden;
}
.header {
    background-color: #007bff;
    color: white;
    padding: 8px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}
.content {
    padding: 8px;
    font-size: 11px;
}
.code {
    color: #007bff;
    font-weight: bold;
    font-size: 14px;
    margin: 5px 0;
    padding: 3px;
    border: 1px dashed #007bff;
    border-radius: 4px;
    text-align: center;
}
.label {
    font-weight: bold;
    color: #555;
}
.value {
    float: right;
}
.info-row {
    margin-bottom: 3px;
    clear: both;
}
/* New QR code styling */
.qr-code {
    text-align: center;
    margin: 8px 0;
}
.qr-code img {
    width: 75px;
    height: 75px;
    border: 1px solid #007bff;
    border-radius: 4px;
    padding: 2px;
}
@media print {
    body {
        margin: 0;
        background-color: white;
    }
    .voucher {
        page-break-inside: avoid;
        border: 1px solid #007bff;
    }
    .header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
        background-color: #007bff !important;
        color: white !important;
    }
}';

        $fancyTemplateQRBody = '<div class="voucher">
    <div class="header">
        <span x-text="voucher.profile"></span>
        <span x-text="\'#\' + voucher.id"></span>
    </div>
    <div class="content">
        <div class="label">VOUCHER CODE</div>
        <div class="qr-code">
            <img :src="\'https://api.qrserver.com/v1/create-qr-code/?size=75x75&data=\' + voucher.code" alt="QR Code" />
        </div>
        <div class="code" x-text="voucher.code"></div>
        <div class="info-row">
            <span class="label">Price:</span>
            <span class="value" x-text="voucher.price"></span>
        </div>
        <div class="info-row">
            <span class="label">Time Limit:</span>
            <span class="value" x-text="voucher.time_limit"></span>
        </div>
        <div class="info-row">
            <span class="label">Validity:</span>
            <span class="value" x-text="voucher.validity"></span>
        </div>
    </div>
</div>';

        DB::table('voucher_templates')->insert([
            'user_id' => $userId,
            'name' => 'Modern Blue (QR)',
            'head' => $fancyTemplateQRHead,
            'body' => $fancyTemplateQRBody,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Template 3: Modern Black (monochrome version of Modern Blue)
        $bwTemplateHead = 'body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 10px;
    background-color: #fff;
}
.voucher {
    width: 180px;
    background-color: white;
    border: 2px solid #000;
    border-radius: 8px;
    margin: 5px;
    display: inline-block;
    overflow: hidden;
}
.header {
    background-color: #000;
    color: white;
    padding: 8px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}
.content {
    padding: 8px;
    font-size: 11px;
}
.code {
    color: #000;
    font-weight: bold;
    font-size: 14px;
    margin: 5px 0;
    padding: 3px;
    border: 2px dashed #000;
    border-radius: 4px;
    text-align: center;
}
.label {
    font-weight: bold;
    color: #333;
}
.value {
    float: right;
}
.info-row {
    margin-bottom: 3px;
    clear: both;
}
@media print {
    body { margin: 0; background-color: white; }
    .voucher { page-break-inside: avoid; border: 2px solid #000; }
    .header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
        background-color: #000 !important;
        color: white !important;
    }
}';

        $bwTemplateBody = '<div class="voucher">
    <div class="header">
        <span x-text="voucher.profile"></span>
        <span x-text="\'#\' + voucher.id"></span>
    </div>
    <div class="content">
        <div class="label">VOUCHER CODE</div>
        <div class="code" x-text="voucher.code"></div>
        <div class="info-row">
            <span class="label">Price:</span>
            <span class="value" x-text="voucher.price"></span>
        </div>
        <div class="info-row">
            <span class="label">Time:</span>
            <span class="value" x-text="voucher.time_limit"></span>
        </div>
        <div class="info-row">
            <span class="label">Valid:</span>
            <span class="value" x-text="voucher.validity"></span>
        </div>
        <div class="info-row" x-show="voucher.data_limit">
            <span class="label">Data:</span>
            <span class="value" x-text="voucher.data_limit"></span>
        </div>
    </div>
</div>';

        DB::table('voucher_templates')->insert([
            'user_id' => $userId,
            'name' => 'Modern Black',
            'head' => $bwTemplateHead,
            'body' => $bwTemplateBody,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
