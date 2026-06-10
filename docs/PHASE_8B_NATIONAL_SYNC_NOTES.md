# Phase 8B National Package Synchronization Notes

Date: 2026-06-04

Source package: `C:\xampp\htdocs\State`
Target package: `C:\xampp\htdocs\presidential-camp`

## Pre-Sync Status

The National Package worktree was already dirty before synchronization. Many tracked files were modified and many files were untracked. This sync must avoid blind overwrites and preserve National-specific behavior where it already differs.

## Target Package Type

The National Package must run as the Presidential Campaign Package.

- Package value: `presidential`
- Commercial label: `Presidential Campaign`
- Final dispute authority: `nationaladmin`
- Regional remains an internal reporting/admin scope only.

## Initial Safe Copy Candidates

The following package-neutral source files were present in State and missing in National before sync:

- `app/Services/LocationScopeService.php`
- `app/Services/LocationDashboardMetricsService.php`
- `app/Services/ElectionOperationsService.php`
- `app/Services/ElectionAnalyticsService.php`
- `app/Services/ElectionReportService.php`
- `app/Services/PackageGovernanceService.php`
- `app/Services/PackageVisibilityService.php`
- `app/Models/Concerns/HasLocationScope.php`
- `app/Exports/ElectionPollingUnitResultsExport.php`
- `app/Exports/ElectionSummaryExport.php`
- `app/Models/SenatorialDistrict.php`
- `app/Models/FederalConstituency.php`
- `routes/senatorialAdmin.php`
- `routes/federalAdmin.php`

## Migration Candidates

The State package contains 2026 electoral boundary, reporting, lifecycle, governance, and package normalization migrations that were not present in National at initial inspection.

## Merge Policy

- Do not overwrite existing National controllers, models, menus, route files, or seeders blindly.
- Add missing package-neutral files directly when absent.
- Merge existing files only after inspecting National-specific differences.
- Do not run destructive database commands.
- Do not copy State demo users into National production seeders.

## Sync Execution Summary

Completed on 2026-06-04.

### Copied Additively

- Package-neutral services
- `HasLocationScope` model concern
- Election export classes
- Senatorial/Federal boundary models
- Senatorial/Federal route files
- Shared dashboard/report/election views
- Senatorial/Federal admin controllers and views
- Controller concern `ManagesAdminProfile`
- Electoral boundary/report/governance/package migrations
- Separate `ElectoralBoundaryDemoUsersSeeder`

### Merged or Adapted

- `routes/web.php` was patched to include Senatorial/Federal route files and global governance/report/export routes.
- `SystemSettingsSeeder` was adapted to default National to `presidential`.
- `.env` was given `CAMPAIGN_PACKAGE_TYPE=presidential` when missing.
- `PackageGovernanceService` fallback was adapted for the National package so empty/unknown package values resolve to Presidential context.
- The package normalization migration was adapted so the commercial enum defaults to `presidential` in National.
- Existing National `UsersTableSeeder` was preserved; State demo users were not copied into the production user seeder.

### Validation Results

- PHP syntax checks passed for synced services, controllers, models, and seeders.
- `php artisan route:list` passed after adding the missing controller concern.
- Report/export routes registered:
  - `election.report.print`
  - `election.report.scope-snapshot`
  - `election.report.polling-units.csv`
  - `election.report.polling-units.excel`
  - `election.report.summary.csv`
  - `election.report.summary.excel`
- Governance/result sheet routes registered:
  - `election.result.verify`
  - `election.result.dispute`
  - `election.result.dispute.clear`
  - `election.result.sheet`
- Senatorial and Federal election route groups registered.
- `php artisan migrate --pretend` completed.
- `php artisan migrate` completed successfully.
- `php artisan view:cache` completed.
- `php artisan view:clear` completed before final view cache.

### Package Verification

- Stored package value: `presidential`
- Resolved package: `presidential`
- Package label: `Presidential Campaign`
- Final dispute authority: `nationaladmin`

### Known Log Note

The National log contains a transient `ManagesAdminProfile` missing-trait error from route validation before the concern was copied. The concern was then added and subsequent route/view checks passed.
