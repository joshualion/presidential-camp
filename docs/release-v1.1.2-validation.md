# 1.1.2 local verification and publication status

Core local suite: 410 passed, 1,824 assertions (PHP 8.3, SQLite, explicit testing environment).
Portal local suite: 211 passed, 1,060 assertions (PHP 8.3, isolated MariaDB).
Core production frontend build passed. Locked Composer and production npm audits passed.

A production-dependency test candidate built successfully. Its compact discovery manifest is 8,976 bytes. Both signatures and 15,151 embedded file hashes were verified. The unchanged 1.1.0/1.1.1 discovery implementation accepts it and identifies 1.1.2 as a verified update. This candidate uses an isolated test signing key and MUST NOT be published as an official release. The official workflow must use the established owner signing identity.

Tests cover paid coverage across releases, expiry, original retries, scope isolation, refunds, duration snapshots and stale quote consent, stable original payment dates, duplicate checkout prevention, compact/embedded signature binding, bootstrap replacement with archived originals, and safe migration/backup behavior. No new live payment, customer installation, Portal production deployment or official publication has been performed.

An initial Core run used the production URL from the local environment example and forced HTTPS in route tests; the final complete run explicitly used testing/localhost and passed. Initial Portal runs reused a stale MariaDB data directory; final complete verification used freshly initialized isolated database storage and passed.

Keep the Core repository private. Deploy the Portal coverage migration and code first using the existing verified backup/activation procedure; live health must report automatic_updates.coverage_protocol=1 and coverage_ready=true. Then publish immutable v1.1.2 through the existing owner-signed release workflow on the tested source. This requires private Actions capacity or an owner-run local build with the established signing key and distribution credentials. Never extract GitHub secrets, generate a replacement production signing identity, publish test-signed artifacts, or replace v1.1.1 assets.

A previous bootstrap can be refreshed from the verified 1.1.2 bundle with:

    php bootstrap-install.php /absolute/path/to/existing/core --replace-bootstrap

The replacement archives previous bridge files privately and refuses active/unrecovered jobs or maintenance. Older applications keep their database and installation state. New 1.1.2 installations include the corrected engine directly.
