<?php

namespace App\Services\Updates;

use Illuminate\Http\Request;

/** Adapt a secondary public entry point to Laravel's ordinary front-controller mount. */
class BootstrapHttpRequest
{
    public static function capture(string $frontController): Request
    {
        if (! is_file($frontController)) {
            throw new \RuntimeException('The existing application front controller is unavailable.');
        }
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (! is_string($uri) || ! preg_match('~^(.*)/campaign-update-bootstrap\\.php$~D', $uri, $matches)) {
            throw new \RuntimeException('Invalid updater entry address.');
        }
        // Symfony derives base URLs from these identifiers. The updater is a route,
        // not a front-controller directory; preserve the real URI, host, body and cookies.
        $_SERVER['SCRIPT_NAME'] = $matches[1].'/index.php';
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
        $_SERVER['SCRIPT_FILENAME'] = realpath($frontController);
        unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);

        return Request::capture();
    }
}
