<?php

namespace MacCesar\LaravelDropzoneEnhanced\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageProcessor
{
  /**
   * Generate a thumbnail for the given image
   *
   * @param string $sourcePath Path to the source image
   * @param string $thumbnailPath Path where to save the thumbnail
   * @param int $width Target width
   * @param int $height Target height
   * @param string $disk Storage disk to use
   * @param int $quality JPEG quality (0-100)
   * @param string|null $outputFormat Output format: 'jpg', 'png', 'webp', 'gif' (null = auto-detect from path)
   * @return bool Success status
   */
  public static function generateThumbnail($sourcePath, $thumbnailPath, $width, $height, $disk = 'public', $quality = 90, $outputFormat = null)
  {
    try {
      // Get the full path of the source image
      $sourceFullPath = Storage::disk($disk)->path($sourcePath);

      // Early validation (optimization #1: fail fast)
      if (!file_exists($sourceFullPath)) {
        Log::warning('Source image not found', ['path' => $sourceFullPath]);
        return false;
      }

      // Get image info with error handling
      $imageInfo = @getimagesize($sourceFullPath);
      if (!$imageInfo) {
        Log::warning('Invalid image file', ['path' => $sourceFullPath]);
        return false;
      }

      $sourceWidth = $imageInfo[0];
      $sourceHeight = $imageInfo[1];
      $sourceMimeType = $imageInfo['mime'];

      // Memory check (optimization #2: prevent memory exhaustion)
      if (!self::checkMemoryRequirements($sourceWidth, $sourceHeight, $width, $height)) {
        Log::error('Insufficient memory for image processing', [
          'source_size' => "{$sourceWidth}x{$sourceHeight}",
          'target_size' => "{$width}x{$height}"
        ]);
        return false;
      }

      // Determine output format with validation (optimization #3: better format handling)
      $outputMimeType = self::determineOutputMimeType($outputFormat, $thumbnailPath, $sourceMimeType);
      if (!$outputMimeType) {
        Log::error('Unsupported output format', ['format' => $outputFormat]);
        return false;
      }

      // Create source image resource based on type
      $sourceImage = self::createImageFromFile($sourceFullPath, $sourceMimeType);
      if (!$sourceImage) {
        Log::error('Failed to create image resource', ['mime_type' => $sourceMimeType]);
        return false;
      }

      // Apply EXIF orientation correction to source image before processing
      $sourceImage = self::correctImageOrientation($sourceImage, $sourceFullPath);

      // Calculate dimensions maintaining aspect ratio with crop (optimization #4: cleaner calculation)
      $cropData = self::calculateCropDimensions($sourceWidth, $sourceHeight, $width, $height);

      // Create thumbnail image with error checking
      $thumbnail = imagecreatetruecolor($width, $height);
      if (!$thumbnail) {
        imagedestroy($sourceImage);
        Log::error('Failed to create thumbnail canvas');
        return false;
      }

      // Setup transparency and background (optimization #5: better transparency handling)
      self::setupImageBackground($thumbnail, $outputMimeType, $width, $height);

      // Resize and crop with error handling
      $success = imagecopyresampled(
        $thumbnail,
        $sourceImage,
        0,
        0,
        $cropData['cropX'],
        $cropData['cropY'],
        $width,
        $height,
        $cropData['newWidth'],
        $cropData['newHeight']
      );

      if (!$success) {
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        Log::error('Image resampling failed');
        return false;
      }

      // Ensure directory exists (optimization #6: better directory handling)
      $thumbnailFullPath = Storage::disk($disk)->path($thumbnailPath);
      if (!self::ensureDirectoryExists(dirname($thumbnailFullPath))) {
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        return false;
      }

      // Save image with proper error handling
      $saveSuccess = self::saveImage($thumbnail, $thumbnailFullPath, $outputMimeType, $quality);

      // Clean up memory immediately (optimization #7: immediate cleanup)
      imagedestroy($sourceImage);
      imagedestroy($thumbnail);

      if (!$saveSuccess) {
        Log::error('Failed to save thumbnail', ['path' => $thumbnailFullPath]);
        return false;
      }

      return true;
    } catch (\Exception $e) {
      Log::error('Thumbnail generation exception', [
        'source' => $sourcePath,
        'thumbnail' => $thumbnailPath,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Check if there's enough memory for processing (optimization #8: memory management)
   *
   * @param int $sourceWidth
   * @param int $sourceHeight
   * @param int $targetWidth
   * @param int $targetHeight
   * @return bool
   */
  private static function checkMemoryRequirements($sourceWidth, $sourceHeight, $targetWidth, $targetHeight)
  {
    // Calculate memory needed (4 bytes per pixel for RGBA)
    $sourceMemory = $sourceWidth * $sourceHeight * 4;
    $targetMemory = $targetWidth * $targetHeight * 4;
    $totalNeeded = ($sourceMemory + $targetMemory) * 1.5; // 50% overhead

    // Get memory limit
    $memoryLimit = self::getMemoryLimit();
    if ($memoryLimit === -1) return true; // Unlimited

    // Use maximum 80% of available memory
    return $totalNeeded <= ($memoryLimit * 0.8);
  }

  /**
   * Get PHP memory limit in bytes (optimization #9: proper memory limit parsing)
   *
   * @return int
   */
  private static function getMemoryLimit()
  {
    $limit = ini_get('memory_limit');
    if ($limit == -1) return -1;

    $limit = strtolower(trim($limit));
    $bytes = (int) $limit;

    if (str_contains($limit, 'g')) {
      $bytes *= 1024 * 1024 * 1024;
    } elseif (str_contains($limit, 'm')) {
      $bytes *= 1024 * 1024;
    } elseif (str_contains($limit, 'k')) {
      $bytes *= 1024;
    }

    return $bytes;
  }

  /**
   * Determine output MIME type with validation (optimization #10: better format detection)
   *
   * @param string|null $outputFormat
   * @param string $thumbnailPath
   * @param string $sourceMimeType
   * @return string|null
   */
  private static function determineOutputMimeType($outputFormat, $thumbnailPath, $sourceMimeType)
  {
    if ($outputFormat) {
      return match ($outputFormat) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => function_exists('imagewebp') ? 'image/webp' : null,
        default => null
      };
    }

    // Auto-detect from path
    $extension = strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION));
    return match ($extension) {
      'jpg', 'jpeg' => 'image/jpeg',
      'png' => 'image/png',
      'gif' => 'image/gif',
      'webp' => function_exists('imagewebp') ? 'image/webp' : null,
      default => $sourceMimeType
    };
  }

  /**
   * Calculate crop dimensions (optimization #11: extracted for clarity)
   *
   * @param int $sourceWidth
   * @param int $sourceHeight
   * @param int $targetWidth
   * @param int $targetHeight
   * @return array
   */
  private static function calculateCropDimensions($sourceWidth, $sourceHeight, $targetWidth, $targetHeight)
  {
    $sourceRatio = $sourceWidth / $sourceHeight;
    $targetRatio = $targetWidth / $targetHeight;

    if ($sourceRatio > $targetRatio) {
      // Source is wider, crop width
      $newHeight = $sourceHeight;
      $newWidth = $sourceHeight * $targetRatio;
      $cropX = ($sourceWidth - $newWidth) / 2;
      $cropY = 0;
    } else {
      // Source is taller, crop height
      $newWidth = $sourceWidth;
      $newHeight = $sourceWidth / $targetRatio;
      $cropX = 0;
      $cropY = ($sourceHeight - $newHeight) / 2;
    }

    return [
      'newWidth' => (int) $newWidth,
      'newHeight' => (int) $newHeight,
      'cropX' => (int) $cropX,
      'cropY' => (int) $cropY,
    ];
  }

  /**
   * Setup image background and transparency (optimization #12: better background handling)
   *
   * @param resource $image
   * @param string $mimeType
   * @param int $width
   * @param int $height
   */
  private static function setupImageBackground($image, $mimeType, $width, $height)
  {
    if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'])) {
      // Preserve transparency
      imagealphablending($image, false);
      imagesavealpha($image, true);
      $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
      imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
    } else {
      // Fill with white for JPEG
      $white = imagecolorallocate($image, 255, 255, 255);
      imagefilledrectangle($image, 0, 0, $width, $height, $white);
    }
  }

