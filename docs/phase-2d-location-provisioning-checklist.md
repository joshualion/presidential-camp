# Phase 2D-1 Licensed Location Provisioning Checklist

Use this checklist after deploying the licensed location provisioning phase.

Normal self-hosted customers should provision location data through the browser installer. The `php artisan campaign:provision-locations` command is reserved for already-installed systems, recovery, and developer/admin maintenance.

## Source Data

- Confirm prepared CSV files exist in `storage/app/imports/inec/prepared/all`.
- Required files: `states.csv`, `senatorial_districts.csv`, `federal_constituencies.csv`, `local_government_areas.csv`, `wards.csv`, `polling_units.csv`.
- Do not fetch location data from the internet during install.

## Fresh Install Scenarios

- Use the browser installer for normal fresh installs; do not require customers to run artisan commands.
- The Run Installation step shows the prepared CSV preflight status before activation/install proceeds.
- Self-hosted Governorship install provisions only the licensed state, its parent region, districts, constituencies, LGAs, wards, and polling units.
- Self-hosted LGA install provisions only the licensed LGA, its parent state/region, related boundaries where available, wards, and polling units.
- Self-hosted Presidential/National install provisions the full prepared dataset.
- Missing CSV files stop installation before `installed.lock` is written.
- The Complete step shows package, deployment mode, licensed scope, imported/updated counts, and warnings.
- Retry succeeds after the missing files are restored.

## Already Installed Systems

- Use the artisan command only for already-installed systems, recovery, or developer/admin maintenance.
- Run `php artisan campaign:provision-locations --dry-run` to preview the licensed scope and expected import counts.
- Run `php artisan campaign:provision-locations --force` to provision missing licensed location records.
- Re-running `php artisan campaign:provision-locations --force` does not create duplicates.
- Provisioning does not reset the license, users, settings, or existing campaign data.

## Managed Readiness

- Managed mode without a local license remains unaffected and skips provisioning.
- Managed mode with a future local license can run `php artisan campaign:provision-locations --force` and provision by that license.

## Diagnostics

- Run `php artisan campaign:scope-status` to confirm deployment mode, package, scope, and latest provisioning run.
- Confirm runtime dashboards only show records allowed by the licensed scope.
