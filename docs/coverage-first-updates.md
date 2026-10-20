# Coverage-first automatic updating

The Official Updates page offers **Update automatically** and a free manual GitHub download.

1. Automatic Update validates signed release metadata and supported source versions.
2. Server readiness runs without downloading the archive or changing application data.
3. Installation coverage is checked with the Portal.
4. An unpaid installation sees secure checkout. Returning to the same screen refreshes coverage.
5. An installation with active coverage gets one consented **Back up and update** action.
6. The server checks coverage again before downloading. It reuses a matching, already-verified staged package where possible. The existing worker still independently authorizes the job, verifies signatures, backs up, applies files, runs approved migrations, checks health and provides recovery.

Direct legacy calls to package preparation are gated inside the download service. Free manual ZIPs remain on GitHub. Previously staged files are not silently deleted.

## Database readiness

MySQL/MariaDB backups use a transaction-consistent dump. Non-InnoDB base tables remain blocked, including immediately before export. The message names the affected tables and directs the administrator to database backup/hosting work. It does not offer an unrelated installation-registration link. Production code does not convert database engines automatically.

A disposable MySQL 8 CI check exercises a mixed InnoDB/MyISAM database, verifies refusal before export, then converts only its own disposable test table and checks a real backup, checksum and restore. Feature tests cover unpaid POST attempts, legacy preparation, paid coverage, payment return, wrong target versions and registration-specific help.

## Existing installations

Repository changes do not alter files already installed on customer servers. The revised signed bootstrap must be built and verified before using this flow on an existing 1.1.3 installation. Existing published 1.1.4 application ZIPs must not be replaced.

The owner's reported live database has not been inspected in this session. The earlier 1.1.3-to-1.1.4 release exercise used isolated SQLite and stubbed entitlement calls; it did not prove live MySQL readiness or real payment checkout.

Read-only diagnosis in the application's database:

```sql
SELECT TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_TYPE = 'BASE TABLE'
  AND UPPER(COALESCE(ENGINE, '')) <> 'INNODB'
ORDER BY TABLE_NAME;
```

Inspect and verify a full backup before reviewing any engine conversion. Do not alter tables blindly or bypass backup checks.
