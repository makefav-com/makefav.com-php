# MakeFav PHP – Favicon API client & examples

[![License: MIT](https://img.shields.io/badge/license-MIT-2f9e68.svg)](LICENSE)
[![PHP 7.4+](https://img.shields.io/badge/php-%3E%3D7.4-777bb4.svg)](https://www.php.net/)
[![API docs](https://img.shields.io/badge/docs-makefav.com-2f9e68.svg)](https://makefav.com/developers)

Generate a complete, standards-compliant favicon package from any image with one API call:
`favicon.ico`, PNG favicons, Apple touch icons, Android/PWA icons (incl. maskable),
Windows tiles, `manifest.json`, `browserconfig.xml` and the ready-to-paste HTML for your `<head>`.

Powered by **[MakeFav.com](https://makefav.com)** – free favicon generator, pixel editor and favicon checker.

- Full API docs: https://makefav.com/developers
- OpenAPI spec: https://makefav.com/api/v1/openapi.json

## Requirements

- PHP 7.4 or newer with the `curl` and `json` extensions
- A free MakeFav account and an API key – create one at https://makefav.com/account/api-keys

## Installation

Clone the repository (or use **Code → Download ZIP** on GitHub):

```bash
git clone https://github.com/makefav-com/makefav.com-php.git
cd makefav.com-php
```

No Composer needed – the examples load the two files in `src/` automatically.
In your own project simply:

```php
require 'path/to/makefav.com-php/src/MakeFavException.php';
require 'path/to/makefav.com-php/src/MakeFavClient.php';
```

### With Composer

Add the GitHub repository to your `composer.json` and require the package:

```bash
composer config repositories.makefav vcs https://github.com/makefav-com/makefav.com-php
composer require makefav/makefav-php:dev-main
```

## Quick start

```php
<?php
require 'vendor/autoload.php';

$client = new MakeFav\MakeFavClient(getenv('MAKEFAV_API_KEY'));

// 1) ZIP with all icons, manifest.json and readme-makefav.txt (incl. the <head> code)
$client->generateZip('logo.png', 'favicons.zip', [
    'app_name'    => 'My Website',
    'theme_color' => '#2f9e68',
    'padding'     => 6,
]);

// 2) Or get every file as base64 + the HTML snippet, and write them anywhere
$package = $client->generateJson('logo.svg', ['path' => '/icons/']);
MakeFav\MakeFavClient::saveFiles($package, __DIR__ . '/public/icons');
echo $package['html'];
```

## Run the examples

```bash
export MAKEFAV_API_KEY=mf_your_key_here      # Windows: set MAKEFAV_API_KEY=mf_...

php examples/01-account.php                  # account + remaining quota
php examples/02-generate-zip.php             # examples/logo.png -> examples/favicons.zip
php examples/03-generate-to-folder.php       # write icons to examples/favicons/ and print <head> HTML
php examples/04-from-url.php https://example.com/logo.png
```

Use your own image: `php examples/02-generate-zip.php path/to/logo.svg out.zip`

## Methods

| Method | Description |
|---|---|
| `me()` | Account info and current rate limit |
| `sizes()` | List of all files a package can contain (public) |
| `generateZip($image, $zipPath, $options)` | Generate from a local file and save the ZIP |
| `generateJson($image, $options)` | Generate from a local file, returns `id`, `html` and `files[]` (base64) |
| `generateFromUrl($url, $options, $format)` | Generate from a public image URL (`json` or `zip`) |
| `MakeFavClient::saveFiles($package, $dir)` | Write files from a JSON response into a folder |
| `rateLimit()` | `limit` / `remaining` / `reset` from the last request |

Supported input formats: PNG, JPG, WebP, AVIF, GIF, BMP, SVG and ICO (max. 10 MB).
Uploading an **SVG** also adds a vector `favicon.svg` to the package.

## Options

| Option | Values | Default |
|---|---|---|
| `app_name` | App / website name (max. 60 chars) | `My Website` |
| `short_name` | Short name for the home screen (max. 24 chars) | – |
| `theme_color` | `#rrggbb` | `#ffffff` |
| `tile_color` | Windows tile colour `#rrggbb` | theme colour |
| `background` | `transparent` or `#rrggbb` | `transparent` |
| `apple_background` | Background of Apple icons `#rrggbb` | `#ffffff` |
| `padding` | Empty space around the logo, 0–40 % | `0` |
| `radius` | Rounded corners, 0–50 % | `0` |
| `zoom` | 50–400 % | `100` |
| `offset_x`, `offset_y` | Move the logo, −1 to 1 | `0` |
| `path` | Folder of the icons on your server | `/` |
| `display` | `standalone`, `fullscreen`, `minimal-ui`, `browser` | – |
| `pixelated` | `auto`, `1`, `0` – sharp scaling for pixel art | `auto` |
| `maskable` | `1` / `0` – add a maskable Android icon | `1` |
| `ico_sizes` | Comma list of 16,24,32,48,64,128,256 | `16,32,48` |
| `groups` | Comma list of `favicon,apple,android,ms` | all |
| `format` | `zip` or `json` (set by the client methods) | `zip` |

## Errors & rate limits

Every API error throws `MakeFav\MakeFavException`:

```php
try {
    $client->generateZip('logo.png', 'favicons.zip');
} catch (MakeFav\MakeFavException $e) {
    echo $e->getCode();        // HTTP status, e.g. 429
    echo $e->getErrorCode();   // unauthorized, forbidden, rate_limited, invalid_image, file_too_large, unsupported_type …
    echo $e->getMessage();
}
```

The free plan allows a fixed number of requests per hour; check `$client->rateLimit()`
or `me()` for the remaining quota.

## Security

Never commit your API key. Keep it in an environment variable or a `.env` file that is
listed in `.gitignore`. You can revoke and recreate keys at any time in your MakeFav account.

## Contributing

Bug reports and pull requests are welcome at
[github.com/makefav-com/makefav.com-php](https://github.com/makefav-com/makefav.com-php/issues).

## License

MIT © Webmint s.r.o. – see [LICENSE](LICENSE).
Questions? support@makefav.com
