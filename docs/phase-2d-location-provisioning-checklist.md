# Phase 2D-1 Licensed Location Provisioning Checklist

Use this checklist after deploying the licensed location provisioning phase.

Normal self-hosted customers should provision location data through the browser installer. The `php artisan campaign:locations:provision` command is reserved for already-installed systems, recovery, and developer/admin maintenance.

## Portal Source Data

- Confirm the Campaign Manager portal exposes licensed JSON batches through the location provisioning API.
- Confirm the release ZIP includes only bootstrap/sample CSV files under `storage/app/imports/campaign-locations/bootstrap`.
- Do not bundle full prepared location CSV data in customer ZIPs.

## Fresh Install Scenarios

- Use the browser installer for normal fresh installs; do not require customers to run artisan commands.
- The Provision Location Data step imports licensed JSON batches after activation.
- Self-hosted Governorship install provisions only the licensed state, its parent region, districts, constituencies, LGAs, wards, and polling units.
- Self-hosted LGA install provisions only the licensed LGA, its parent state/region, related boundaries where available, wards, and polling units.
- Self-hosted Presidential/National install provisions the full prepared dataset.
- Missing CSV files stop installation before `installed.lock` is written.
- The Complete step shows package, deployment mode, licensed scope, imported/updated counts, and warnings.
- Retry succeeds after the missing files are restored.

## Already Installed Systems

- Use the artisan command only for already-installed systems, recovery, or developer/admin maintenance.
- Run `php artisan campaign:locations:provision` to provision missing licensed location records from the portal.
- Re-running `php artisan campaign:locations:provision` resumes from the saved portal cursor and does not create duplicates.
- Provisioning does not reset the license, users, settings, or existing campaign data.

## Managed Readiness

- Managed mode without a local license remains unaffected and skips provisioning.
- Managed mode with a future local license can run `php artisan campaign:locations:provision` and provision by that license.

## Diagnostics

- Run `php artisan campaign:scope-status` to confirm deployment mode, package, scope, and latest provisioning run.
- Confirm runtime dashboards only show records allowed by the licensed scope.
