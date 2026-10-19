# Automatic updates and legacy installations

## Customer flow

Official Updates checks the public stable release. Downloading the source and manually installing it remain free. The optional hosted automatic-update service uses owner-configured Portal service prices for each campaign scope, per registered installation and target release. No amount is hardcoded in the Core or checkout. It is not a subscription, source-code licence or assistance-credit bundle. A failed attempt can retry the same purchase.

Configure **Super Admin → Services & Pricing → Automatic Application Update** for Presidential, Governorship, Senatorial, Federal Constituency and Local Government campaigns. Set each amount and currency and enable the required scopes. There are no pre-enabled monetary defaults. For a single flat price, enter the same amount for all scopes. The Core derives its actual installed scope (chairmanship maps to Local Government) and requests the matching Portal quote. The customer cannot authorize another scope merely by changing checkout parameters. Publicly distributed source can still be modified by its operator; this is not a claim of tamper-proof scope attestation.

Prices and currency are captured on the purchase. An owner price change affects new purchases, not existing paid or pending orders. If a price changes between quotation and checkout submission, checkout requires a review of the current price. Repeated submissions reuse the same installation/release order; retries do not create another charge.

The page presents the next relevant action: download and verify, continue to secure Portal payment, then update safely after payment confirmation. Installation registration stays in Settings. Registration and confirmation of the contact email happen once; the Portal account must use that confirmed contact address. Payment uses the existing **Paystack** or **bank-transfer** flow and verified ledger. Paystack confirmation authorizes the installation automatically. A submitted bank receipt remains unpaid until an administrator confirms it; afterward the same automatic authorization becomes available. There is no activation key for the customer to copy. The authorization is bound to the installation, campaign scope, target release, package checksum and job. A paid automatic update does not require a separate administrator approval or generate software activation entitlements.

The user's update consent starts a detached PHP worker. The initiating response displays progress directly, so maintenance cannot interrupt a redirect to the progress page. A random, HttpOnly, same-site cookie authorizes the standalone status endpoint. Status responses exclude backup paths, credentials and raw command output.

## Compatibility and migrations

The signed manifest explicitly lists supported source versions. The initial list is 1.0.8, 1.0.9 and 1.1.0; other versions and customized migration histories require review or an intermediate/manual upgrade. This is not a promise that every historical or modified installation can upgrade directly.

An older release without the updater needs the free `campaign-manager-updater-bootstrap.zip` companion asset. Extract it outside the web document root, then run:

```sh
php bootstrap-install.php /absolute/path/to/existing/core
```

The installer verifies the bundle signature against its embedded release public key and verifies every bundled file before installing the private bridge. It preserves Core, `.env`, uploads and the database. Sign in to the existing application as Super Admin and open `/campaign-update-bootstrap.php`. The bridge uses the existing authentication, CSRF protection and Super Admin authorization. It connects the installation only after explicit consent. No database reset or fresh installation is involved. The verified bridge endpoint and its private bootstrap directory are removed after success.

The worker verifies both outer and embedded release signatures and matching upgrade policies, hashes every managed file, checks source/target versions and reads the existing migration repository. Only pending migrations whose **exact hashes** appear in the signed `upgrade.approved_migrations` map can run. Release building fails if a reviewed migration changes. Adding a filename to the configuration requires reviewing its `up()` behavior and all supported source schemas. The updater runs ordinary `migrate --force` against a private directory containing only that approved pending subset. It never calls `migrate:fresh`, `migrate:refresh`, `db:wipe`, the browser installer or seeders.

For 1.0.8, the reviewed `2026_09_06_000001_add_boundary_inec_codes_to_location_tables` adds nullable indexed boundary codes when missing. The 1.0.9 archive already contains it. Existing migrations are not replayed, even if their `down()` methods remove tables.

## Safety sequence

1. Verify compatibility, package, registration/payment binding, PHP CLI, process execution, writable managed paths, queue state and disk capacity.
2. Acquire an installation-wide lock, recheck the plan and authorization, and enter maintenance.
3. Export a consistent database snapshot and verify it, then back up every affected existing file and `.env` in private storage. Persist recovery metadata before replacing anything.
4. Atomically replace verified managed files, preserving `.env`, storage, uploads, hosting rules and application key. Run only approved pending migrations using the new application runtime.
5. Boot the upgraded application through a fresh CLI process, check version and required database tables, rebuild caches and release owned maintenance mode. Report completion to the Portal; retry a temporarily unavailable receipt later.

