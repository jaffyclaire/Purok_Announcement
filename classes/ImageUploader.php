<?php
class ImageUploader
{
    public const MAX_SIZE = 2097152; // 2 MB
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    public const ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP,
    ];

    /**
     * Validate an uploaded image file without moving it.
     * Returns ['valid' => true, 'tmp_path' => ..., 'target_path' => ..., 'relative_path' => ..., 'mime' => ...]
     * or ['valid' => false, 'error' => ...].
     */
    public static function validate($file, $subfolder = 'announcements')
    {
        if (!isset($file) || !is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valid' => false, 'error' => 'No file uploaded.', 'empty' => true];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload error occurred (code: ' . $file['error'] . ').'];
        }

        if ($file['size'] > self::MAX_SIZE) {
            return ['valid' => false, 'error' => 'Image size exceeds the 2 MB limit.'];
        }

        $tmpPath = $file['tmp_name'];
        if (php_sapi_name() !== 'cli' && !is_uploaded_file($tmpPath)) {
            return ['valid' => false, 'error' => 'Invalid upload request.'];
        }

        // 1. Check with finfo (real MIME type)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpPath);

        if (!array_key_exists($realMime, self::ALLOWED_MIME_TYPES)) {
            return ['valid' => false, 'error' => 'Invalid image format. Only JPG, PNG, and WebP images are allowed.'];
        }

        // 2. Check with getimagesize
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false || !in_array($imageInfo[2], self::ALLOWED_IMAGE_TYPES, true)) {
            return ['valid' => false, 'error' => 'The uploaded file is not a valid image.'];
        }

        // 3. Generate random filename - never use original name
        $extension = self::ALLOWED_MIME_TYPES[$realMime];
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;

        $targetDir = dirname(__DIR__) . '/uploads/' . trim($subfolder, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $randomName;
        $relativePath = 'uploads/' . trim($subfolder, '/') . '/' . $randomName;

        return [
            'valid'         => true,
            'tmp_path'      => $tmpPath,
            'target_path'   => $targetPath,
            'relative_path' => $relativePath,
            'filename'      => $randomName,
            'mime'          => $realMime,
        ];
    }

    /**
     * Move validated file to target path.
     */
    public static function moveFile($validatedInfo)
    {
        if (empty($validatedInfo['valid']) || empty($validatedInfo['tmp_path']) || empty($validatedInfo['target_path'])) {
            throw new RuntimeException('Cannot move invalid upload info.');
        }

        if (php_sapi_name() === 'cli') {
            if (!copy($validatedInfo['tmp_path'], $validatedInfo['target_path'])) {
                throw new RuntimeException('Failed to move uploaded file to destination.');
            }
            @unlink($validatedInfo['tmp_path']);
        } else {
            if (!move_uploaded_file($validatedInfo['tmp_path'], $validatedInfo['target_path'])) {
                throw new RuntimeException('Failed to move uploaded file to destination.');
            }
        }

        return true;
    }

    /**
     * Delete a file if it exists.
     */
    public static function deleteFile($relativePath)
    {
        if (empty($relativePath)) return;
        $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    /**
     * Normalize $_FILES['images'] array structure into a flat list of file arrays.
     */
    public static function normalizeFiles($files)
    {
        $normalized = [];
        if (!isset($files) || !is_array($files)) {
            return $normalized;
        }

        if (isset($files['name']) && is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if (!isset($files['error'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $normalized[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i] ?? 0,
                ];
            }
        } elseif (isset($files['error']) && $files['error'] !== UPLOAD_ERR_NO_FILE) {
            $normalized[] = $files;
        }

        return $normalized;
    }

    /**
     * Validate multiple files at once.
     */
    public static function validateMultiple($files, $subfolder = 'announcements', $maxFiles = 5)
    {
        $normalized = self::normalizeFiles($files);
        if (empty($normalized)) {
            return ['valid' => true, 'files' => []];
        }

        if (count($normalized) > $maxFiles) {
            return [
                'valid' => false,
                'error' => "You can upload a maximum of {$maxFiles} images per announcement. (" . count($normalized) . " provided)"
            ];
        }

        $validatedList = [];
        foreach ($normalized as $idx => $f) {
            $res = self::validate($f, $subfolder);
            if (!$res['valid']) {
                $fileNum = $idx + 1;
                $origName = htmlspecialchars($f['name'] ?? 'image');
                return [
                    'valid' => false,
                    'error' => "Image #{$fileNum} ({$origName}): " . $res['error']
                ];
            }
            $validatedList[] = $res;
        }

        return [
            'valid' => true,
            'files' => $validatedList
        ];
    }
}
