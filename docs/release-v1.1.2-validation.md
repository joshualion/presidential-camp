# 1.1.2 verification and publication status

Core final local suite: **442 passed, 1,949 assertions**, no skipped tests (PHP 8.3, SQLite, explicit testing/localhost environment). The SMS-specific suite also runs against isolated MariaDB. Production frontend build passes. Locked Composer and production npm audits report zero known vulnerabilities.

The final production-dependency candidate includes Community customer-owned SMS. Its compact discovery manifest is **9,468 bytes**. Both manifest signatures and **15,173 embedded file hashes** are verified. The unchanged 1.1.0/1.1.1 discovery implementation identifies 1.1.2 as a verified update. The final sending service, worker, migration and pinned migration policy match the archive. SMS source, setup guide, new job and model files are required package entries; private runtime configuration, database files, signing secrets and local QA material are excluded.

This candidate uses an isolated **test signing key** and MUST NOT be published. The official workflow must use the established owner signing identity. v1.1.1 and its immutable assets remain untouched. No GitHub CI run, official v1.1.2 publication or real SMS delivery has been performed for this SMS change.

The 32 SMS tests cover administrator-level delegation and individual overrides, own-account isolation and encrypted keys, secret-free validation sessions, endpoint restrictions, cross-state and regional audits, explicit wider audit oversight, campaign caps, partial-batch content restrictions, scoped pasted contacts, opt-outs before and after confirmation, preview consent and replay protection, account changes and permission revocation, geographic changes before sending, schedules, bounded chunks, safe interrupted/uncertain submissions, both provider payloads, authenticated delivery polling and Unicode estimates. Provider tests use fake HTTP responses; production smoke verification needs owner-supplied credentials and consented recipient numbers.

The additive SMS migration is hash-pinned in config/updates.php and its six new tables are declared for reviewed updater recovery. It preserves existing users, wallets and audit data. No fresh migrations, database wipes or seeders run during an update. Keep APP_KEY stable for stored encrypted credentials and backups. Follow docs/customer-owned-sms.md for provider setup, opt-outs, queue workers, polling limits and unresolved-provider reconciliation.

Coverage and updater regression checks include paid coverage across releases, expiry, original retries, refunds, scope isolation, duration snapshots, stale quotes, stable payment dates, duplicate checkout prevention, compact/embedded signature binding and bootstrap replacement with archived originals. Earlier test runs identified and corrected a packaging-guide exclusion. One local installer check collided with another test process sharing the installed-state fixture; the isolated full-suite run passes.

Portal coverage changes are already merged and deployed: PR #22, production commit 6015f1af2fbeb4cefccebe48e919dbaee7490ba4. Its local and GitHub suites passed **211 tests, 1,060 assertions** and production activation succeeded. Live health reported coverage protocol 1, coverage_ready, payment_ready and the five configured campaign scopes. Customer-owned SMS requires **no additional Portal deployment** and does not use Portal credits or update payments. The reserved hosted SMS service remains hidden and inactive by default.

Keep Core private until the owner opens the agreed Actions window. Then run checks on this reviewed source, merge it, and use the existing owner-signed Community v1.1.2 publication workflow. Check live Portal registration/update health before publication. Never extract GitHub secrets, replace the production signing identity, publish test-signed artifacts or replace v1.1.1 assets.

A previous bootstrap may be refreshed from the verified official 1.1.2 bundle using:

    php bootstrap-install.php /absolute/path/to/existing/core --replace-bootstrap

Replacement archives previous bridge files privately and refuses active/unrecovered jobs or maintenance. Existing applications retain their database and installation state. New 1.1.2 installations include the corrected engine directly.
