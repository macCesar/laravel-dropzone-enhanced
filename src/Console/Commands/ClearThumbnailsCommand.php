<?php

namespace MacCesar\LaravelDropzoneEnhanced\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearThumbnailsCommand extends Command
{
  protected $signature = 'dropzoneenhanced:clear-thumbnails
                          {--disk= : Storage disk to use (default: from config)}
                          {--force : Skip confirmation prompt}';

  protected $description = 'Delete all generated thumbnails from the central cache directory';

  public function handle(): int
  {
    $disk = $this->option('disk') ?: config('dropzone.storage.disk', 'public');
    $cachePath = config('dropzone.storage.thumbnail_cache_path', '.cache');
    $storage = Storage::disk($disk);

    if (!$storage->exists($cachePath)) {
      $this->info("Cache directory '{$cachePath}' is already empty or does not exist.");

      return self::SUCCESS;
    }

    if (!$this->option('force') && !$this->confirm("Delete all thumbnails in '{$cachePath}' on disk '{$disk}'?")) {
      $this->info('Cancelled.');

      return self::SUCCESS;
    }

    $storage->deleteDirectory($cachePath);

    $this->info("Thumbnail cache cleared: {$cachePath}");

    return self::SUCCESS;
  }
}