Currently supported automatic hosts are Unix hosts with PHP 8.2+ CLI, `proc_open`, `nohup`, sufficient disk capacity, and MySQL/MariaDB InnoDB tables or file-backed SQLite. MySQL needs executable dump/import clients. Configure optional `CAMPAIGN_UPDATE_PHP_CLI`, `CAMPAIGN_UPDATE_MYSQL_DUMP`, and `CAMPAIGN_UPDATE_MYSQL_CLIENT` paths when required. Unsupported database options/topologies, custom storage paths, external queue backends, active database queue jobs and web PHP caches requiring an operator restart fail closed. Forced/external workers and independent database writers must be paused by the operator; maintenance mode does not control external processes. CLI health checks do not prove every hosting or web-server configuration.

## Failure and recovery

Before any migrations start, a failed application update restores verified original files automatically. Once a migration starts, a failure leaves maintenance enabled for operator recovery rather than guessing whether data should be rolled back. Backups are not automatically deleted. Keep them outside the document root with restricted permissions and review their retention after verifying the upgraded installation.

The private directory is `storage/app/updates/jobs/<job UUID>`. Its standalone recovery script works without a functioning application/vendor autoloader:

```sh
php storage/app/updates/jobs/JOB_UUID/recover.php --confirm=JOB_UUID --restore-database
```

The explicit database flag is required after migrations start. Recovery verifies all backups before changing anything, checks maintenance ownership and database identity, refuses unexpected new tables, restores the pre-update snapshot and original affected files, clears generated caches and exits maintenance. This is restoration of the existing database from its verified snapshot, not a fresh migration or database wipe. Do not restore after reopening the application for new writes; keep failed updates in maintenance until an operator has assessed recovery.

A worker that never started can be cancelled under the same lock:

```sh
php artisan campaign:updates-cancel JOB_UUID
```

This only releases a still-queued attempt. Running/interrupted jobs require recovery. A prepared package can be discarded in the page's secondary package options when no active or unrecovered job owns it. Payment and backups are retained.

Private `failure.log`, `worker.log` and `storage/app/updates/command-errors.log` support diagnosis. Never expose private job files, environment snapshots, database exports or recovery connection files through web hosting or support screenshots.

## Release gates

CI, merge, publication and deployment remain separate from implementation. Before enabling the service: deploy the Portal purchase migration, configure the automatic-update service prices and matching active Paystack or bank-transfer methods, verify gateway payment confirmation, and verify the live Portal health response reports automatic-update readiness. The manual publication workflow checks these gates. It prepares the signed Core archive and signed bootstrap companion with the existing owner-managed signing identity. No private signing keys ship to customers. GitHub's asset digest must match the package authorized by the Portal. Publish a new immutable 1.1.1 asset instead of replacing 1.1.0.

Monetization covers the hosted authorization, payment and updater convenience. Publicly distributed source cannot provide unbreakable DRM; this implementation does not claim otherwise.

## Local validation

Targeted tests cover signed policy compatibility, unreviewed/tampered migrations, protected paths, SQLite snapshots, standalone recovery, backup tampering, unexpected tables, progress cookies, bootstrap signature verification, authorization/payment boundaries, idempotency and retry ownership. Isolated rehearsals using the actual GitHub 1.0.8 and 1.0.9 release archives reached 1.1.1 with existing test account/password hash, environment and uploaded file preserved. The 1.0.8 rehearsal ran one pending migration; 1.0.9 ran none. Separate isolated MySQL/MariaDB checks exercised snapshot restore and standalone database/file recovery. These rehearsals are local evidence, not a completed production CI or live payment/deployment test.

## Payment currency setup

In **Super Admin → Campaign Manager → Payment Methods**, use **Add payment currency** to create separate inactive Paystack and bank-transfer entries for a currency. Existing NGN methods are retained. Configure the methods and verify that your Paystack account can accept the chosen currency; create an active matching bank account for bank transfer. Then enable the appropriate method. The service does not convert currencies. Paystack initialization sends the order currency explicitly, and verification checks amount, reference and currency.

Changing or disabling a service price does not reprice an existing purchase. Refunded or cancelled orders cannot authorize updating. The paid service covers one installation/scope and target release, with retry access to that purchase.
