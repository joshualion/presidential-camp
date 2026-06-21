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
- [ ] License screen still loads.
- [ ] Activation and first superadmin creation still work.
- [ ] Package-aware dashboards still load.
- [ ] Package-aware role and location forms still enforce license scope.
- [ ] Community disabled-by-default behavior still works.
- [ ] Community enabled behavior still works when licensed and toggled on.

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
- [ ] `php artisan view:cache`
- [ ] `php -l` passes on modified PHP/config/test files.
- [ ] Focused feature tests pass.
- [ ] Vite build is rerun only if Vue/JS assets changed.
