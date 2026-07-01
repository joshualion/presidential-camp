# Shared Hosting Guide

Use this option only when the hosting panel cannot point the domain document root directly to the Laravel `public` directory.

## Recommended Layout

Example account layout:

```text
/home/account/campaign-manager-core
/home/account/public_html
```

Place the Laravel core in `campaign-manager-core`. Only the bridge files below should live in `public_html`.

## Preferred Symbolic Link

If the host permits symbolic links, point `public_html` directly to the core application's `public` directory:

```text
/home/account/public_html -> /home/account/campaign-manager-core/public
```

This preserves Laravel's normal public-directory layout and is preferred over copying files. If symbolic links are unavailable, use the bridge below.

## public_html/index.php Bridge

Create `public_html/index.php`:

```php
<?php

$corePath = __DIR__.'/../campaign-manager-core';

define('LARAVEL_START', microtime(true));

require $corePath.'/vendor/autoload.php';

$app = require_once $corePath.'/bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
```

Adjust `$corePath` to the real private core path.

## public_html/.htaccess

The release ZIP never includes a real `.htaccess`. Use the packaged `.htaccess.example` for a normal public-directory deployment, or create the following generic bridge rule when using `public_html/index.php`:

Create `public_html/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} -d [OR]
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]

    RewriteRule ^ index.php [L]
</IfModule>
```

## Public Assets

Best option: configure the domain document root to `campaign-manager-core/public`.

Bridge option: copy the contents of `campaign-manager-core/public` into `public_html` while keeping `.env`, `app`, `storage`, `vendor`, `database`, and `artisan` outside `public_html`. Keep `public_html/index.php` as the bridge.

Do not upload the whole Laravel root into `public_html`.

## Production URL

Use:

```text
APP_URL=https://clientdomain.com
```

Do not use:

```text
APP_URL=https://clientdomain.com/public
```

Temporary `/public` URLs are acceptable only for local smoke tests.
