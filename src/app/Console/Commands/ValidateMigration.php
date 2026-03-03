<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateMigration extends Command
{
    protected $signature = 'mendyfi:validate-migration
                            {--strict : Exit with non-zero on any failure}
                            {--mysql-manifest= : Path to MySQL row count manifest JSON}';

    protected $description = 'Validate PostgreSQL migration: table existence, row counts, FK integrity, sequences';

    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('==========================================');
        $this->info('  MendyFi Migration Validator');
        $this->info('==========================================');
        $this->info('');

        // Load MySQL manifest if provided (row counts from pre-migration)
        $manifest = [];
        if ($path = $this->option('mysql-manifest')) {
            if (file_exists($path)) {
                $manifest = json_decode(file_get_contents($path), true) ?? [];
                $this->info("Loaded MySQL manifest: " . count($manifest) . " databases");
            } else {
                $this->warn("Manifest file not found: $path");
            }
        }

        // Validate central database
        $this->info('--- Central Database ---');
        $this->validateDatabase('central', $manifest['central'] ?? []);

        // Validate each tenant database
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $this->info('');
            $this->info("--- Tenant: {$tenant->id} ---");
            tenancy()->initialize($tenant);
            $dbName = DB::connection()->getDatabaseName();
            $this->validateDatabase($dbName, $manifest[$tenant->id] ?? []);
        }
        tenancy()->end();

        // Summary
        $this->info('');
        $this->info('==========================================');
        $total = $this->passed + $this->failed;
        if ($this->failed === 0) {
            $this->info("  PASS: All $total checks passed");
        } else {
            $this->error("  FAIL: {$this->failed} of $total checks failed");
            foreach ($this->failures as $f) {
                $this->error("    - $f");
            }
        }
        $this->info('==========================================');
        $this->info('');

        if ($this->option('strict') && $this->failed > 0) {
            return 1;
        }

        return 0;
    }

    private function validateDatabase(string $label, array $expectedCounts): void
    {
        // Check 1: Expected tables exist
        $expectedTables = $this->getExpectedTables($label);
        foreach ($expectedTables as $table) {
            if (Schema::hasTable($table)) {
                $this->pass("Table '$table' exists");
            } else {
                $this->fail("Table '$table' MISSING");
            }
        }

        // Check 2: Row counts match manifest (if provided)
        if (!empty($expectedCounts)) {
            foreach ($expectedCounts as $table => $count) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $actual = DB::table($table)->count();
                if ($actual === (int) $count) {
                    $this->pass("Row count '$table': $actual (matches)");
                } else {
                    $this->fail("Row count '$table': expected $count, got $actual");
                }
            }
        }

        // Check 3: Sequence values are valid (next_val > max(id))
        $sequences = DB::select("
            SELECT c.table_name, c.column_name,
                   pg_get_serial_sequence(c.table_name, c.column_name) as seq_name
            FROM information_schema.columns c
            WHERE c.column_default LIKE 'nextval%'
              AND c.table_schema = 'public'
        ");

        foreach ($sequences as $seq) {
            if (!$seq->seq_name) {
                continue;
            }
            $maxVal = DB::selectOne("SELECT COALESCE(MAX({$seq->column_name}), 0) as m FROM {$seq->table_name}")->m;
            $nextVal = DB::selectOne("SELECT last_value FROM {$seq->seq_name}")->last_value;
            if ($nextVal >= $maxVal) {
                $this->pass("Sequence '{$seq->seq_name}': {$nextVal} >= max({$maxVal})");
            } else {
                $this->fail("Sequence '{$seq->seq_name}': {$nextVal} < max({$maxVal})");
            }
        }

        // Check 4: Foreign key constraints are valid (no orphan references)
        $fks = DB::select("
            SELECT
                tc.constraint_name,
                tc.table_name,
                kcu.column_name,
                ccu.table_name AS foreign_table,
                ccu.column_name AS foreign_column
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema = 'public'
        ");

        foreach ($fks as $fk) {
            $orphans = DB::selectOne("
                SELECT COUNT(*) as cnt FROM {$fk->table_name} t
                LEFT JOIN {$fk->foreign_table} f ON t.{$fk->column_name} = f.{$fk->foreign_column}
                WHERE t.{$fk->column_name} IS NOT NULL AND f.{$fk->foreign_column} IS NULL
            ");
            if ($orphans->cnt === 0) {
                $this->pass("FK '{$fk->constraint_name}': no orphan rows");
            } else {
                $this->fail("FK '{$fk->constraint_name}': {$orphans->cnt} orphan rows in {$fk->table_name}");
            }
        }
    }

    private function getExpectedTables(string $label): array
    {
        if ($label === 'central') {
            return ['users', 'tenants', 'domains', 'app_settings'];
        }

        // Tenant tables (from migrations/tenant/)
        return [
            'users', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens',
            'resellers', 'hotspot_profiles', 'hotspot_vouchers', 'app_settings',
            'sales_records', 'user_settings', 'voucher_templates', 'jobs',
            'unified_profiles', 'permissions', 'roles', 'model_has_permissions',
            'model_has_roles', 'role_has_permissions',
            'fair_use_policies', 'bind_fair_use_policies', 'active_fair_use_policies',
        ];
    }

    private function pass(string $message): void
    {
        $this->passed++;
        $this->line("  <fg=green>PASS</> $message");
    }

    private function fail(string $message): void
    {
        $this->failed++;
        $this->failures[] = $message;
        $this->line("  <fg=red>FAIL</> $message");
    }
}
