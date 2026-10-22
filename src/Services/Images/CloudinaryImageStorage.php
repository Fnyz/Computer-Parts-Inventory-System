<?php

declare(strict_types=1);

namespace App\Services\Images;

/**
 * Uploads images to Cloudinary with a signed request (no SDK needed).
 * Configure with CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
 */
final class CloudinaryImageStorage implements ImageStorage
{
    public function __construct(
        private readonly string $cloudName,
        private readonly string $apiKey,
        private readonly string $apiSecret,
    ) {
    }

    public static function fromUrl(string $url): self
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'cloudinary' || empty($parts['user']) || empty($parts['pass']) || empty($parts['host'])) {
            throw new \InvalidArgumentException('CLOUDINARY_URL must look like cloudinary://API_KEY:API_SECRET@CLOUD_NAME');
        }

        return new self($parts['host'], rawurldecode($parts['user']), rawurldecode($parts['pass']));
    }

    public function store(array $upload, string $mime, string $folder): string
    {
        $params = ['folder' => 'partsdesk/' . trim($folder, '/'), 'timestamp' => (string) time()];
        $response = $this->post('image/upload', $params + ['file' => new \CURLFile($upload['tmp_name'], $mime)]);
        if (empty($response['secure_url'])) {
            throw new \RuntimeException('Cloudinary did not return an image URL: ' . json_encode($response));
        }

        return (string) $response['secure_url'];
    }

    public function delete(string $url): void
    {
        // https://res.cloudinary.com/<cloud>/image/upload/v123/partsdesk/products/abc.jpg -> partsdesk/products/abc
        if (!preg_match('~/image/upload/(?:v\d+/)?(.+)\.[a-z0-9]+$~i', $url, $m)) {
            return;
        }
        try {
            $this->post('image/destroy', ['public_id' => $m[1], 'timestamp' => (string) time()]);
        } catch (\Throwable) {
            // An old image left behind is harmless; don't block the save
        }
    }

    private function post(string $endpoint, array $fields): array
    {
        $signed = array_filter($fields, static fn ($v) => !$v instanceof \CURLFile);
        ksort($signed);
        $toSign = urldecode(http_build_query($signed));
        $fields += ['api_key' => $this->apiKey, 'signature' => sha1($toSign . $this->apiSecret)];

        $ch = curl_init("https://api.cloudinary.com/v1_1/{$this->cloudName}/{$endpoint}");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $fields, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false || $status >= 400) {
            throw new \RuntimeException("Cloudinary {$endpoint} failed ({$status}): " . (is_string($body) ? $body : curl_error($ch)));
        }
        $data = json_decode((string) $body, true);

        return is_array($data) ? $data : [];
    }
}
