# Changelog

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
