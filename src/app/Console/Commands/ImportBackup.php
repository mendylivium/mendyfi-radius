<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-backup {date}';

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
        $date = $this->argument('date');
        
        // Import central database
        $this->recreateDatabase(env("DB_DATABASE"));
        $this->importDatabase("central_database_backup-{$date}.sql", "central", env("DB_DATABASE"));

        $this->reconnectToDatabase(env("DB_DATABASE"));
        // Import each tenant's database backup
        Tenant::all()->each(function ($tenant) use ($date) {
            $prefix = config('tenancy.database.prefix');
            $tenantFile = "{$prefix}{$tenant->id}_backup-{$date}.sql";
            $tenantDB = "{$prefix}{$tenant->id}";
            $this->recreateDatabase($tenantDB);
            $this->importDatabase($tenantFile,"tenants",$tenantDB);
        });
    }

    protected function importDatabase($databaseName,$path = 'central', $dbName = 'central')
    {
        $dbPath = "/var/www/html/storage/app/backups/{$path}/{$databaseName}";

        if (!file_exists($dbPath)) {
            $this->error("Backup for $databaseName on specified date does not exist: $dbPath");
            return;
        }

        $dbPassword = env('DB_PASSWORD');
        $dbUsername = env('DB_USERNAME');
        $dbHost = env('DB_HOST');

        // Run the import command
        $command = "PGPASSWORD='$dbPassword' psql -h $dbHost -U $dbUsername $dbName < " . escapeshellarg($dbPath);

        $returnVar = null;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error("Failed to import database backup for $databaseName.");
        } else {
            $this->info("Successfully imported database backup for $databaseName.");
        }
    }

    protected function recreateDatabase($database)
    {
        try {
            // Drop the database if it exists, then create it again
            DB::statement("DROP DATABASE IF EXISTS \"$database\"");
            DB::statement("CREATE DATABASE \"$database\"");
            $this->info("Database `$database` recreated.");
        } catch (\Exception $e) {
            $this->error("Failed to recreate database `$database`: " . $e->getMessage());
        }
    }

    protected function reconnectToDatabase($database)
{
    try {
        $connection = config('tenancy.database.central_connection', config('database.default'));

        // Purge the connection to reset the database context
        DB::purge($connection);

        // Set the database connection to the new database
        config(["database.connections.{$connection}.database" => $database]);

        // Re-establish the connection to the new database
        DB::reconnect($connection);

        $this->info("Connected to database `$database`.");
    } catch (\Exception $e) {
        $this->error("Failed to connect to database `$database`: " . $e->getMessage());
    }

}
}
