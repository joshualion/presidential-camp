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
php artisan campaign:build-release full --release-version=1.0.2 --dry-run
php artisan campaign:build-release community --release-version=1.0.2 --dry-run
php artisan campaign:build-release all --release-version=1.0.2 --dry-run
```

## 3. Build customer ZIP files

Build the full self-hosted package:

```bash
php artisan campaign:build-release full --release-version=1.0.2 --force --channel=stable
```

Build the community/bootstrap package:

```bash
php artisan campaign:build-release community --release-version=1.0.2 --force --channel=stable
```

## 4. Output files

By default, release files are created here:

```text
builds/releases/
```

Expected files:

```text
campaign-manager-self-hosted-portal-provisioned-v1.0.2.zip
campaign-manager-community-bootstrap-v1.0.2.zip
campaign-manager-self-hosted-portal-provisioned-v1.0.2.json
campaign-manager-community-bootstrap-v1.0.2.json
checksums.txt
```

Upload the ZIP files and matching JSON/checksum metadata to the campaignmanager.ng portal release area.

## 5. Optional public output folder

If you want to generate directly under a public release folder:

```bash
php artisan campaign:build-release full --release-version=1.0.2 --output=public/releases --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.2 --output=public/releases --force --channel=stable
```

Do not use `public/build`; that folder is for Vite frontend assets.

## 6. Version updates

The current release target is `1.0.2`:

```bash
php artisan campaign:build-release full --release-version=1.0.2 --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.2 --force --channel=stable
```

## 7. Quick rule

Normal release flow:

```bash
php artisan campaign:release-check
php artisan campaign:build-release all --release-version=1.0.2 --dry-run
php artisan campaign:build-release full --release-version=1.0.2 --force --channel=stable
php artisan campaign:build-release community --release-version=1.0.2 --force --channel=stable
```
