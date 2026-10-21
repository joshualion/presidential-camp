# Automatic-update reliability correction — 21 October 2026

## Source and release boundaries

Core: `joshualion/presidential-camp`, based on `b079df124b1ab3228367f878845b3867adcee1a3`.
Portal: `joshualion/campaigmanager.ng`, based on `6015f1af2fbeb4cefccebe48e919dbaee7490ba4`.
Work is delivered on `main` as requested. Source commits use `[skip ci]`: the owner must authorize CI separately. No production deployment, repository visibility change, payment transaction or distribution-repository write is part of this correction.

## Findings and implementation

1. The Portal authorization API generated checkout addresses with request-dependent `route()` origins. Production returned `www.campaignmanager.ng`, while Core v1.1.5 required exactly `campaignmanager.ng`.
2. Portal checkout and automatic-update Paystack callback addresses now use `AUTOMATIC_UPDATE_PORTAL_ORIGIN`, defaulting to `https://campaignmanager.ng`. It must be an HTTPS origin without credentials, paths, query strings or a nonstandard port. This setting is deliberately independent of `APP_URL` and incoming Host headers.
3. Core validates complete, explicitly approved HTTPS origins. The official canonical and `www` origins are explicit defaults; custom aliases require `CAMPAIGN_PORTAL_CHECKOUT_ORIGINS`. It also checks the exact checkout path, installation UUID, target version, checksum, scope and quote-token shape. Duplicate query keys, credentials, downgrade, unexpected ports, fragments and unrelated destinations fail closed. Core Portal API requests refuse redirects rather than forwarding installation credentials down a redirect chain.
4. Checkout previously only instructed customers to navigate back manually. Return links now derive from the registered installation. New Core sends its configured Official Updates URL, supporting a subdirectory installation. Portal validates its registered host, HTTPS origin and fixed update-route suffix and binds it to installation/domain/version/checksum with an HMAC. Altered return fields are rejected. Legacy Core supplies no return fields and receives the registered HTTPS domain plus `/superadmin/updates/automatic`. Return information is stored on the existing order; no schema migration is added.
5. An authenticated order owner with a fully verified online payment returns to the bound installation. Other callback users land on the protected Portal payment page. Pending bank payments remain pending and show a return link without claiming coverage. The payment page no longer says paid automatic updates need separate administrative approval. Bank receipts still require normal administrator confirmation.
6. Navigation never supplies entitlement or starts an update. Core's existing protected page refreshes signed release discovery and calls its authenticated entitlement API; the owner must explicitly confirm backup and update. Scope, release, checksum and job binding are rechecked at download/start and worker authorization. Replayed return links grant nothing. Existing order/quote checks still govern purchase consent.
7. Payment verification now requires exact minor-unit amount and an explicit matching currency and reference. Unresolved automatic-update charges retain the original reference across retries instead of rotating after ten minutes. Pending or ambiguous verification does not turn the charge into a failed/rechargeable attempt. Confirmed failed/abandoned payments retain the existing retry behavior. Automatic-update Paystack initialization/cached navigation permits only HTTPS `checkout.paystack.com`; gateway API calls refuse redirects and have finite timeouts.
8. Official Updates labels Automatic Update and free Manual Update explicitly. Manual navigation uses the specific official release tag when a valid version is available. The candidate v1.1.7 signed policy can include v1.1.5 and v1.1.6. No release identity or published package is changed by this source correction.

## Engine review

The existing engine already separates discovery from download, gates download on authenticated entitlement, validates outer/embedded signed manifests and package hashes, allows only explicitly supported source versions and exact approved migration hashes, journals the update under an installation-wide lock, exports the database, backs up affected files, protects configuration/uploads, starts a detached supervisor/worker, checks the new runtime/version, refreshes caches and reports completion.

Failure tests exercise backup failure before changes, migration failure with database/file restoration, interrupted workers, surviving command leases, tampered backups, unexpected tables, maintenance ownership and verified-update recovery. A new successful worker fixture exercises a real disposable SQLite backup, schema change, preserved customer/configuration/upload, new version reporting and receipt reporting after maintenance release. This fixture uses a small command harness; it is not a rehearsal with a full published package on cPanel.

