<?php

declare(strict_types=1);

// Show your account and remaining API quota.
// Usage: php examples/01-account.php

require __DIR__ . '/bootstrap.php';

try {
    $me = makefav_client()->me();
} catch (MakeFav\MakeFavException $e) {
    fwrite(STDERR, "API error [{$e->getErrorCode()}]: {$e->getMessage()}\n");
    exit(1);
}
printf("Logged in as %s <%s>\n", $me['name'] ?: '(no name)', $me['email']);
printf("Rate limit: %d of %d requests left, resets at %s\n",
    $me['rate_limit']['remaining'], $me['rate_limit']['limit'], date('Y-m-d H:i', $me['rate_limit']['reset']));
