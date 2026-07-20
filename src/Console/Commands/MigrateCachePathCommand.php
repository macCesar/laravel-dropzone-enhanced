<?php

namespace MacCesar\LaravelDropzoneEnhanced\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateCachePathCommand extends Command
{
  protected $signature = 'dropzoneenhanced:migrate-cache-path
                          {--disk= : Storage disk to use (default: from config)}
                          {--from=cache : Old thumbnail cache directory}
                          {--to= : New thumbnail cache directory (default: from config)}
                          {--delete : Delete the old directory instead of moving it (thumbnails regenerate on demand)}
                          {--force : Skip confirmation prompt}';

  protected $description = 'Move thumbnails from the legacy cache directory to the current thumbnail_cache_path';

  public function handle(): int
  {
    $disk = $this->option('disk') ?: config('dropzone.storage.disk', 'public');
    $from = trim((string) $this->option('from'), '/');
    $to = trim((string) ($this->option('to') ?: config('dropzone.storage.thumbnail_cache_path', '.cache')), '/');
    $storage = Storage::disk($disk);

    if ($from === '' || $to === '' || $from === $to) {
      $this->error("Invalid directories: from '{$from}' to '{$to}'.");

      return self::FAILURE;
    }

    if (!$storage->exists($from)) {
      $this->info("Old cache directory '{$from}' does not exist. Nothing to migrate.");

      return self::SUCCESS;
    }

    $action = $this->option('delete') ? "Delete '{$from}'" : "Move '{$from}' to '{$to}'";
    if (!$this->option('force') && !$this->confirm("{$action} on disk '{$disk}'?")) {
      $this->info('Cancelled.');

      return self::SUCCESS;
    }

    if ($this->option('delete')) {
      $storage->deleteDirectory($from);

      $this->info("Old thumbnail cache deleted: {$from}");

      return self::SUCCESS;
    }

    $moved = 0;
    foreach ($storage->allFiles($from) as $file) {
      $target = $to . substr($file, strlen($from));

      // A thumbnail already regenerated at the new path wins over the old copy
      if ($storage->exists($target)) {
        $storage->delete($file);
        continue;
      }

      $storage->move($file, $target);
      $moved++;
    }

    $storage->deleteDirectory($from);

    $this->info("Thumbnail cache migrated: {$moved} files moved from '{$from}' to '{$to}'.");

    return self::SUCCESS;
  }
}
