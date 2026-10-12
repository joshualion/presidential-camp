# HANDOVER TO CHATGPT — MARKETING PORTAL PRODUCTION DEPLOYMENT

Prepared 2026-10-12, Africa/Lagos. Resume from pushed implementation; do not repeat the audit or recreate completed source. **Production is not deployed and v1.1.0 is not published.** Read `implementation-progress.md` for validation and exact source revisions.

## Revisions and review

Portal source `f49cd44318c015d219860e133f8a95156838fd03`, branch `feat/community-v110-implementation`, https://github.com/joshualion/campaigmanager.ng/pull/13 . Application source b735ca7 passed focused tests; f49cd44 adds the API deployment readiness gate and documentation. Core source `803b15056fa00933da5445d7d2f08a6e74e6c92a`, https://github.com/joshualion/presidential-camp/pull/2 . Public docs `36065e345b68f6f2e4ef48a0778e57aea649ad43`, https://github.com/joshualion/Campaign-Manager/pull/15 . Reports may advance branch heads. Preserve existing PRs/contributors' changes and inspect current heads before mutation.

Resolve Actions jobs failing before runner allocation; reason unknown. Obtain a genuine full-data checkout with Git LFS, pass the complete portal suite/build and staging checks, review and merge PR #13, record resulting main merge SHA. Deploy that exact tested main SHA. Do not merge while required checks are failing. Portal deployment workflow runs only from main, gated by `PORTAL_PRODUCTION_READY == 'true'`; leave that variable unset/false until prerequisites are satisfied.

## Confirmed host layout

| Setting | Value |
| --- | --- |
| SSH host / user / port | business106.web-hosting.com / payopxxb / 21098 |
| Deployment root | /home/payopxxb/campaignmanager-portal-deploy |
| Apache public symlink | /home/payopxxb/campaignmanager.ng/public |
| Legacy application | /home/payopxxb/campaign/campaigmanager.ng |
| Canonical origin | https://campaignmanager.ng |

Earlier backup paths supplied in the prior audit:
`/home/payopxxb/campaignmanager-portal-deploy/backups/portal-db-20261012T142852Z.sql.gz`
and `/home/payopxxb/campaignmanager-portal-deploy/backups/portal-files-20261012T143540Z.tar.gz`.
Verify existence, checksums, ownership, readability and coverage; do not assume freshness or restorability. A new backup is required before any cutover. The local test restored synthetic fixtures only; a real production backup has not been restored in isolation here.

## Preconditions and secure configuration

- Preserve the original document-root target, live APP_KEY, database credentials/data, storage, uploads, .htaccess and .well-known. Never run `migrate:fresh`, wipe/reset a database, regenerate a live APP_KEY or use `.env.example` as production secrets.
- Inspect current shared layout, available disk, permissions, PHP CLI/web versions/extensions, MySQL client support, verified SSH fingerprint, workers and scheduler. CLI PHP must be >=8.2. Do not assume local MariaDB fixture results validate the hosting MySQL/PHP runtime.
- `shared/.env`, `shared/storage`, `shared/public_uploads` must be real files/directories for backup, not dangling links. Shared storage must remain outside disposable releases. Preserve legacy app and previous managed release.
- Production APP_URL must be `https://campaignmanager.ng`, APP_DEBUG=false. Preserve mail, payment and other secrets. Optional new env: COMMUNITY_LEAD_RETENTION_DAYS=365, COMMUNITY_EVENT_RETENTION_DAYS=90, PORTAL_DB_DUMP_BINARY for a compatible dump binary. Run scheduler and workers against `current`.
- In GitHub `production`, configure PORTAL_SSH_HOST/USER/PORT, PORTAL_DEPLOY_ROOT, PORTAL_WEB_PUBLIC_LINK, PORTAL_BASE_URL as above. Secure secrets: PORTAL_SSH_KEY, PORTAL_SSH_PASSPHRASE if encrypted, PORTAL_SSH_KNOWN_HOSTS verified independently. Never paste them into chat or logs.
- The pre-deploy hook is now implemented: install the staged release's `deploy/pre-deploy.sh` to `shared/hooks/pre-deploy` with mode 0700. It runs `php artisan portal:backup ROOT` from the new release. Inspect and test it before readiness markers. Backup command uses private defaults file, streamed SQL gzip plus shared files tar and SHA256 manifest; any failure blocks activation. Backup files contain secrets/PII and must remain private, outside web root.

## Ordered deployment procedure

