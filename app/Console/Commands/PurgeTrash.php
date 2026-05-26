<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PurgeTrash extends Command
{
    protected $signature = 'files:purge-trash';
    protected $description = 'Permanently delete trashed files older than 30 days';
    public function handle()
    {
        $cutoff = now()->subDays(30);
        $trashed = \App\Models\TrashedFile::where('created_at', '<', $cutoff)->get();

        foreach ($trashed as $file) {
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($file->trash_path)) {
                if ($file->is_directory) {
                    \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory($file->trash_path);
                } else {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($file->trash_path);
                }
            }
            $file->delete();
        }

        $this->info("Purged {$trashed->count()} files from trash.");
    }
}