  /**
   * Ensure directory exists (optimization #13: better directory creation)
   *
   * @param string $directory
   * @return bool
   */
  private static function ensureDirectoryExists($directory)
  {
    if (is_dir($directory)) {
      return true;
    }

    if (!mkdir($directory, 0755, true)) {
      Log::error('Failed to create directory', ['path' => $directory]);
      return false;
    }

    return true;
  }

  /**
   * Create image resource from file with better error handling (optimization #14)
   */
  private static function createImageFromFile($filePath, $mimeType)
  {
    return match ($mimeType) {
      'image/jpeg' => @imagecreatefromjpeg($filePath),
      'image/png' => @imagecreatefrompng($filePath),
      'image/gif' => @imagecreatefromgif($filePath),
      'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false,
      default => false
    };
  }

  /**
   * Save image to file with better quality handling (optimization #15)
   */
  private static function saveImage($image, $filePath, $mimeType, $quality)
  {
    return match ($mimeType) {
      'image/jpeg' => @imagejpeg($image, $filePath, max(0, min(100, $quality))),
      'image/png' => @imagepng($image, $filePath, self::convertQualityToPngCompression($quality)),
      'image/gif' => @imagegif($image, $filePath),
      'image/webp' => function_exists('imagewebp') ? @imagewebp($image, $filePath, max(0, min(100, $quality))) : false,
      default => false
    };
  }

