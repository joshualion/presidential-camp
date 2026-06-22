# Campaign Manager Release Build Commands

Use this from the master core codebase only:

```bash
cd C:\xampp\htdocs\presidential-camp
```

## 1. Check release readiness

```bash
php artisan campaign:release-check
```

The command should show zero failures. A warning that local `.env` exists is acceptable because it is excluded from release ZIPs.

## 2. Dry-run the release builds

Run these before building actual ZIP files:

```bash
php artisan campaign:build-release full --release-version=1.0.0 --dry-run
php artisan campaign:build-release community --release-version=1.0.0 --dry-run
php artisan campaign:build-release all --release-version=1.0.0 --dry-run
```

## 3. Build customer ZIP files

Build the full-data self-hosted package:

```bash
php artisan campaign:build-release full --release-version=1.0.0 --force --channel=stable
```

Build the community/bootstrap package:

```bash
php artisan campaign:build-release community --release-version=1.0.0 --force --channel=stable
```

## 4. Output files

By default, release files are created here:

```text
builds/releases/
```

Expected files:

```text
campaign-manager-self-hosted-full-data-v1.0.0.zip
campaign-manager-community-bootstrap-v1.0.0.zip
campaign-manager-self-hosted-full-data-v1.0.0.json
campaign-manager-community-bootstrap-v1.0.0.json
checksums.txt
```

Upload the ZIP files and matching JSON/checksum metadata to the campaignmanager.ng portal release area.

## 5. Optional public output folder

If you want to generate directly under a public release folder:

```bash
php artisan campaign:build-release full --release-version=1.0.0 --output=public/releases --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.0 --output=public/releases --force --channel=stable
```

Do not use `public/build`; that folder is for Vite frontend assets.

## 6. Version updates

Change `1.0.0` to the version you are releasing:

```bash
php artisan campaign:build-release full --release-version=1.0.1 --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.1 --force --channel=stable
```

## 7. Quick rule

Normal release flow:

```bash
php artisan campaign:release-check
php artisan campaign:build-release all --release-version=1.0.0 --dry-run
php artisan campaign:build-release full --release-version=1.0.0 --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.0 --force --channel=stable
```