1. Fresh backup of live database, .env, storage and public uploads; verify archive/hash integrity and **restore to isolated database/filesystem**, inspect row counts and representative uploaded files. Never test restoration against live DB. Record evidence and secure retention policy. Stop cutover if recovery cannot be demonstrated.
2. Stage the tested, complete LFS release and production dependencies/frontend build. Confirm no unresolved pointers, correct public asset manifest and revision marker; staging validates login, payments/webhooks, existing downloads and dataset behavior. Never delete historical ZIPs or overwrite customer assets.
3. Inspect all pending production migrations, not just new ones. New reviewed files are `2026_10_11_000001_create_deployment_support_tables.php` and `2026_10_12_000001_create_community_download_leads.php`. They add support/registration/lead tables and consent/domain fields; contact-email width expands to 254. Verify compatibility with actual schema and old serving code. Use `php artisan migrate --force` only after backup; never blindly run migration down. The new leads migration deliberately refuses unsafe schema rollback.
4. For first bootstrap, follow `docs/production-deployment.md` and retained `deploy/stage-legacy-baseline.sh`. Record original public symlink, establish valid managed `current`/previous baseline and `.initialized` only after checks. Copy live shared data safely; do not substitute empty directories. Initial public symlink cutover needs its separately recorded original target for recovery.
5. With hook and layout ready, normal workflow builds immutable release, transfers complete data, invokes `deploy/activate.sh ROOT SHA`, backs up, migrates, synchronizes release registry, caches config/views, switches current atomically and restarts queues. Verify existing release sync cannot activate an absent/incomplete historical ZIP.
6. Verify `/up`, `/deploy-revision.txt` equals exact main SHA, and `/api/v2/campaign-manager/deployments/health` returns HTTP 200 JSON `ready:true`, `protocol:1`, `terms_version:"2026-10-12"` on canonical HTTPS with no redirect. Workflow now gates success on all three. Current live probe returned 302, so prerequisite remains unsatisfied.
7. Smoke test branded form, owner analytics, registration/receipt, contact verification, unsubscribe, domain change, pending failure/completion retries and operational message retrieval. Use synthetic contacts or explicitly authorized real email. Confirm no phone/campaign/password collection. Check existing account/payment/upload/download flows, worker health and daily retention. Retention command dry-runs by default; inspect impact before `--apply`.
8. Record deployed SHA/time, checks, backup paths/manifests, rollback target and operational owner in the progress report. Only then enable ongoing production workflow if appropriate and advance the core release gate.

## Rollback

For a failed managed release, `bash "$release/deploy/rollback.sh" "$root" "$failedSha"` restores the recorded previous code symlink only and refuses to replace a later unrelated release. Verify previous release supports additive migrated schema. It does not reverse migrations or restore DB/files. First manual public cutover must retain and restore the original document-root symlink target if needed. Restoring a live DB is a separate high-risk incident decision, never an automatic smoke test.

## Core release sequence after portal is ready

Do not publish a mandatory-registration installer until canonical production health passes. Resolve CI and hydrate the full core source. The core workflow `.github/workflows/community-release.yml` runs manually on main in protected `community-release`. Owner must commit established public key `config/release-public.pem`, securely configure matching `CAMPAIGN_RELEASE_PRIVATE_KEY` and narrowly scoped `COMMUNITY_DISTRIBUTION_TOKEN`, and set `COMMUNITY_LEGAL_REVIEW_APPROVED` only after privacy/terms review. Do not invent a replacement signing identity, expose keys in chat, or override failed checks.

Then build production-only dependencies, full tests/audits/frontend, generate signed candidate, inspect archive, verify SHA256 and signature, publish immutable `campaign-manager.zip`, `campaign-manager.zip.sha256`, `release-manifest.json` to new public v1.1.0 release. Verify publicly downloadable assets, clean installation against ready portal, upgrade/manual-update behavior, and record true size/hash/time/URL. Latest verified public release is still v1.0.9; no new ZIP/hash/signature exists. Do not overwrite or relabel old assets.

## Open follow-ups

Complete portal geography tests with genuine LFS data; diagnose zero-step runner failures; test real production backup restoration and mail/payment staging; validate physical PHP 8.2/shared host/MySQL 8; review legal text; configure established owner signing identity. Seven Tailwind 3 development build-chain advisories remain (production npm audit and Composer audit zero). Automatic paid conversion attribution and automatic updater apply/rollback are subsequent reviewed work. None warrants restarting implemented code.
