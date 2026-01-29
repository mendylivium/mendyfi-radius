<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('company_name');
            $table->string('favicon_path')->nullable()->after('logo_path');
            $table->string('primary_color', 7)->default('#4e73df')->after('favicon_path');
            $table->string('secondary_color', 7)->default('#858796')->after('primary_color');
            $table->string('sidebar_color', 7)->default('#4e73df')->after('secondary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'logo_path',
                'favicon_path',
                'primary_color',
                'secondary_color',
                'sidebar_color'
            ]);
        });
    }
};
