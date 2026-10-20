# Automatic updating in 1.1.6

## Customer flow

1. Check signed release metadata, supported source versions, PHP/background execution, writable paths, storage and database recovery capabilities.
2. Check the installation's Portal coverage without downloading the application archive.
3. Uncovered installations continue to Portal checkout; covered installations see **Back up and update**.
4. Recheck coverage before archive download and independently authorize the installation/scope/version/checksum-bound worker.
5. Enter maintenance, drain normal database queue jobs, back up, apply verified files, run only approved migrations and check health.
6. If installation fails, attempt automatic database/file restoration and check the restored application before reopening it. Coverage remains available for retry.

Free manual downloads remain on GitHub. Older unused staged packages are replaced automatically after coverage is confirmed; a matching verified package is reused. Active and unrecovered update packages and private recovery backups are protected.

## Engine-aware backups

InnoDB uses a consistent transaction snapshot. A database containing MyISAM, MEMORY or MariaDB Aria uses one dedicated connection holding explicit READ locks across all application base tables for the whole export. READ LOCAL is not sufficient because it allows concurrent MyISAM inserts. No global lock across other hosted databases is requested. No production engine conversion or repair is performed.

Native dump/import clients are preferred when both are available. If absent, incompatible or unsuccessful, the updater automatically starts a fresh snapshot with the dependency-free streaming PDO exporter. Its private newline-delimited format stores individual base64 SQL statements, preserves NULL/binary/Unicode/numeric/temporal/spatial values, skips generated insert columns, explicitly includes invisible columns, restores views in dependency order and restores triggers after rows. Schema, routines, triggers, disabled events, engines and row-count manifests are retained. It does not parse SQL delimiters, evaluate PHP or unserialize backup data.

Exports are written to partial files with restricted permissions and atomically finalized after completion checks. Incomplete snapshots are discarded, with at most three fresh attempts. PDO restore validates the entire stream before changing data, then checks restored row counts and object/engine inventory. Native restore checks object/engine inventory; a checksum/completion marker alone is not a full proof of restoreability.

Readiness performs real CREATE/INSERT/UPDATE/ALTER/DROP probes using randomly named, isolated probe objects; existing client rows are not modified. When corresponding objects exist, it also probes view/trigger/routine/event recreation. It verifies readable definitions and, for mixed engines, actual read-lock acquisition. Probes are immediately removed. Readiness is not a promise that permissions or disk capacity cannot change after checkout.

## Interrupted updates

A separate PHP supervisor waits for the worker. On an unexpected exit it checks both the installation lock and artisan command leases before invoking the standalone private recovery kit. A surviving migration must finish before recovery starts. Browser progress polling and the existing Laravel scheduler can restart orphan recovery after a process/host interruption. Scheduler recovery runs in maintenance mode; host-wide restart recovery still needs either a returning progress request or the configured scheduler to run.

The worker journals the completed database/file backup before applying files. It validates all file backups before rollback, restores the database after started migrations, restores managed files, clears generated caches and verifies the previous version through a fresh CLI health check. The standalone recovery kit does not depend on Laravel/vendor boot for file/database restoration. Failed recovery verification leaves maintenance enabled and retains private backups and diagnostics. Final verification is durably committed before reopening; recovery must never restore an old snapshot after the application may have accepted new writes.

Automatic updating assumes exclusive application write ownership while in maintenance. Forced queue workers, direct external writers, independent schema changes, active database events and unsupported distributed/remote database topologies need a verified pause strategy. Database table locks protect a mixed-engine export, not later migration/rollback writes. Discarding/remote engines such as BLACKHOLE/FEDERATED are not treated as ordinary local tables. Denied privileges, corruption and exhausted storage cannot be bypassed safely.

## Existing installations and verification

Code already installed in 1.1.3/1.1.4/1.1.5 does not change when a new release is discovered. Install the new signed companion bootstrap once; then use its authenticated Automatic Update screen. The bundle preserves existing Core, environment, uploads and database, and verifies every updater file against the established signing identity. It accepts source versions through 1.1.5 and can archive a previous bootstrap when no active/unrecovered job exists.

Version 1.1.4 and 1.1.5 assets must never be replaced. Prepare 1.1.6 with:

- PHP 8.2/8.3 application, worker, recovery and bootstrap tests.
- MySQL 8.0 and MariaDB 10.11/11.4 real backup/restore, native/PDO fallback, concurrency, engine preservation, denied permissions, truncation and unexpected-table checks.
- Signed bootstrap rehearsals from official 1.1.3, 1.1.4 and 1.1.5 archives into the immutable signed 1.1.6 candidate.

These checks are release gates. Test code being present does not mean it passed. Isolated rehearsals stub entitlement calls; they do not exercise real payment or the owner's production database.

## Latest release and skipped versions

The latest signed release takes priority over an older downloaded package. Opening Automatic Update refreshes official metadata. Starting an update checks the signed version and checksum again; a newly changed target requires a fresh review. An older unused stage is superseded only after coverage and compatibility checks. A matching stage is reused and verified by the update policy before applying. Recovery backups are never removed by this process.

Release 1.1.6 supports direct upgrades from 1.1.3, 1.1.4 and 1.1.5. Intermediate installation is unnecessary for those paths. Existing older updater code cannot replace itself just because a release is published: install the signed 1.1.6 bootstrap once, then open `/campaign-update-bootstrap.php` as Super Admin. Future skip paths remain subject to the signed release compatibility policy.
