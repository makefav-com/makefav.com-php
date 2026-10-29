<?php

declare(strict_types=1);

// Works with Composer (vendor/autoload.php) or as a plain download.
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/../src/MakeFavException.php';
    require __DIR__ . '/../src/MakeFavClient.php';
}

/** API key from the MAKEFAV_API_KEY environment variable – never commit keys to Git. */
function makefav_client(): MakeFav\MakeFavClient
{
    $key = getenv('MAKEFAV_API_KEY') ?: '';
    if ($key === '') {
        fwrite(STDERR, "Set your API key first:  export MAKEFAV_API_KEY=mf_...\n"
            . "Create one for free at https://makefav.com/account/api-keys\n");
        exit(1);
    }
    return new MakeFav\MakeFavClient($key, getenv('MAKEFAV_BASE_URL') ?: MakeFav\MakeFavClient::BASE_URL);
}
