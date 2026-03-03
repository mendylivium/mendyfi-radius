<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DailyBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-backup';

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
        // Backup central database
        $this->backupCentralDatabase();

        // Backup each tenant database
        Tenant::all()->each(function ($tenant) {
            tenancy()->initialize($tenant);
            $this->backupTenantDatabase($tenant->id);
        });

        // End tenant context to clean up
        tenancy()->end();

        $this->info("All databases have been backed up.");
    }

    protected function backupCentralDatabase()
    {
        $centralDatabaseName = env('DB_DATABASE');

        $time = now()->format("Y-m-d");

        $file= "central_database_backup-{$time}.sql";
        $dbPassword = env('DB_PASSWORD');
        $dbUsername = env('DB_USERNAME');
        $dbHost = env('DB_HOST');

        $command = "PGPASSWORD='$dbPassword' pg_dump -h $dbHost -U $dbUsername $centralDatabaseName > /var/www/html/storage/app/backups/central/{$file}";
        exec($command);

        $this->cleanupOldCentralBackups();
        $this->info("Central database backed up to $file");
    }

    protected function backupTenantDatabase($tenantId)
    {
        $databaseName = DB::connection()->getDatabaseName();
        $time = now()->format("Y-m-d");

        // Look up readable username for this tenant
        $username = DB::connection(config('tenancy.database.central_connection'))
            ->table('domains')
            ->where('tenant_id', $tenantId)
            ->value('username') ?? $tenantId;

        $file = "{$username}_backup-{$time}.sql";
        $dbPassword = env('DB_PASSWORD');
        $dbUsername = env('DB_USERNAME');
        $dbHost = env('DB_HOST');

        $command = "PGPASSWORD='$dbPassword' pg_dump -h $dbHost -U $dbUsername $databaseName > /var/www/html/storage/app/backups/tenants/{$file}";
        exec($command);

        $this->cleanupOldTenantBackups();
        $this->info("Database for tenant {$username} ({$tenantId}) backed up to $file");
    }

    protected function cleanupOldCentralBackups()
    {
        $files = glob("/var/www/html/storage/app/backups/central/central_database_backup-*.sql");
    
        $sevenDaysAgo = now()->subDays(env('DB_BACKUP_INTERVAL', 7));
    
        foreach ($files as $file) {
            if (filemtime($file) < $sevenDaysAgo->timestamp) {
                unlink($file);  // Delete the file
                $this->info("Deleted old backup: $file");
            }
        }
    }

    protected function cleanupOldTenantBackups()
    {
        $files = glob("/var/www/html/storage/app/backups/tenants/*_backup-*.sql");
    
        $sevenDaysAgo = now()->subDays(env('DB_BACKUP_INTERVAL', 7));
    
        foreach ($files as $file) {
            if (filemtime($file) < $sevenDaysAgo->timestamp) {
                unlink($file);  // Delete the file
                $this->info("Deleted old backup: $file");
            }
        }
    }
    
}