  /**
   * Convert quality percentage to PNG compression level (optimization #16)
   *
   * @param int $quality
   * @return int
   */
  private static function convertQualityToPngCompression($quality)
  {
    // PNG: 0 = no compression, 9 = max compression
    // Quality: 100 = best quality (low compression), 0 = worst quality (high compression)
    return max(0, min(9, 9 - floor(($quality / 100) * 9)));
  }

  /**
   * Correct image orientation based on EXIF data
   *
   * @param resource|\GdImage $image
   * @param string $filePath
   * @return resource|\GdImage
   */
  public static function correctImageOrientation($image, $filePath)
  {
    // Check if EXIF functions are available
    if (!function_exists('exif_read_data')) {
      Log::info('EXIF functions not available, skipping orientation correction');
      return $image;
    }

    try {
      $exif = @exif_read_data($filePath);

      if (!$exif || !isset($exif['Orientation'])) {
        return $image; // No orientation data, return original
      }

      $orientation = $exif['Orientation'];

      switch ($orientation) {
        case 2: // Flip horizontal
          imageflip($image, IMG_FLIP_HORIZONTAL);
          break;
        case 3: // Rotate 180 degrees
          $image = imagerotate($image, 180, 0);
          break;
        case 4: // Flip vertical
          imageflip($image, IMG_FLIP_VERTICAL);
          break;
        case 5: // Rotate 90 degrees clockwise and flip horizontal
          $image = imagerotate($image, -90, 0);
          imageflip($image, IMG_FLIP_HORIZONTAL);
          break;
        case 6: // Rotate 90 degrees clockwise
          $image = imagerotate($image, 90, 0);
          break;
        case 7: // Rotate 90 degrees counter-clockwise and flip horizontal
          $image = imagerotate($image, -90, 0);
          imageflip($image, IMG_FLIP_HORIZONTAL);
          break;
        case 8: // Rotate 90 degrees counter-clockwise
          $image = imagerotate($image, -90, 0);
          break;
      }

      Log::info('Applied EXIF orientation correction', ['orientation' => $orientation]);
    } catch (\Exception $e) {
      Log::warning('Failed to read EXIF data or apply orientation', [
        'file' => $filePath,
        'error' => $e->getMessage()
      ]);
    }

    return $image;
  }

  /**
   * Correct original image file EXIF orientation in place
   *
   * @param string $filePath Full path to image file
   * @param string $mimeType Image MIME type
   * @return bool Success status
   */
  public static function correctOriginalImageInPlace($filePath, $mimeType)
  {
    if (!function_exists('exif_read_data') || !in_array($mimeType, ['image/jpeg', 'image/jpg'])) {
      return false;
    }

    try {
      // Check if EXIF orientation correction is needed
      $exif = @exif_read_data($filePath);
      if (!$exif || !isset($exif['Orientation']) || $exif['Orientation'] == 1) {
        return false; // No correction needed
      }

      // Create image resource
      $image = self::createImageFromFile($filePath, $mimeType);
      if (!$image) {
        return false;
      }

      // Apply EXIF orientation correction (reuse existing logic)
      $correctedImage = self::correctImageOrientation($image, $filePath);

      // Save corrected image back to original file
      $success = self::saveImage($correctedImage, $filePath, $mimeType, 95);

      // Clean up memory
      imagedestroy($image);
      if ($correctedImage !== $image) {
        imagedestroy($correctedImage);
      }

      if ($success) {
        Log::info('Applied EXIF correction to original image', ['file' => basename($filePath)]);
      }

      return $success;
    } catch (\Exception $e) {
      Log::warning('Failed to correct original image orientation', [
        'file' => basename($filePath),
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }
}
