# v1.1.8 implementation and approval handoff

## Result and boundaries

Core implementation: `df9ac4bd6eb3749567c39ff06bdc68cf4039fb9c` on `joshualion/presidential-camp/main`, following bootstrap checkpoint `f2e4057778942217bf203f94922bdf51cca8bc6a`.

Portal implementation: `2541efbbf61d42d727bb22496b5d313d50625379` on `joshualion/campaigmanager.ng/main`. Subsequent documentation/CI commits do not change that integration implementation.

The latest published release remains v1.1.7. Prepare v1.1.8; no release was published, production installation modified, payment initiated or repository visibility changed. Published v1.1.6/v1.1.7 assets remain untouched.

The implemented experience is **Check for Updates → Update Automatically → Pay if Needed → Return → Confirm → Install → Continue Using Campaign Manager**. Its components and isolated rehearsals pass. A genuine production payment followed by the production automatic upgrade is still unperformed; this flow is **not production-proven**.

## Confirmed causes and corrections

1. The legacy entry bootstrapped Laravel before binding its HTTP request. `BootstrapHttpRequest` now captures once, normalizes the front-controller mount and binds that same request before HTTP kernel initialization, handling and termination.
2. Symfony inferred the bootstrap filename as an application base directory, corrupting login/dashboard/assets. The request adapter uses the ordinary front controller while preserving original URI, HTTPS host, method, body and cookies. Actual root/subdirectory requests with a symlinked public root pass.
3. Customer controls exposed internal readiness, coverage and staging operations. Shared customer templates now present the three requested actions. Protected POST requests check identity/release/coverage internally; installation still requires explicit CSRF-protected consent.
4. Return links needed expiry and original-browser binding. Protocol 2 binds the registered HTTPS destination, release, hash, expiry and random session state with the Portal HMAC. Core remembers the proof, checks it on return, then revalidates authenticated coverage and the signed release. Legacy callers retain their old protocol.
5. Laravel's default schema listing included other databases visible to a shared account. Backup/rollback schema checks now explicitly select the installation database. A real separate-database sentinel test passes.
6. Registration identity and contact ownership had been conflated in the interface. Existing verified contacts are not reverified. The summary hides private identifiers; exceptional changes are secondary. Missing ownership proof remains a single guided email flow. Installation completion explains the one-time verification already sent by the installer. A different local Super Admin cannot transfer Portal ownership by editing their local email.

## Changed source

Core bootstrap: `app/Services/Updates/BootstrapHttpRequest.php`; `tools/updater/{entry,bridge,bootstrap-install,build-bootstrap}.php`; `tools/updater/screen.blade.php`.

Core workflow/security: `OfficialUpdateController`, `DeploymentRegistrationController`, `AutomaticUpdateFlow`, `UpdatePortalClient`, `PortalCheckoutAddress`, `UpdateProgress`, `DatabaseSnapshot`; `routes/superAdmin.php`; `config/{updates,release}.php`.

Core interface: shared Official Updates/automatic-update/registration views, the shared customer and registration cards, installer completion view, rebuilt Vite assets/manifest. Superseded compiled assets were removed.

Core validation/release: `BootstrapHttpRequestTest`, `UpdateReturnStateTest`, updated flow/UI/safety tests; `scripts/verify-{bootstrap-http,upgrade-bootstrap,mysql-update-backup}.php`; candidate/publication/post-publication workflows; v1.1.8 notes and recovery instructions. Candidate/publication workflows are manual-only. Their signed release pipeline now gates publication on actual packaged bootstrap HTTP tests as well as full upgrades from older official archives.

Portal: `AutomaticUpdateAuthorizationController`, `AutomaticUpdateCheckoutController`, `AutomaticUpdateNavigation`, `AutomaticUpdatePricingService`, `AutomaticUpdatePurchaseService`, `AutomaticUpdatePurchaseTest`, production workflow and integration documentation. Production deployment requires an explicit manual `deploy=true`; source pushes cannot deploy it.

## Verification

| Check | Result |
| --- | --- |
| Core full local suite, PHP 8.3 | 615 passed; 2,683 assertions |
| Targeted tests after database isolation change | 45 passed; 195 assertions |
| Final UI/flow regression | 39 passed; 181 assertions |
| Bootstrap request regression | 5 passed; 24 assertions |
| Core GitHub application CI | PHP 8.2 and 8.3 passed; run `39633913578` |
| Core GitHub database CI | MySQL 8.0, MariaDB 10.11 and 11.4 on PHP 8.2/8.3: all six passed; run `39633913568` |
| Portal full local suite, isolated MariaDB 10.11 | 223 passed; three production-geography tests blocked by unavailable exact polling-unit LFS bytes |
| New Portal expiry/tampering/retry tests | Passed within the 223 passing tests |
| Both repositories | PHP lint, Vite build, Composer audit and production npm audit passed |
| Packaged bootstrap HTTP on official v1.1.3/1.1.4/1.1.5/1.1.6 | All four passed |
| Full candidate upgrades from those four archives | All completed with signatures, backups, migrations, credentials, version, environment and upload preservation verified |

