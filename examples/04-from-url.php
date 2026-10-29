<?php

declare(strict_types=1);

// Generate icons from an image that is already online.
// Usage: php examples/04-from-url.php https://example.com/logo.png [output.zip]

require __DIR__ . '/bootstrap.php';

$url = $argv[1] ?? '';
if (!preg_match('~^https?://~', $url)) {
    fwrite(STDERR, "Usage: php examples/04-from-url.php https://example.com/logo.png [output.zip]\n");
    exit(1);
}
$zip = $argv[2] ?? __DIR__ . '/favicons-from-url.zip';

try {
    $bytes = makefav_client()->generateFromUrl($url, ['app_name' => 'My Website'], 'zip');
} catch (MakeFav\MakeFavException $e) {
    fwrite(STDERR, "API error [{$e->getErrorCode()}]: {$e->getMessage()}\n");
    exit(1);
}
file_put_contents($zip, $bytes);
printf("Saved %s (%s kB)\n", $zip, number_format(strlen($bytes) / 1024, 1));
