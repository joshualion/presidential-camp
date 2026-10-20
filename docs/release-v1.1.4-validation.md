# v1.1.4 release validation

Implementation and official INEC NDC assets are committed.

- PHP 8.2: 479 tests passed, 2,120 assertions.
- PHP 8.3: 479 tests passed, 2,120 assertions.
- Composer audit, production npm audit and frontend build passed.
- Matrix run: https://github.com/joshualion/presidential-camp/actions/runs/39472855529
- Verified source revision: `caa7f6ebeff3967a1464fe9af27f74bcd21e9ef8`.
- The release workflow rebuilds the signed package and applies it to an isolated official 1.1.3 installation before publication. It checks actual old-engine policy admission, signatures, backups, migration execution, health, version, credentials and preservation of existing users, uploads, environment and custom party data. Only entitlement HTTP calls are stubbed in that isolated exercise; no customer data or payments are used.
- The established `community-release` environment supplies the existing trusted signing identity for candidate and publication workflows.
- Existing 1.1.3 release assets are never replaced.

The earlier candidate run stopped before packaging because the separate `release-preparation` environment had no signing key. It did not publish or create unsigned assets.

## Reviewed migration

`2026_10_20_000001_add_nigeria_democratic_congress`, SHA-256
`06036a50125f8670adb74fed01913a89516f62769bc8e222d7cab26a04bac594`.

This additive migration installs NDC if absent, fills an empty logo, preserves custom party details and logos, and copies the bundled default asset without overwriting an existing file.
