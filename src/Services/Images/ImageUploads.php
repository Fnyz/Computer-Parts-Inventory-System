<?php

declare(strict_types=1);

namespace App\Services\Images;

/** Checks uploads and picks where they are stored (Cloudinary on Render, public/uploads locally). */
final class ImageUploads
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /** Returns an error message, or null when the file is a usable image. Fills $mime. */
    public static function problem(array $upload, ?string &$mime = null): ?string
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || (int) ($upload['size'] ?? 0) > self::MAX_BYTES) {
            return 'The image is too large. Use one under 5 MB.';
        }
        if ($error !== UPLOAD_ERR_OK || !is_file((string) ($upload['tmp_name'] ?? ''))) {
            return 'The image could not be uploaded. Try again.';
        }
        // Trust the file's contents, not its name or the browser's claim
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        if (!isset(self::TYPES[$mime]) || @getimagesize($upload['tmp_name']) === false) {
            return 'Use a JPG, PNG, WEBP or GIF image.';
        }

        return null;
    }

    public static function storage(): ImageStorage
    {
        $cloudinary = env('CLOUDINARY_URL');

        return $cloudinary ? CloudinaryImageStorage::fromUrl($cloudinary) : new LocalImageStorage(BASE_DIR . '/public/uploads');
    }
}
