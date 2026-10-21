# 1.1.9

- Add accessible Show/Hide Password controls across authentication, account and administrator forms. Passwords remain hidden by default.
- Stop returning saved SMTP passwords in forms and model serialization. Blank password replacements retain the existing credential.
- Preserve signed update authorization, payment checks, explicit confirmation and backup/recovery protections. No new database migrations.

# 1.1.7

- Fix empty election results pages for every administrator level, including optional country records, explicit empty results and correctly scoped headings.
- Fix Volunteer Group Settings and Age Grade Settings returning HTTP 500 on case-sensitive hosting, including their add and edit pages.
- Correct settings redirects, accept optional descriptions, and prevent deleting an age grade that still has associated users.
- Improve automatic-update checkout validation for explicitly trusted Portal origins and installation-bound return links. The matching Customer Portal correction must be deployed first.
- Automatic updates require confirmed coverage and customer consent. Manual release downloads remain free. Pending bank transfers provide coverage only after verification.

# 1.1.5

Coverage-first automatic updating, engine-aware consistent backups, native-tool fallback, bounded export retries, automatic rollback and independently supervised recovery. Existing 1.1.4 assets remain unchanged. See docs/releases/v1.1.5.md.

# 1.1.4

Administrator navigation and theme fixes, conditional SMS recipient input, printable offline registration PDFs, NDC with an official INEC logo, and reviewed automatic upgrades from 1.1.3. See docs/releases/v1.1.4.md.

# v1.1.2

- Restore legacy update discovery with compact signed metadata.
- Include Portal-managed update coverage periods and signed bootstrap support for 1.1.1.

## Automatic updater follow-up for 1.1.1 (unreleased)

- Optional automatic updates with owner-configured service prices by campaign scope, installation and target release, with free manual downloads unchanged.
- Signed explicit compatibility/migration policy, private verified database/file backups, detached worker, protected progress and standalone recovery.
- Signed legacy bootstrap for 1.0.8/1.0.9/1.1.0 and reviewed pending migration execution without reset or seeding.
- See docs/automatic-updates.md. CI, merge, publication and deployment remain pending.

# Changelog

## 1.1.1

- Simplified Official Updates with automatic discovery and clear verified-staging status.
- Moved installation registration controls to their own settings page.
- Branded server-access confirmation and improved installer mobile layout.
- Automatic installation is deferred; staged updates continue to require manual application.


## v1.1.0 — candidate, publication pending

- Require free installation registration after explicit licence/privacy acceptance for new setups.
- Keep downloader and installation contacts separate; optional promotions remain unchecked and require verified contact.
- Persist encrypted registration identity before network requests and validate the receipt before installer completion.
- Retry status reporting without remotely disabling established installations.
- Add confirmed-contact domain migration and resend controls.
- Preserve the preceding route, upload, privacy, installer ownership and verified update-staging fixes.
- Publish only through the gated workflow using the established trusted signing identity and live registration API.
- Automatic updater application/rollback remains unavailable; use verified manual maintenance.


## [1.0.9] - 2026-09-06

### Fixed
- Installer geography selection now preserves authoritative Portal geography codes separately from Portal IDs.
- Base campaign geography bootstrapping creates or reuses coded State, Senatorial District, Federal Constituency, and LGA records.
- QuickStart provisioning reuses matching null-code local geography records instead of creating duplicate coded parents.
- Local campaign settings and LocalLicense scope IDs are synchronized to canonical local geography IDs after provisioning.
- Added an idempotent repair command for existing duplicate geography from prior null-code installer records.

## [1.0.8] - 2026-09-05

### Fixed
- QuickStart installation no longer incorrectly requires a legacy activation reference when the provisioning entitlement has activation_required=false.
- QuickStart now proceeds directly to geography provisioning after entitlement validation.
- New Community/QuickStart users no longer receive legacy software-activation errors in this path.


### Pending 1.1.2 — customer-owned SMS

Community administrators can use their own Termii or BulkSMSNigeria account when enabled by Super Admin. Includes encrypted account revisions, geographic audit isolation and explicit wider oversight, recipient confirmation, opt-outs, scheduling, queued chunks and authenticated delivery reports. Hosted SMS remains hidden by default. See docs/customer-owned-sms.md.

