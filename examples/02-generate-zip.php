<?php

declare(strict_types=1);

// Generate a complete favicon package and save it as a ZIP.
// Usage: php examples/02-generate-zip.php [image] [output.zip]

require __DIR__ . '/bootstrap.php';

$image = $argv[1] ?? __DIR__ . '/logo.png';
$zip = $argv[2] ?? __DIR__ . '/favicons.zip';

$client = makefav_client();
try {
    $client->generateZip($image, $zip, [
        'app_name' => 'My Website',
        'short_name' => 'MySite',
        'theme_color' => '#2f9e68',
        'background' => 'transparent',
        'padding' => 6,         // % of empty space around the logo
        'radius' => 0,          // rounded corners in %
        'ico_sizes' => '16,32,48',
    ]);
} catch (MakeFav\MakeFavException $e) {
    fwrite(STDERR, "API error [{$e->getErrorCode()}]: {$e->getMessage()}\n");
    exit(1);
}

printf("Saved %s (%s kB)\n", $zip, number_format(filesize($zip) / 1024, 1));
if ($rl = $client->rateLimit()) {
    printf("Requests left this hour: %d/%d\n", $rl['remaining'], $rl['limit']);
}
echo "Unzip it into your web root and paste the code from readme-makefav.txt into <head>.\n";