HTTP rehearsals use the actual ZIP installer, including refusal to overwrite an existing bootstrap and safe explicit replacement. They check guest login redirect and login page, Super Admin access, denial to Polling Unit Admin, sessions, missing/valid CSRF, dashboard URLs, CSS/JavaScript, private-file denial and no download/upgrade on GET. Fixtures are disposable and checksum-verified official archives, not the development branch pretending to be an older version.

Rehearsal target packages use an **ephemeral test signing identity**; production signing keys were not accessed. Only entitlement is stubbed in full upgrade rehearsals. Genuine provider verification, replay prevention, pending bank transfers, scope prices and retry behavior are covered by isolated Portal tests; mocked gateway responses do not prove real-payment acceptance.

The existing engine regression suite covers failed backups, failed migrations and health checks, worker interruption/leases, recovery/rollback and successful version reporting. Real database checks cover native export, missing/broken CLI fallback, mixed InnoDB/MyISAM/MEMORY/Aria engines, database objects, concurrency, truncation, permissions, transaction snapshots and separate-schema isolation.

## Blockers and release readiness

Core source and CI checks are green. Portal's exact polling-unit LFS file remains a local data-fixture blocker, not a waived test. Portal CI must materialize the exact repository LFS data and pass before Portal deployment. Check its latest workflow result rather than assuming private Actions can run. Publication still requires the official signing identity and the owner-approved candidate/publication gates. Local Apache/LiteSpeed/cPanel behavior is not claimed from PHP's test HTTP server; production acceptance remains necessary.

The old Portal canonical-host correction can unblock v1.1.5 checkout navigation. **Portal-only changes cannot repair the faulty bootstrap or the installed Core engine.** A new immutable Core/bootstrap release is required; proposed version **v1.1.8**.

## Production requirements and acceptance sequence

1. Obtain owner approval. Record current deployed revisions and baseline health, installed version, representative location/user counts, uploads and registration domain/email. Retain recoverable matched application/database backups. Do not reset data or delete old backups.
2. Deploy the compatible Portal revision first using its existing versioned backup/stage/activate procedure at `/home/payopxxb/campaignmanager-portal-deploy`. Keep the established production `.env`, shared uploads and payment credentials. Use canonical `AUTOMATIC_UPDATE_PORTAL_ORIGIN=https://campaignmanager.ng`. No new schema migration is introduced by this integration. Verify exact revision, health and `automatic_updates.return_protocols` containing `2`.
3. With separate release approval, run the private candidate workflow for `1.1.8` using the established production signing identity. Require all application/database checks, archive signatures/checksums and both older-archive rehearsal gates. Review artifacts before approving the manual publication workflow. Never replace existing release assets.
4. For `app.campaignmanager.ng`, approve installing the newly published signed bootstrap into `/home/payopxxb/presidential-camp`. Follow the recovery guide; use `--replace-bootstrap` only after confirming no active/unrecovered job. The installed version must remain **1.1.5** at this point.
5. Sign in as Super Admin. Check for Updates and confirm the latest release is shown. Verify that checking/refreshing did not stage an archive or change the version. Verify Manual Update opens the official free release page.
6. Choose Update Automatically. Confirm the registered presidential scope and Portal-configured price/period at checkout. Separately authorize any production payment test. Verify retries reuse the purchase/reference. Test cancellation/pending return without coverage or installation; a pending transfer must remain pending until approved.
7. Complete one explicitly authorized genuine payment/approval. Verify server-side confirmation activates coverage and the signed return reaches this installation. Core must revalidate identity, coverage and the signed target. Returning or refreshing must not install automatically.
8. Explicitly confirm installation. Verify complete private application/database backups, maintenance, approved pending migrations and managed replacement. Observe completion and health verification. Confirm installed **1.1.8**, sign-in, uploads, registration identity, campaign data/counts and the previously repaired settings/election-result routes. Retain backups until owner acceptance.

## Rollback and hygiene

Before a Core update, Portal rollback uses its existing previous-release activation procedure, preserving shared data/uploads/`.env`. Coordinate any rollback to an older Portal with Core v1.1.8 because it requires protocol-2 return proofs.

An unverified failed Core update should restore its verified backups automatically. If it requires assistance, keep maintenance enabled and use the actual job's private `recover.php --confirm=<job-uuid>`; add `--restore-database` if migrations started. Do not bypass the refusal to roll back an already verified/reopened update. An independent operator-reviewed restore must pair application and database snapshots and preserve the matching application key, installation identity and uploads. See the recovery guide for exact commands. No reset, seeder or `migrate:fresh` is permitted.

Both active development checkouts use `main`; no feature branch or redundant development checkout was created. Generated test media and obsolete compiled assets were removed. Disposable rehearsal installations/test signing material are temporary fixtures, not production backups. Never prune unmerged work or customer backups as part of source cleanup.