Automatic restore is permitted only while the engine owns maintenance and its locks and backups validate. After verified installation is committed, recovery never restores the old database, since the new application may have accepted writes. Unsupported hosting/process/queue/storage configurations fail readiness; these limits have not been relaxed.

## Portal-first compatibility decision

The published v1.1.6 release manifest was fetched read-only. Its signature verifies with the bundled official release public key. Its SHA-256 is `959185e20c6a5bb6fd161fad6633097e4cdf454d0bb4dd68137851654f223526`, matching GitHub's asset digest. The signed policy includes source `1.1.5`; its package hash is `25d86475f63dd8d1b004a9deaeea02b21fed224b1990d0274e9f5f7605952e91`.

Deploying the corrected Portal with canonical origin `https://campaignmanager.ng` addresses the confirmed v1.1.5 checkout-host rejection without modifying any customer `.env` or v1.1.6 asset. It also supplies the legacy installation's safe return link. This is sufficient for that checkout blocker, not proof of successful installation on the live host. Live gateway behavior, hosting readiness, production canonical redirects and a complete v1.1.5 → v1.1.6 run remain acceptance gates.

New Core defensive checks and UI require a new immutable release, recommended **v1.1.7**, after separately approved CI/release validation. Do not overwrite v1.1.6. Build/sign through the existing official workflow and owner-managed signing identity. Revalidate the actual built manifest and package and supported-source list before publication.

## Authorized production deployment requirements (future step)

1. Deploy the Portal first through `docs/production-deployment.md` and existing versioned release scripts. Stage a full Git checkout with materialized Git LFS files, locked production Composer dependencies and built frontend assets. Preserve `.env`, `APP_KEY`, storage, uploads and historical releases.
2. Verify and exercise the existing production backup hook before activation. Preserve a restore-tested database/files backup and the previous release/revision. Inspect pending migrations; this change adds none. Run only previously reviewed safe pending migrations, never resets or seeders.
3. Set `AUTOMATIC_UPDATE_PORTAL_ORIGIN=https://campaignmanager.ng` in the Portal shared environment and rebuild its configuration/view caches. Confirm canonical HTTPS checkout and callback URLs, valid TLS, and expected redirect behavior. Both approved official web origins must remain controlled by the operator. The new Core API client requires a direct successful HTTPS API response, not a host redirect.
4. Confirm all five scope prices, currency, coverage duration, contact registration, paid ledger, active payment methods and bank accounts. Do not initialize a real payment merely to test connectivity. Verify signed checkout output using the existing installation credential server-side without exposing that credential in screenshots or logs.
5. After approved activation, verify exact deployed revision, `/up`, registration/update readiness, login, existing uploads and historical release availability. Keep code rollback available. There is no automatic authority in this document to deploy.

## Safe acceptance plan for app.campaignmanager.ng

1. Record the installed version (1.1.5), registration UUID/domain/scope, database identity, migration history, existing user/account sentinel and upload hashes. Record exact per-location-table row counts and their total (currently reported approximately 186,935). Take independent restore-tested database/application/configuration backups outside the document root.
2. Rehearse failure cases and the published upgrade on an isolated disposable clone first. Use test payment configuration only in that isolated environment. Verify restore of original data and files. Never exercise failure injection against the live installation.
3. With production Portal deployment separately approved and healthy, use Official Updates to discover v1.1.6. Verify its signature, package hash and supported source. Confirm discovery/review created no new stage/package download.
4. For the absent-coverage case, inspect the Presidential price, currency, coverage duration and canonical checkout link. Confirm it identifies this installation. Stop before a charge unless the owner separately authorizes the specific payment. Repeated checkout/retry must reuse the order/reference; pending bank transfer must not authorize a package download.
5. Once genuine payment is independently confirmed, return through the Portal link. Require the installation's Super Admin login where necessary. Recheck coverage/release/readiness and confirm backup/update explicitly. Do not patch v1.1.5 application files manually for this test.
6. Observe the detached worker/status page. Verify database/file backups, maintenance ownership, approved migration subset, health checks, recovery availability and preserved configuration/deployment identity. If progress is interrupted, inspect the private journal and supervisor rather than starting another job.
7. Confirm installed v1.1.6, HTTP 200, login, exact baseline location counts, existing customer/campaign records, upload hashes, registration/licensing state and Portal completion receipt. Ensure no duplicate payment/order and free manual release navigation still works. Retain all backups through owner sign-off.

