# Release readiness and integration checkpoint

No official release is approved by the checks recorded here. This branch is reviewable implementation work, not a deployed production system.

## Completed implementation

- Upgrade to Laravel 12/PHP 8.2 minimum and refreshed locked dependencies; Composer audit reports no known advisories in the tested lock. Frontend builds; npm production audit reports none. Seven build-tool advisories remain in the full npm audit and require assessed remediation.
- Fix Linux route-case mismatch, unresolved chart aliases, safe post-author fields, scoped comments and private broadcast payloads, image upload validation, S3 driver configuration, sensitive legacy activation logging, installer command-exit handling, repeat administrator protection and atomic private installer state.
- Require a local hosting ownership code before installer configuration. This is independent of paid services and remote activation.
- Fail closed for unsigned release integrity, require owner-managed build signing keys, package the public key and re-sign final external metadata including the ZIP checksum and size.
- Discover stable releases from the official public repository, verify metadata against the installed trust key, validate size/hash and archive paths, verify every staged application file and prevent concurrent preparations. Super Admin can check and stage a release. Preparation does not overwrite files or migrate databases.
- Optional encrypted deployment registration, separate marketing consent, confirmed-email promotional messages, token-authenticated Portal polling, local immediate opt-out/revocation and retry of remote deletion. No arbitrary remote commands or executable messages.
- CI definitions and a private candidate preparation workflow. They require execution on the actual complete Git checkout before their results can be accepted. Candidate workflow never publishes.

## Release gates

1. Review changes and confirm complete repository CI on PHP 8.2 and 8.3. Run production-like MySQL migrations and full Portal regressions. Existing cloud PHP 8.3/SQLite core evidence does not establish either of those.
2. Improve installer preflight and durable progress journal: reject unrelated populated databases before changing `.env`, serialize concurrent installation and prove retry after each migration/seed/provision failure. Current late administrator guard and atomic state do not establish complete interrupted-install recovery.
3. Implement and test upgrade apply, pre-update encrypted/private backups, database export verification, maintenance/queue control, disk checks, replacement journaling, full code/database rollback and health/version verification. Keep apply disabled until these pass. Preserve `.env`, uploads, storage, server-specific `.htaccess` and permissions.
4. Define supported upgrade paths from v1.0.9. Old releases without updater code require one manual upgrade before future notifications/staging can work. Do not imply retroactive discovery of historical installations.
5. Owner approves a stable public signing key and commits `config/release-public.pem`. Store only the matching private key in the protected `release-preparation` environment secret `CAMPAIGN_RELEASE_PRIVATE_KEY`. Never include it in ZIPs, logs, PRs or customer machines. Define key rotation and emergency revocation.
6. Verify the repeatable production-only build on a complete clean checkout. The private candidate workflow installs `--no-dev`, includes compiled assets, creates signed `release-manifest.json` and ZIP SHA-256 sidecar. Audit ZIP exclusions and fresh install on the actual candidate. Review action versions/pinning and CI environment access.
7. Owner configures protected environment approvals, Actions permissions and a scoped GitHub App/token for eventual cross-repository publication. The private repository's default `GITHUB_TOKEN` cannot write the public repository. No owner personal credentials are needed. Current connector lacks a callable release-publish operation.
8. Replace/extend legacy `GitHubReleasePublisher`: it currently uploads only the ZIP and marks latest immediately. Upload verified metadata/checksums/notes to a draft candidate, verify assets, then explicitly publish after staging and customer acceptance. Do not use the legacy command for the next stable release.
9. Deploy the reviewed Portal migrations/API first to staging, verify SMTP confirmation, consent/retention, assisted-update payment currency/terms, authorization and concurrent-credit behavior. Keep optional registration tolerant of Portal outages. Add menu integration and confirmation resend. Do not infer a domain's ownership from its email.
10. Staging: fresh manual and paid automatic geography installations across all supported scopes, interrupted installs, existing-install upgrades, migration failure, disk-full/permission failure, altered packages, rollback and backup restore. Test shared-hosting PHP time limits/no shell/cron and VPS queue/worker behavior independently.
11. Owner downloads and personally verifies the candidate before public publication. Recommend a minor version such as 1.1.0 if this feature scope remains, subject to actual merged changes and version-history review. No version was automatically selected or tag created.

## Compatibility and limits

Public Community ZIPs remain free and do not require registration or remote software activation. Paid credits fund assistance, not permission to run or manually download the software. Portal messages are plain escaped content, polled only by consenting deployments, and cannot remotely execute code.

Installation start/failure telemetry is not yet collected during setup; it requires an explicit consent stage before any such reporting. Direct GitHub download counters contain no customer email/domain. Domains reported by registration are unverified. Marketing email campaigns, retention exports/deletion automation and production payment/SMTP integration have not been tested or enabled.

Legacy election mutation routes return an explicit 410 and direct users to the existing review/dispute workflow because their handlers were absent. This needs UI regression verification so no intended current workflow relies on those links.

## Validation matrix

| Check | Cloud evidence | Remaining validation |
|---|---|---|
| Core existing/new feature tests | PHP 8.3, isolated HTTP, SQLite; JUnit accompanies checkpoint | Actual complete-checkout CI, PHP 8.2, MySQL |
| Portal new registration/credits | 8 passed, 34 assertions; MariaDB 10.11; fake mail | Full Portal suite, MySQL 8, payment integration/concurrency |
| Frontend | Production build passed; production npm audit 0 | Browser regressions; remaining build-only advisories |
| Composer dependencies | Both refreshed Laravel 12 lockfiles; audit 0 at refresh | CI fresh no-dev install and packaged inventory |
| Release signing/staging | Fixture signatures, final metadata, tampered/archive tests | Owner trusted key, real candidate asset publishing/verification |
| Installer recovery | Exit failure, safe admin retry and ownership tests | Full failure-injection/state recovery/preflight |
| Upgrade/rollback | Apply intentionally unavailable | Backup restore, migration rollback and staging |
| Shared hosting/VPS | Source compatibility reviewed | Real hosting/webserver/cron/queue trials |

Tests block stray HTTP; no production campaign data or Portal credentials were used. A previous test action was rejected by automatic approval review for possible external Portal transmission; that run is not accepted as test evidence and isolated tests replaced it.
