<?php

namespace MacCesar\LaravelDropzoneEnhanced\Services;

use Illuminate\Support\Facades\Storage;

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

      if (!file_exists($sourceFullPath)) {
        return false;
      }

      // Get image info
      $imageInfo = getimagesize($sourceFullPath);
      if (!$imageInfo) {
        return false;
      }

      $sourceWidth = $imageInfo[0];
      $sourceHeight = $imageInfo[1];
      $sourceMimeType = $imageInfo['mime'];
      
      // Determine output format
      if ($outputFormat) {
        $outputMimeType = 'image/' . ($outputFormat === 'jpg' ? 'jpeg' : $outputFormat);
      } else {
        // Auto-detect from thumbnail path extension
        $extension = strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION));
        $outputMimeType = match($extension) {
          'jpg', 'jpeg' => 'image/jpeg',
          'png' => 'image/png',
          'gif' => 'image/gif',
          'webp' => 'image/webp',
          default => $sourceMimeType // Fallback to source format
        };
      }

      // Create source image resource based on type
      $sourceImage = self::createImageFromFile($sourceFullPath, $sourceMimeType);
      if (!$sourceImage) {
        return false;
      }

      // Calculate dimensions maintaining aspect ratio with crop
      $sourceRatio = $sourceWidth / $sourceHeight;
      $targetRatio = $width / $height;

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

      // Create thumbnail image
      $thumbnail = imagecreatetruecolor($width, $height);

      // Preserve transparency for PNG/GIF/WebP
      if ($outputMimeType === 'image/png' || $outputMimeType === 'image/gif' || $outputMimeType === 'image/webp') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
      }

      // Resize and crop
      imagecopyresampled(
        $thumbnail,
        $sourceImage,
        0,
        0,
        $cropX,
        $cropY,
        $width,
        $height,
        $newWidth,
        $newHeight
      );

      // Save thumbnail
      $thumbnailFullPath = Storage::disk($disk)->path($thumbnailPath);

      // Create directory if it doesn't exist
      $thumbnailDir = dirname($thumbnailFullPath);
      if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
      }

      $success = self::saveImage($thumbnail, $thumbnailFullPath, $outputMimeType, $quality);

      // Clean up memory
      imagedestroy($sourceImage);
      imagedestroy($thumbnail);

      return $success;
    } catch (\Exception $e) {
      \Log::error('Thumbnail generation failed: ' . $e->getMessage(), [
        'source' => $sourcePath,
        'thumbnail' => $thumbnailPath
      ]);
      return false;
    }
  }

  /**
   * Create image resource from file
   */
  private static function createImageFromFile($filePath, $mimeType)
  {
    switch ($mimeType) {
      case 'image/jpeg':
        return imagecreatefromjpeg($filePath);
      case 'image/png':
        return imagecreatefrompng($filePath);
      case 'image/gif':
        return imagecreatefromgif($filePath);
      case 'image/webp':
        return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($filePath) : false;
      default:
        return false;
    }
  }

  /**
   * Save image to file
   */
  private static function saveImage($image, $filePath, $mimeType, $quality)
  {
    switch ($mimeType) {
      case 'image/jpeg':
        return imagejpeg($image, $filePath, $quality);
      case 'image/png':
        // PNG quality is 0-9 (compression level), convert from 0-100
        $pngQuality = 9 - floor(($quality / 100) * 9);
        return imagepng($image, $filePath, $pngQuality);
      case 'image/gif':
        return imagegif($image, $filePath);
      case 'image/webp':
        return function_exists('imagewebp') ? imagewebp($image, $filePath, $quality) : false;
      default:
        return false;
    }
  }
}
