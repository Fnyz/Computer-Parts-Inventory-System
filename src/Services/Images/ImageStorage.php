<?php

declare(strict_types=1);

namespace App\Services\Images;

interface ImageStorage
{
    /**
     * Saves a validated upload and returns the URL to show it with.
     *
     * @param array{tmp_name: string, size: int, error: int} $upload
     */
    public function store(array $upload, string $mime, string $folder): string;

    public function delete(string $url): void;
}
