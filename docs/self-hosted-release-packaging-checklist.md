# Self-Hosted Release Packaging Checklist

Run this checklist from the master source codebase, `presidential-camp`. Do not package installed test copies such as `otu` or senatorial test folders.

## Pre-Build

- [ ] Confirm `.env.example` and `.env.production.example` use self-hosted safe defaults.
- [ ] Confirm `DEPLOYMENT_MODE=self_hosted`.
- [ ] Confirm `BROADCAST_CONNECTION=log`.
- [ ] Confirm `QUEUE_CONNECTION=sync`.
- [ ] Confirm `CACHE_STORE=file`.
- [ ] Confirm `SESSION_DRIVER=file`.
- [ ] Confirm Community Forum remains disabled by default.
- [ ] Confirm no real secrets are present in env examples.

## Build Inputs to Include

- [ ] `app`
- [ ] `bootstrap`
- [ ] `config`
- [ ] `database`
- [ ] `docs`
- [ ] `public`
- [ ] `resources`
- [ ] `routes`
- [ ] `storage/app/imports/campaign-locations/bootstrap`
- [ ] `vendor`
- [ ] `artisan`
- [ ] `composer.json`
- [ ] `composer.lock`
- [ ] `package.json`
- [ ] `vite.config.js`
- [ ] `.env.example`
- [ ] `.env.production.example`
- [ ] `.htaccess.example`
- [ ] `docs/self-hosted-installation-guide.md`
- [ ] `docs/self-hosted-shared-hosting-guide.md`

## v1.0.2 Release Scope

- [ ] Email Notifications controllers, permission settings, audience resolver, job, mail class, models, migration, views, routes, middleware, gate, and sidebar links are included.
- [ ] Branded email composition, preview, and delivery use the shared template and branding resolver.
- [ ] Jurisdiction-based recipient targeting and package-aware audience filters are included.
- [ ] Secure campaign details and recipient delivery reports are included.
- [ ] Shared-hosting public-directory symbolic-link guidance is included.
- [ ] `.htaccess.example` is included, while every real or live-server `.htaccess` reference file is excluded.

## Required Generated Assets

- [ ] Run `composer install --no-dev --optimize-autoloader` before packaging.
- [ ] Confirm `vendor/autoload.php` exists.
- [ ] Run `npm run build` if frontend assets changed.
- [ ] Confirm `public/build/manifest.json` exists.
- [ ] Confirm current compiled JS/CSS assets exist in `public/build/assets`.
- [ ] Confirm shared backend templates load local Ionicons, Toastr, DataTables, AdminLTE, Bootstrap, Chart.js, jQuery, and FontAwesome assets.
- [ ] Review remaining external UI dependencies before release:
  - Leaflet CSS where map UI is used; no local Leaflet package is currently bundled.
  - `intl-tel-input` CSS and phone utility CDN usage on profile forms; local JS utilities exist, but local CSS is not bundled.
  - TinyMCE cloud editor on product/system settings forms.

These remaining external dependencies do not block basic Laravel installation or core navigation, but they should be replaced or explicitly accepted before an offline-first customer release.

## Required Bootstrap Location Data

Include only the minimal bootstrap/sample files. Full licensed location data is provisioned from the Campaign Manager portal after activation.

- [ ] `storage/app/imports/campaign-locations/bootstrap/states.csv`
- [ ] `storage/app/imports/campaign-locations/bootstrap/senatorial_districts.csv`
- [ ] `storage/app/imports/campaign-locations/bootstrap/federal_constituencies.csv`
- [ ] `storage/app/imports/campaign-locations/bootstrap/local_government_areas.csv`
- [ ] `storage/app/imports/campaign-locations/bootstrap/wards.csv`
- [ ] `storage/app/imports/campaign-locations/bootstrap/polling_units.csv`

## Include Empty Writable Folder Placeholders

- [ ] `storage/framework/cache`
- [ ] `storage/framework/sessions`
- [ ] `storage/framework/views`
- [ ] `storage/logs`
- [ ] `bootstrap/cache`
- [ ] `public/uploads`
- [ ] `public/uploads/community/photos`
- [ ] `public/uploads/community/videos`
- [ ] `public/uploads/member_images`
- [ ] `public/uploads/system_images/election_result_sheets`

## Exclude From Release ZIP

- [ ] `.env`
- [ ] `.git`
- [ ] `.agents`
- [ ] `.codex`
- [ ] all real `.htaccess` files
- [ ] root `htaccess` live-server reference file
- [ ] `node_modules`
- [ ] `tests`
- [ ] `storage/app/installed.lock`
- [ ] `storage/app/releases/*`
- [ ] `storage/logs/*`
- [ ] `storage/framework/cache/*`
- [ ] `storage/framework/sessions/*`
- [ ] `storage/framework/views/*`
- [ ] `storage/framework/testing/*`
- [ ] `bootstrap/cache/*.php`
- [ ] `bootstrap/cache/*.tmp`
- [ ] `public/hot`
- [ ] `public/storage`
- [ ] `public/uploads/community/photos/*`
- [ ] `public/uploads/community/videos/*`
- [ ] `public/uploads/member_images/*`
- [ ] `public/uploads/product_images/*`
- [ ] `public/uploads/logo/*`
- [ ] live client files directly under `public/uploads/system_images/*` (retain only bundled political-party assets)
- [ ] `public/uploads/system_images/election_result_sheets/*`
- [ ] `public/uploads/uploads/*`
- [ ] old build ZIPs
- [ ] Vite timestamp temp files
- [ ] local databases and test artifacts
- [ ] database dumps (`*.sql`, `*.sql.gz`, `*.dump`, `*.sqlite`, `*.sqlite3`, `*.bak`)

Keep `.gitkeep` or equivalent placeholders for empty writable folders.

## Validation

Run:

```bash
php artisan campaign:release-check
php artisan campaign:build-release full --release-version=1.0.2 --dry-run
php artisan campaign:build-release community --release-version=1.0.2 --dry-run
php artisan campaign:build-release all --release-version=1.0.2 --dry-run
```

The command must have zero critical failures before a release ZIP is attempted. Warnings about local-only files are acceptable only when the ZIP builder excludes those paths.

## ZIP Build

Build the first local ZIP only after dry-run passes:

```bash
php artisan campaign:build-release full --release-version=1.0.2 --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.2 --force --channel=stable
```

Default output goes to `builds/releases/`:

- `campaign-manager-self-hosted-portal-provisioned-v{version}.zip`
- `campaign-manager-community-bootstrap-v{version}.zip`
- `campaign-manager-self-hosted-portal-provisioned-v{version}.json`
- `campaign-manager-community-bootstrap-v{version}.json`
- `checksums.txt`

The build report includes version, channel, build date, package type, required CSV presence, built asset presence, excluded file summary, warnings, ZIP size, and SHA256 checksum.

Use `--release-version=1.0.2` to override the configured app version, `--output=/path/to/releases` to change the output directory, and `--no-zip` for manifest/report-only validation. Artisan reserves the global `--version` option for framework version output.

This command does not implement one-click updater, portal release management, or customer download delivery.
