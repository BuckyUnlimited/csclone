<?php

namespace App\Http\Controllers\API;

use App\Jobs\CreateBackupJob;
use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    private $disk;

    private $folder;

    public function __construct()
    {
        $this->disk = Storage::disk(config('backup.backup.destination.disks')[0]);

        $this->folder = config('backup.backup.name');
    }

    public function createBackup()
    {
        CreateBackupJob::dispatch();
        return response([
            'message' => 'Backup process started.'
        ], 202);
    }

    public function getBackups()
    {
        $backups = [];
        $total_backups = 0;
        $total_size = 0;

        try {
            $files = $this->disk->files($this->folder);

            foreach ($files as $file) {
                $filename = basename($file);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                if ($extension !== 'zip') {
                    continue;
                }
                $size = $this->disk->size($file);
                $size_human = $this->formatBytes($size);
                $date = $this->disk->lastModified($file);
                $date_human = date('Y-m-d H:i:s', $date);

                $total_backups++;
                $total_size += $size;

                $backups[] = [
                    'filename' => $filename,
                    'size' => $size,
                    'date' => $date,
                    'size_human' => $size_human,
                    'date_human' => $date_human,
                ];
            }
            $backups = array_reverse($backups);
        } catch (Exception $e) {
            return response([
                'message' => 'Failed to retrieve backups'
            ], 500);
        }

        return response([
            'backups' => $backups,
            'total_backups' => $total_backups,
            'total_size' => $total_size,
            'total_size_human' => $this->formatBytes($total_size),
        ], 200);
    }

    public function downloadBackup($filename)
    {
        $path = $this->folder . '/' . $filename;

        if (!$this->disk->exists($path)) {
            return response([
                'message' => 'Backup file not found.'
            ], 404);
        }

        try {
            return response()->download($this->disk->path($path));
        } catch (Exception $e) {
            return response([
                'message' => 'Failed to download backup file.'
            ], 500);
        }
    }

    public function deleteBackup($filename)
    {
        $path = $this->folder . '/' . $filename;

        if (!$this->disk->exists($path)) {
            return response([
                'message' => 'Backup file not found.'
            ], 404);
        }

        try {
            $this->disk->delete($path);
        } catch (Exception $e) {
            return response([
                'message' => 'Failed to delete backup file.',
            ], 500);
        }

        return response([
            'message' => 'Backup deleted successfully.'
        ], 200);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
