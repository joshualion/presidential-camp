# Pre-Release Cleanup Checklist

Use this checklist before self-hosted release packaging.

## Hidden or Gated Incomplete Modules

- [ ] SMS/Bulk SMS menu links are hidden for every backend role.
- [ ] Direct SMS/Bulk SMS pages return a controlled forbidden response while the module is unavailable.
- [ ] Finance, donations, finance requests, wallet/balance, and fund request links are hidden.
- [ ] Campaign store, products, branded materials, marketplace, gala/event tickets, and sponsor/ad links are hidden from normal navigation.
- [ ] Reserved future module keys remain disabled by default: `sms`, `bulk_sms`, `finance`, `donations`, `finance_requests`, `campaign_store`, `sponsored_placements`.

## Navigation and Sidebar Safety

- [ ] Backend menus have no broken sidebar links.
- [ ] Backend menus use app-safe URLs and do not assume a root `/public` deployment path.
- [ ] Topbar/navbar links point only to working modules.
- [ ] Community Forum is shown only when enabled and available.
- [ ] Community sidebar renders only safe widgets: Election/Post Watch, My Campaign Scope, Community Guidelines, and profile completion where applicable.
- [ ] Community sidebar does not show donate, sponsor, store, finance, wallet, marketplace, or greyed-out future widgets.
- [ ] Post Watch does not crash when election data is missing.

## Messaging

- [ ] Internal Communication remains accessible when Community Forum is disabled.
- [ ] SMS is not shown inside messaging menus or settings.
- [ ] LiveChat links are absent from navigation or redirected through the stabilized internal messaging path.
- [ ] Messaging works in polling mode and does not require Reverb.
- [ ] Lower-level users can reply to conversations initiated by higher-level users.

## Installer, License, and Scope

- [ ] Installer route still loads.
- [ ] Installer requirements report missing `vendor/autoload.php`, `public/build/manifest.json`, and prepared INEC CSV files with customer-friendly messages.
- [ ] License screen still loads.
- [ ] Activation and first superadmin creation still work.
- [ ] Package-aware dashboards still load.
- [ ] Package-aware role and location forms still enforce license scope.
- [ ] Community disabled-by-default behavior still works.
- [ ] Community enabled behavior still works when licensed and toggled on.

## Release Packaging Readiness

- [ ] Package only from the master `presidential-camp` source tree.
- [ ] Do not package installed test copies such as `otu` or senatorial smoke-test folders.
- [ ] Include `vendor` and `public/build`.
- [ ] Include only bootstrap/sample location CSV files under `storage/app/imports/campaign-locations/bootstrap`; exclude full prepared location data from customer ZIPs.
- [ ] Exclude `.env`, `.git`, `node_modules`, `storage/app/installed.lock`, logs, cache files, sessions, compiled views, local uploads, local license data, and old ZIPs.
- [ ] Preserve writable folder placeholders.
- [ ] Confirm production document root points to `/public` or uses the shared-hosting bridge.
- [ ] Confirm production `APP_URL` does not contain `/public`.

## Verification

- [ ] `php artisan route:list --path=sms`
- [ ] `php artisan route:list --path=bulk`
- [ ] `php artisan route:list --path=finance`
- [ ] `php artisan route:list --path=donate`
- [ ] `php artisan route:list --path=store`
- [ ] `php artisan route:list --path=sponsor`
- [ ] `php artisan route:list --path=messages`
- [ ] `php artisan route:list --path=community`
- [ ] `php artisan route:list --path=install`
- [ ] `php artisan route:list --path=admin/license`
- [ ] `php artisan campaign:scope-status`
- [ ] `php artisan campaign:release-check`
- [ ] `php artisan view:cache`
- [ ] `php -l` passes on modified PHP/config/test files.
- [ ] Focused feature tests pass.
- [ ] Vite build is rerun only if Vue/JS assets changed.
