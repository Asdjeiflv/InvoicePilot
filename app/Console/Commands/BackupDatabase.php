<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep-days=90}';
    protected $description = 'Backup the database with automatic cleanup';

    public function handle(): int
    {
        $this->info('Starting database backup...');

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $localPath = storage_path('backups/' . $filename);

        if (!file_exists(storage_path('backups'))) {
            mkdir(storage_path('backups'), 0755, true);
        }

        $config = config('database.connections.mysql');
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($localPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($localPath)) {
            $this->error('Backup failed!');
            return self::FAILURE;
        }

        $this->info("Backup created: {$localPath}");

        // Cleanup old backups
        $keepDays = $this->option('keep-days');
        $cutoffTime = now()->subDays($keepDays)->timestamp;
        $files = glob(storage_path('backups/backup_*.sql'));
        $deletedCount = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} old backup(s)");
        }

        $this->info('Backup completed successfully!');
        return self::SUCCESS;
    }
}
