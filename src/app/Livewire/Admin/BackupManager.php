<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Traits\BasicHelper;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupManager extends Component
{
    use BasicHelper;

    public bool $isRunning = false;

    public function runBackup(): void
    {
        $this->isRunning = true;

        try {
            Artisan::call('app:daily-backup');

            $this->showFlash([
                'type'    => 'success',
                'message' => 'Backup completed successfully for all databases.',
            ]);
        } catch (\Exception $e) {
            $this->showFlash([
                'type'    => 'error',
                'message' => 'Backup failed: ' . $e->getMessage(),
            ]);
        }

        $this->isRunning = false;
    }

    public function downloadBackup(string $type, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = basename($filename);
        $path     = "backups/{$type}/{$filename}";

        if (!Storage::disk('local')->exists($path)) {
            $this->showFlash([
                'type'    => 'error',
                'message' => 'Backup file not found.',
            ]);
            return response()->noContent();
        }

        return Storage::disk('local')->download($path, $filename);
    }

    public function deleteBackup(string $type, string $filename): void
    {
        $filename = basename($filename);
        $path     = "backups/{$type}/{$filename}";

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
            $this->showFlash([
                'type'    => 'warning',
                'message' => 'Backup file deleted.',
            ]);
        }
    }

    #[Computed()]
    public function centralBackups(): array
    {
        return $this->getBackupFiles('central');
    }

    #[Computed()]
    public function tenantBackups(): array
    {
        return $this->getBackupFiles('tenants');
    }

    #[Computed()]
    public function totalSize(): string
    {
        $bytes = 0;
        foreach (Storage::disk('local')->files('backups/central') as $file) {
            $bytes += Storage::disk('local')->size($file);
        }
        foreach (Storage::disk('local')->files('backups/tenants') as $file) {
            $bytes += Storage::disk('local')->size($file);
        }
        return $this->formatBytes($bytes);
    }

    #[Computed()]
    public function lastBackupTime(): ?string
    {
        $latest = null;
        foreach (['central', 'tenants'] as $type) {
            foreach (Storage::disk('local')->files("backups/{$type}") as $file) {
                $time = Storage::disk('local')->lastModified($file);
                if (!$latest || $time > $latest) {
                    $latest = $time;
                }
            }
        }
        return $latest ? Carbon::createFromTimestamp($latest)->diffForHumans() : null;
    }

    private function getBackupFiles(string $type): array
    {
        $files     = [];
        $directory = "backups/{$type}";

        if (!Storage::disk('local')->exists($directory)) {
            return [];
        }

        foreach (Storage::disk('local')->files($directory) as $file) {
            $files[] = [
                'name'      => basename($file),
                'size'      => $this->formatBytes(Storage::disk('local')->size($file)),
                'date'      => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->format('d M Y H:i'),
                'timestamp' => Storage::disk('local')->lastModified($file),
            ];
        }

        usort($files, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $files;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function render()
    {
        return view('livewire.admin.backup-manager')
            ->layout('components.layouts.app', [
                'pageName' => 'Backups',
                'links'    => ['System', 'Backups'],
            ]);
    }
}
