<?php

declare(strict_types=1);

namespace MakeFav;

/**
 * Minimal, dependency-free client for the MakeFav Favicon API.
 *
 * Docs: https://makefav.com/developers
 * Get an API key (free account): https://makefav.com/account/api-keys
 */
final class MakeFavClient
{
    public const BASE_URL = 'https://makefav.com/api/v1';

    /** @var string */
    private $apiKey;
    /** @var string */
    private $baseUrl;
    /** @var int */
    private $timeout;
    /** @var array{limit:int,remaining:int,reset:int}|null */
    private $rateLimit;

    public function __construct(string $apiKey, string $baseUrl = self::BASE_URL, int $timeout = 60)
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key must not be empty.');
        }
        if (!\function_exists('curl_init')) {
            throw new \RuntimeException('The PHP cURL extension is required.');
        }
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Account info and current rate limit.
     *
     * @return array<string,mixed>
     */
    public function me(): array
    {
        return $this->json($this->request('GET', '/me'));
    }

    /**
     * List of all files a package can contain (public endpoint).
     *
     * @return array<int,array{name:string,size:mixed,group:string}>
     */
    public function sizes(): array
    {
        return $this->json($this->request('GET', '/sizes'))['files'];
    }

    /**
     * Generate a package from a local image and save the ZIP to $zipPath.
     *
     * @param array<string,scalar> $options See README / https://makefav.com/developers
     */
    public function generateZip(string $imagePath, string $zipPath, array $options = []): string
    {
        $body = $this->request('POST', '/generate', $this->multipart($imagePath, $options + ['format' => 'zip']));
        if (file_put_contents($zipPath, $body) === false) {
            throw new \RuntimeException("Cannot write $zipPath");
        }
        return $zipPath;
    }

    /**
     * Generate a package from a local image and get every file (base64) plus the HTML <head> code.
     *
     * @param array<string,scalar> $options
     * @return array{id:string,html:string,files:array<int,array{name:string,mime:string,size:int,base64:string}>}
     */
    public function generateJson(string $imagePath, array $options = []): array
    {
        return $this->json($this->request('POST', '/generate', $this->multipart($imagePath, $options + ['format' => 'json'])));
    }

    /**
     * Generate a package from a public image URL (format "json" or "zip").
     *
     * @param array<string,scalar> $options
     * @return array<string,mixed>|string Decoded JSON, or raw ZIP bytes when format=zip
     */
    public function generateFromUrl(string $imageUrl, array $options = [], string $format = 'json')
    {
        $payload = json_encode(['image_url' => $imageUrl, 'format' => $format] + $options);
        $body = $this->request('POST', '/generate', $payload, ['Content-Type: application/json']);
        return $format === 'zip' ? $body : $this->json($body);
    }

    /**
     * Write the files returned by generateJson()/generateFromUrl() into a directory.
     *
     * @param array{files:array<int,array{name:string,base64:string}>} $package
     * @return string[] Written file paths
     */
    public static function saveFiles(array $package, string $dir): array
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create $dir");
        }
        $written = [];
        foreach ($package['files'] as $file) {
            $name = basename($file['name']); // never trust paths from the network
            $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
            file_put_contents($path, base64_decode($file['base64'], true));
            $written[] = $path;
        }
        return $written;
    }

    /**
     * Rate limit reported by the last authenticated request.
     *
     * @return array{limit:int,remaining:int,reset:int}|null
     */
    public function rateLimit(): ?array
    {
        return $this->rateLimit;
    }

    /* ------------------------------------------------------------------ */

    /**
     * @param array<string,scalar> $options
     * @return array<string,mixed>
     */
    private function multipart(string $imagePath, array $options): array
    {
        if (!is_file($imagePath)) {
            throw new \InvalidArgumentException("Image not found: $imagePath");
        }
        $fields = ['image' => new \CURLFile($imagePath, (string)(mime_content_type($imagePath) ?: 'application/octet-stream'), basename($imagePath))];
        foreach ($options as $k => $v) {
            $fields[$k] = \is_bool($v) ? ($v ? '1' : '0') : (string)$v;
        }
        return $fields;
    }

    /**
     * @param array<string,mixed>|string|null $body
     * @param string[] $headers
     */
    private function request(string $method, string $path, $body = null, array $headers = []): string
    {
        $resHeaders = [];
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'makefav-php/1.0',
            CURLOPT_HTTPHEADER => array_merge(['Authorization: Bearer ' . $this->apiKey, 'Accept: application/json, application/zip'], $headers),
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$resHeaders): int {
                $parts = explode(':', $line, 2);
                if (\count($parts) === 2) {
                    $resHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return \strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new MakeFavException('Network error: ' . $err, 0, 'network_error');
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (isset($resHeaders['x-ratelimit-limit'])) {
            $this->rateLimit = [
                'limit' => (int)$resHeaders['x-ratelimit-limit'],
                'remaining' => (int)($resHeaders['x-ratelimit-remaining'] ?? 0),
                'reset' => (int)($resHeaders['x-ratelimit-reset'] ?? 0),
            ];
        }
        if ($status >= 400) {
            $data = json_decode((string)$response, true);
            $code = $data['error']['code'] ?? 'http_' . $status;
            $message = $data['error']['message'] ?? 'HTTP ' . $status;
            throw new MakeFavException($message, $status, $code);
        }
        return (string)$response;
    }

    /** @return array<string,mixed> */
    private function json(string $body): array
    {
        $data = json_decode($body, true);
        if (!\is_array($data)) {
            throw new MakeFavException('Unexpected response from the API.', 0, 'invalid_response');
        }
        return $data;
    }
}
