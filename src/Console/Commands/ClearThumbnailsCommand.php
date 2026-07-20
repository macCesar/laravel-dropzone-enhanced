<?php

namespace MacCesar\LaravelDropzoneEnhanced\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearThumbnailsCommand extends Command
{
  protected $signature = 'dropzoneenhanced:clear-thumbnails
                          {--disk= : Storage disk to use (default: from config)}
                          {--keep= : Comma-separated dimension directories to preserve (e.g. 640x360,960x540); crop variants of a kept dimension are preserved too}
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

    $keep = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('keep')))));

    if ($keep === []) {
      if (!$this->option('force') && !$this->confirm("Delete all thumbnails in '{$cachePath}' on disk '{$disk}'?")) {
        $this->info('Cancelled.');

        return self::SUCCESS;
      }

      $storage->deleteDirectory($cachePath);

      $this->info("Thumbnail cache cleared: {$cachePath}");

      return self::SUCCESS;
    }

    if (!$this->option('force') && !$this->confirm("Delete all thumbnails in '{$cachePath}' on disk '{$disk}' except " . implode(', ', $keep) . '?')) {
      $this->info('Cancelled.');

      return self::SUCCESS;
    }

    $deleted = 0;
    foreach ($storage->allDirectories($cachePath) as $directory) {
      $basename = basename($directory);

      // Only dimension directories (e.g. 640x360 or 640x360_top) are candidates
      if (!preg_match('/^(\d+x\d+)(_.+)?$/', $basename, $matches)) {
        continue;
      }

      if (in_array($basename, $keep, true) || in_array($matches[1], $keep, true)) {
        continue;
      }

      if ($storage->exists($directory)) {
        $storage->deleteDirectory($directory);
        $deleted++;
      }
    }

    $this->info("Thumbnail cache cleaned: {$deleted} dimension directories deleted, kept " . implode(', ', $keep) . '.');

    return self::SUCCESS;
  }
}
