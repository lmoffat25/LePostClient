<?php

declare(strict_types=1);

namespace LePostClient\Post;

use LePostClient\Exceptions\ImageProcessingException;

/**
 * Handles image optimization for uploaded images.
 *
 * @since 1.0.0
 */
class ImageOptimizer {
    
    /**
     * JPEG compression quality (0-100)
     *
     * @var int
     */
    private int $jpeg_quality = 85;
    
    /**
     * PNG compression level (0-9)
     *
     * @var int
     */
    private int $png_compression = 7;
    
    /**
     * Size unit for file size display
     * 1024 = KB, 1048576 = MB
     * @var int
     */
    private int $size_unit = 1024;
    
    /**
     * Constructor.
     *
     * @param array $config Optional configuration parameters
     */
    public function __construct(array $config = []) {
        // Apply custom configuration if provided
        if (!empty($config['jpeg_quality']) && is_int($config['jpeg_quality'])) {
            $this->jpeg_quality = max(0, min(100, $config['jpeg_quality']));
        }
        
        if (!empty($config['png_compression']) && is_int($config['png_compression'])) {
            $this->png_compression = max(0, min(9, $config['png_compression']));
        }
    }
    
    /**
     * Optimize an image file
     *
     * @param string $file_path Path to the image file
     * @param string $original_url Original URL of the image (for error reporting)
     * @return string Path to the optimized image
     * @throws ImageProcessingException If optimization fails
     */
    public function optimize(string $file_path, string $original_url = ''): string {
        if (!file_exists($file_path)) {
            throw $this->createOptimizationException('File does not exist', $original_url);
        }
        
        try {
            // Get image info
            $image_info = $this->getImageInfo($file_path);
            if (!$image_info) {
                throw $this->createOptimizationException('Unable to get image information', $original_url);
            }
            
            // Log original image details
            error_log(sprintf(
                'LePostClient: Optimizing image - Type: %s, Size: %s KB, Dimensions: %dx%d', 
                $image_info['mime'],
                round(filesize($file_path) / $size_unit, 2),
                $image_info['width'],
                $image_info['height']
            ));
            
            // First compress the image
            $compressed_file = $this->compressImage($file_path, $image_info);
            if ($compressed_file && $compressed_file !== $file_path) {
                $file_path = $compressed_file;
                // Update image info after compression
                $image_info = $this->getImageInfo($file_path);
            }
            
            // Convert format if beneficial
            $converted_file = $this->convertFormat($file_path, $image_info);
            if ($converted_file && $converted_file !== $file_path) {
                $file_path = $converted_file;
            }
            
            // Log optimization results
            if (file_exists($file_path)) {
                $final_info = $this->getImageInfo($file_path);
                $original_size = filesize($file_path);
                error_log(sprintf(
                    'LePostClient: Image optimization complete - New size: %s KB, Dimensions: %dx%d, Type: %s', 
                    round($original_size / $size_unit, 2),
                    $final_info ? $final_info['width'] : 0,
                    $final_info ? $final_info['height'] : 0,
                    $final_info ? $final_info['mime'] : 'unknown'
                ));
            }
            
            return $file_path;
            
        } catch (\Throwable $e) {
            if ($e instanceof ImageProcessingException) {
                throw $e;
            }
            throw $this->createOptimizationException($e->getMessage(), $original_url, 0, $e);
        }
    }
    
    /**
     * Compress an image to reduce file size
     *
     * @param string $file_path Path to the image file
     * @param array $image_info Image information
     * @return string|null Path to the compressed image or null if compression failed
     */
    private function compressImage(string $file_path, array $image_info): ?string {
        $mime_type = $image_info['mime'];
        
        // Create image resource based on type
        $image = $this->createImageResource($file_path, $mime_type);
        if (!$image) {
            error_log('LePostClient: Failed to create image resource for compression');
            return null;
        }
        
        // Create temporary file for the compressed image
        $temp_file = $file_path . '.compressed';
        
        // Compress based on image type
        $success = $this->saveImageToFile($image, $temp_file, $mime_type);
        
        // Clean up
        imagedestroy($image);
        
        if (!$success) {
            error_log('LePostClient: Failed to save compressed image');
            @unlink($temp_file); // Try to clean up
            return null;
        }
        
        // Check if compression actually reduced the file size
        $original_size = filesize($file_path);
        $compressed_size = filesize($temp_file);
        
        if ($compressed_size < $original_size) {
            // Compression worked, replace original
            if (!rename($temp_file, $file_path)) {
                @unlink($temp_file); // Try to clean up
                error_log('LePostClient: Failed to replace original with compressed image');
                return null;
            }
            
            error_log(sprintf(
                'LePostClient: Image compressed from %s KB to %s KB (saved %s%%)',
                round($original_size / $size_unit, 2),
                round($compressed_size / $size_unit, 2),
                round(($original_size - $compressed_size) / $original_size * 100, 1)
            ));
            
            return $file_path;
        } else {
            // Compression didn't help, keep original
            @unlink($temp_file);
            error_log('LePostClient: Compression did not reduce file size, keeping original');
            return $file_path;
        }
    }
    
