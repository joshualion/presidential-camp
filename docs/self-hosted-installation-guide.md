# Self-Hosted Installation Guide

This guide is for customers installing the Campaign Manager self-hosted ZIP on their own server.

## Production Web Root

Recommended setup:

- Upload the Campaign Manager core outside the public web root.
- Point the domain document root directly to the app's `public` directory.
- Use a production URL like `https://clientdomain.com`.
- Do not use `https://clientdomain.com/public` in production.

Never expose these paths through the web server:

- `.env`
- `app`
- `bootstrap`
- `config`
- `database`
- `resources`
- `routes`
- `storage`
- `vendor`
- `artisan`

## Shared Hosting Public Symbolic Link

When the hosting panel cannot change the domain document root directly, keep the application core outside `public_html` and point `public_html` to the Laravel `public` directory with a symbolic link.

Example layout:

```text
/home/account/campaign-manager-core
/home/account/public_html -> /home/account/campaign-manager-core/public
```

If SSH access is available and `public_html` does not already exist, the hosting provider may allow:

```bash
ln -s /home/account/campaign-manager-core/public /home/account/public_html
```

Do not replace an existing `public_html` directory without first preserving its current files. Some shared hosts prohibit symbolic links; use the bridge approach in `docs/self-hosted-shared-hosting-guide.md` in that case.

The release intentionally excludes real `.htaccess` files and server-specific rewrite references. For Apache hosting, copy the packaged safe template into the public directory only when required:

```bash
cp .htaccess.example public/.htaccess
```

The template contains no customer domain, account path, or live-server values.

## Upload the Package

Upload the full self-hosted ZIP supplied by Campaign Manager support. The package must include:

- Laravel application source needed to run the app.
- `vendor` dependencies.
- `public/build/manifest.json` and compiled frontend assets.
- Installer routes, controllers, views, migrations, seeders, and config files.
- `.env.example` and `.env.production.example`.
- `.htaccess.example` as the safe optional Apache rewrite template.
- Shared-hosting symbolic-link and bridge guidance.
- Campaign location data package.

Customers should not need Node.js, npm, Vite, or Composer for a normal install.

## Environment File

Copy `.env.example` or `.env.production.example` to `.env`.

Set at minimum:

- `APP_NAME`
- `APP_URL=https://clientdomain.com`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DEPLOYMENT_MODE=self_hosted`
- `INSTALLER_ENABLED=true`
- database credentials

Safe first-run defaults:

- `BROADCAST_CONNECTION=log`
- `QUEUE_CONNECTION=sync`
- `CACHE_STORE=file`
- `SESSION_DRIVER=file`

The installer can also write safe environment values during setup.

Leave `APP_KEY` empty in `.env.example`. On first browser install, Campaign Manager generates a unique key automatically when `.env` is writable. Terminal users may alternatively run `php artisan key:generate`.

Do not change `APP_KEY` after installation. Changing it can invalidate encrypted sessions and stored encrypted values.

## Writable Paths

Make these paths writable by the PHP user:

- `storage/framework/cache`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`
- `public/uploads`
- `public/uploads/community/photos`
- `public/uploads/community/videos`
- `public/uploads/member_images`
- `public/uploads/system_images/election_result_sheets`

## Browser Installer

Open:

```text
https://clientdomain.com/install
```

The installer will:

1. Check PHP extensions, writable folders, bundled vendor dependencies, compiled assets, and location CSV files.
2. Save safe self-hosted environment settings.
3. Test database credentials.
4. Preview and activate the license.
5. Prepare licensed campaign locations.
6. Create the first superadmin.
7. Write `storage/app/installed.lock` after installation succeeds.

## Location Setup

The release package includes the Campaign Manager location data package. The browser installer prepares only the geography allowed by the license.

National installations can take a few minutes. Keep the installer page open while progress continues. Progress is saved, so a browser refresh can continue the setup safely.

## Community Forum

Community Forum is optional and disabled by default. When licensed and enabled, it runs in polling mode by default and does not require Reverb, Redis, supervisor, or a websocket port.

Reverb is an optional advanced realtime mode. Leave `WEB_SOCKET_AVAILABILITY=false` unless the server has been configured for Reverb.

## Troubleshooting

If the installer reports missing `vendor/autoload.php`, the uploaded ZIP is incomplete. Request a full self-hosted release package.

If the installer reports missing `public/build/manifest.json`, the package was not built with frontend assets. Request a rebuilt ZIP.

If the installer reports that the campaign location data package is incomplete, upload the complete self-hosted ZIP again or contact support.

If pages fail after install, confirm the domain document root points to `public`, not the Laravel project root.