## Rollback

Portal: use the versioned deployment rollback for the exact failed revision, e.g. `bash DEPLOY_ROOT/releases/BAD_SHA/deploy/rollback.sh DEPLOY_ROOT BAD_SHA`, after confirming the current revision and prior-release record. This switches code; it does not reverse database changes. Preserve the canonical-origin environment setting and rebuild caches using the restored runtime. This correction adds no migrations.

Core: before apply, cancel only a still-queued job with `php artisan campaign:updates-cancel JOB_UUID`. Running/interrupted jobs must use the supervisor/recovery path. If automatic restoration cannot validate, leave maintenance enabled and retain private logs/backups. Under operator approval, use `php storage/app/updates/jobs/JOB_UUID/recover.php --confirm=JOB_UUID --restore-database`. Recovery verifies backups, leases, maintenance ownership and database identity. Never restore a pre-update snapshot after reopening for new writes. A verified update that reopened requires an operator-reviewed forward correction or reconciled restore, not blind rollback. Read the current private journal before choosing any action.

## Workspace and branch hygiene

Only `main` is retained as a local development branch. The older local Core checkout's seven uncommitted files were compared byte-for-byte with the current upstream source: every change was already committed upstream. That redundant checkout was removed after final source verification; retain any older distinct work until reviewed.

Remote branch ancestry was inspected. The following are fully contained in current main and are candidates for deletion after rechecking their heads:

| Repository | Merged branch |
| --- | --- |
| Core | `fix/coverage-first-updates` |
| Core | `fix/latest-update-116` |
| Core | `release/1.1.4` |
| Core | `ui/consistency-v1.1.1-20261017` |
| Core | `ui/release-v1.1.2-coverage` |
| Core | `ui/update-trust-path-fix` |
| Portal | `ui/release-v1.1.2-coverage` |

Three Core branches are not ancestor-merged: `feat/required-installation-registration-v110` (11 branch-only commits), `fix/stabilization-and-update-foundation` (6), and `ui/update-manifest-size-hotfix` (1). Review cherry-picked/equivalent changes before discarding them. Do not merge or delete them merely for appearance.

The connected GitHub tool does not expose branch deletion; the browser is not signed in. No remote branch deletion was performed. From an authenticated full checkout, fetch/prune and verify each branch with `git merge-base --is-ancestor origin/BRANCH origin/main` immediately before `git push origin --delete BRANCH`. Stop on nonzero ancestry status or a changed branch head. Never delete `main`, tags, releases or published assets.

## Verification results

- PHP 8.3.6, isolated SQLite Core tests: **539 passed, 2,315 assertions**. Latest focused updater/official-update/checkout run: **99 passed, 383 assertions**.
- Portal, isolated MariaDB 10.11.14: **222 passed, zero failed, 1,113 assertions**. All **35** automatic-update checkout/payment tests passed. The earlier three geography failures were resolved by materializing the exact six existing Git LFS objects and verifying every object against its recorded SHA-256 and byte size. Five files required their original CRLF line endings; the polling-unit file also retained one original LF line ending. Its exact SHA-256 is `57420753f483559675f5bbce911ba4096b3d8e758659f5a40fc22686bd356938`, size 19,149,113 bytes. No dataset content, expected counts, LFS pointers or application/test logic changed. Normal full checkouts must still materialize LFS with `git lfs pull`; existing CI already requests LFS checkout.
- Both locked Composer installations, Vite production builds, application PHP syntax checks, new-class Pint checks and Blade view compilation succeeded. Composer locked audits and npm production audits found zero advisories.
- The separate MariaDB integration script passed native, missing-CLI and broken-CLI mixed-engine backup/restore, database objects, concurrent writers, lock cleanup, truncation, unexpected tables, permissions and InnoDB snapshot consistency. Native clients were configured explicitly for this isolated runtime.
- The full GitHub CI matrix (PHP 8.2/8.3, MySQL 8 and additional MariaDB versions), cPanel HTTP/worker behavior, real gateway transactions, live production data and an actual full published-package upgrade were not executed. They remain explicit approval/acceptance gates.
