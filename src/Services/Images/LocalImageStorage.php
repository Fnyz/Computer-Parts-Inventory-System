<?php

declare(strict_types=1);

namespace App\Services\Images;

/** Saves images under public/uploads. Fine on your own computer; not on Render's free plan. */
final class LocalImageStorage implements ImageStorage
{
    public function __construct(private readonly string $root)
    {
    }

    public function store(array $upload, string $mime, string $folder): string
    {
        $dir = $this->root . '/' . trim($folder, '/');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Can't create the upload folder {$dir}");
        }
        // Random name and an extension from the real file type, never from the uploaded name
        $name = bin2hex(random_bytes(12)) . '.' . ImageUploads::TYPES[$mime];
        $target = $dir . '/' . $name;
        $moved = is_uploaded_file($upload['tmp_name'])
            ? move_uploaded_file($upload['tmp_name'], $target)
            : (PHP_SAPI === 'cli' && copy($upload['tmp_name'], $target)); // tests send files that weren't uploaded over HTTP
        if (!$moved) {
            throw new \RuntimeException('Saving the uploaded image failed.');
        }

        return url('/uploads/' . trim($folder, '/') . '/' . $name);
    }

    public function delete(string $url): void
    {
        $prefix = url('/uploads/');
        if (!str_starts_with($url, $prefix)) {
            return;
        }
        $path = realpath($this->root . '/' . substr($url, strlen($prefix)));
        if ($path !== false && str_starts_with($path, (string) realpath($this->root)) && is_file($path)) {
            unlink($path);
        }
    }
}