    /**
     * Convert image format if beneficial
     *
     * @param string $file_path Path to the image file
     * @param array $image_info Image information
     * @return string|null Path to the converted image or null if conversion failed
     */
    private function convertFormat(string $file_path, array $image_info): ?string {
        // Only convert PNGs without transparency to JPG for size reduction
        if ($image_info['mime'] !== 'image/png') {
            return $file_path; // Not a PNG, no conversion needed
        }
        
        // Check if PNG has transparency
        if ($this->hasPngTransparency($file_path)) {
            error_log('LePostClient: PNG has transparency, skipping conversion to JPG');
            return $file_path;
        }
        
        // Create image resource
        $image = $this->createImageResource($file_path, $image_info['mime']);
        if (!$image) {
            error_log('LePostClient: Failed to create image resource for format conversion');
            return null;
        }
        
        // Create a new file with .jpg extension
        $pathinfo = pathinfo($file_path);
        $jpg_file = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.jpg';
        
        // Set a white background (to replace transparency)
        $bg = imagecreatetruecolor($image_info['width'], $image_info['height']);
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefilledrectangle($bg, 0, 0, $image_info['width'], $image_info['height'], $white);
        imagecopy($bg, $image, 0, 0, 0, 0, $image_info['width'], $image_info['height']);
        
        // Save as JPG
        $success = imagejpeg($bg, $jpg_file, $this->jpeg_quality);
        
        // Clean up
        imagedestroy($image);
        imagedestroy($bg);
        
        if (!$success) {
            error_log('LePostClient: Failed to convert PNG to JPG');
            @unlink($jpg_file); // Try to clean up
            return null;
        }
        
        // Check if conversion actually reduced the file size
        $original_size = filesize($file_path);
        $converted_size = filesize($jpg_file);
        
        if ($converted_size < $original_size) {
            // Conversion worked, keep the JPG and delete the original PNG
            @unlink($file_path);
            
            error_log(sprintf(
                'LePostClient: Converted PNG to JPG, reducing size from %s KB to %s KB (saved %s%%)',
                round($original_size / $size_unit, 2),
                round($converted_size / $size_unit, 2),
                round(($original_size - $converted_size) / $original_size * 100, 1)
            ));
            
            return $jpg_file;
        } else {
            // Conversion didn't help, keep original PNG
            @unlink($jpg_file);
            error_log('LePostClient: JPG conversion did not reduce file size, keeping original PNG');
            return $file_path;
        }
    }
    
    /**
     * Check if a PNG image has transparency
     *
     * @param string $file_path Path to the PNG image
     * @return bool True if the image has transparency
     */
    private function hasPngTransparency(string $file_path): bool {
        $image = @imagecreatefrompng($file_path);
        if (!$image) {
            return false;
        }
        
        // Check if image has alpha channel
        $has_transparency = imagecolortransparent($image) != -1 || (imageistruecolor($image) && imagesavealpha($image));
        
        imagedestroy($image);
        return $has_transparency;
    }
    
    /**
     * Get information about an image file
     *
     * @param string $file_path Path to the image file
     * @return array|null Image information or null if failed
     */
    private function getImageInfo(string $file_path): ?array {
        $info = @getimagesize($file_path);
        if (!$info) {
            return null;
        }
        
        return [
            'width' => $info[0],
            'height' => $info[1],
            'mime' => $info['mime'],
        ];
    }
    
    /**
     * Create an image resource from a file
     *
     * @param string $file_path Path to the image file
     * @param string $mime_type MIME type of the image
     * @return \GdImage|resource|false Image resource or false if failed
     */
    private function createImageResource(string $file_path, string $mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return @imagecreatefromjpeg($file_path);
            case 'image/png':
                return @imagecreatefrompng($file_path);
            case 'image/gif':
                return @imagecreatefromgif($file_path);
            case 'image/webp':
                return @imagecreatefromwebp($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Save an image resource to a file
     *
     * @param \GdImage|resource $image Image resource
     * @param string $file_path Path to save the image
     * @param string $mime_type MIME type of the image
     * @return bool True if successful
     */
    private function saveImageToFile($image, string $file_path, string $mime_type): bool {
        switch ($mime_type) {
            case 'image/jpeg':
                return @imagejpeg($image, $file_path, $this->jpeg_quality);
            case 'image/png':
                // PNG quality is 0-9, where 0 is no compression and 9 is max compression
                return @imagepng($image, $file_path, $this->png_compression);
            case 'image/gif':
                return @imagegif($image, $file_path);
            case 'image/webp':
                // WebP quality is 0-100 like JPEG
                return @imagewebp($image, $file_path, $this->jpeg_quality);
            default:
                return false;
        }
    }
    
    /**
     * Create an optimization exception
     *
     * @param string $message Error message
     * @param string $url Original image URL
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     * @return ImageProcessingException
     */
    private function createOptimizationException(
        string $message, 
        string $url = '', 
        int $code = 0, 
        \Throwable $previous = null
    ): ImageProcessingException {
        if (!empty($url)) {
            return ImageProcessingException::optimizationFailed($url, $message, $code, $previous);
        }
        
        return new ImageProcessingException(
            sprintf('Image optimization failed: %s', $message),
            $code,
            $previous
        );
    }
} 