<?php

declare(strict_types=1);

// Generate icons, write them straight into a folder and print the <head> HTML.
// Handy for build scripts and deployments.
// Usage: php examples/03-generate-to-folder.php [image] [output-dir]

require __DIR__ . '/bootstrap.php';

$image = $argv[1] ?? __DIR__ . '/logo.png';
$dir = $argv[2] ?? __DIR__ . '/favicons';

try {
    $package = makefav_client()->generateJson($image, [
        'app_name' => 'My Website',
        'theme_color' => '#ffffff',
        'path' => '/',          // where the icons will live on your server
    ]);
} catch (MakeFav\MakeFavException $e) {
    fwrite(STDERR, "API error [{$e->getErrorCode()}]: {$e->getMessage()}\n");
    exit(1);
}

$files = MakeFav\MakeFavClient::saveFiles($package, $dir);
printf("Wrote %d files to %s\n\n", count($files), $dir);
echo "Paste this into the <head> of your pages:\n\n", $package['html'], "\n";
