# Customer-owned SMS (Community, 1.1.2)

Each authorized campaign administrator supplies and funds their own Termii or BulkSMSNigeria account. This feature is included in Community; it does not use Campaign Manager Portal credits, top-ups, update payments or hosted SMS entitlements. No external SMS credentials ship with the release.

## Setup

1. Super Admin opens **SMS messages → My SMS provider**, enables campaign SMS, selects eligible administrator levels, and optionally allows or denies individual administrators. Individual access overrides the level default; the campaign-wide off switch still stops sending.
2. Each enabled administrator opens their own provider settings, chooses Termii or BulkSMSNigeria, enters the provider's HTTPS base URL, approved sender ID and API key, and saves. The connection test reads account information without sending SMS. Fund the account and approve the sender ID at the provider.
3. Compose a message, choose permitted campaign contacts or paste known in-scope numbers, optionally schedule it, and preview. Review normalized recipient counts, invalid/duplicate/opted-out exclusions, message and estimated segments. Confirm permission and provider charges before queuing.
4. Follow the report for queued, accepted, delivered, failed, suppressed, cancelled or unconfirmed recipients. Acceptance does not establish delivery. Provider prices, sender approval, network restrictions and DND rules still apply; the application cannot guarantee delivery.

Termii accepts the account base URL on api.ng.termii.com, api.termii.com or v3.api.termii.com. BulkSMSNigeria uses https://www.bulksmsnigeria.com/api/v2 (the provider sandbox is supported at /api/sandbox/v2). Endpoints are allowlisted, HTTPS only, and redirects are disabled. Arbitrary custom endpoints and providers require a reviewed adapter rather than forwarding stored secrets to an arbitrary host.

## Delegation and audit boundaries

Only active eligible administrators have access. Super Admin configures the campaign and individual grants. A state administrator sends to stored contacts in their own state; other levels follow their own configured geography. A missing or unknown boundary fails closed. The installation's licensed/configured campaign boundary always caps access, including Super Admin and explicit nationwide audit grants.

Super Admin may separately grant wider audit oversight (for example, Region or Whole campaign). This does not grant permission to send outside the administrator's original scope or reveal another sender's provider credentials. Each recipient's geography is captured when the batch is confirmed, so changing a contact later does not move historic audit records. A partially visible national batch shows only permitted recipient rows and hides the complete message and totals. Settings revisions, access changes, confirmations, provider outcomes and delivery changes generate audit events. The batch report shows its latest 30 events when the whole batch is visible; full event history remains stored.

Pasted numbers for scoped senders must match existing permitted campaign contacts. Only a national sender within a national campaign may paste unknown numbers; these unclassified recipients are visible only to campaign-wide oversight. SMS contact sources reuse the existing campaign members, polling-unit agents and administrator audience rules; unsupported or geographically unresolvable contacts are rejected.

Credentials are encrypted using Laravel's application key, excluded from model serialization and never rendered back into forms. Keep APP_KEY and backups secure and stable. Saving provider settings creates a new immutable account revision and cancels unsent work associated with the previous revision. Historic credentials remain encrypted for delivery reporting. Super Admin audits outcomes through the application without accessing another administrator's secret.

## Queue and scheduler

Use a durable queue (database by default), shared lock-capable cache for multiple workers, and the Laravel scheduler. Synchronous bulk sending is blocked. Example worker:

```sh
php artisan queue:work --queue=sms,default --tries=1 --timeout=75
```

Set the queue connection's retry_after above 75 seconds (the default database value is 90). Restart workers after deployment. With shared hosting, a scheduler cron may run a bounded queue worker instead:

```sh
php artisan queue:work --queue=sms,default --stop-when-empty --max-time=50 --tries=1 --timeout=75
```

Run `php artisan schedule:run` every minute. The scheduler recovers queued batches after worker interruptions and polls delivery reports every ten minutes through queued jobs. Sends claim at most 50 recipients per provider call, with an account lock and a default limit of 60 requests per minute. Each confirmation is capped at 50,000 valid recipients. Monitoring queue depth and provider-specific throttling remains the installation operator's responsibility.

Permission, active account revision, current geographic sending boundary and opt-outs are checked again by the worker. Personal opt-outs affect that sender; Super Admin campaign opt-outs affect all senders. Opt-out removal requires renewed permission. Maintain lists as recipients request changes.

A send timeout, missing reference or interrupted submission is **unconfirmed**, not retried automatically. Check provider history and reconcile before making a new intentional send. There is no claim of exactly-once delivery across an external provider network. Delivery reports are authenticated API polls; unsigned callbacks are not trusted. Polling is bounded and stops automatically seven days after submission; absent evidence remains accepted/unconfirmed, never invented as delivered. Provider polling may not expose every recipient outcome; check the provider dashboard when evidence is unavailable.

## Updates and retained hosted service

The additive migration adds dedicated accounts, grants, batches, recipient snapshots, suppressions and event tables plus two settings columns. It does not reset any database, user, existing wallet or hosted SMS record. It is explicitly hash-reviewed for the signed updater. Reverting schema requires the reviewed backup/recovery process; down() refuses to delete SMS history.

The former Portal SMS backend remains available in source but is hidden from menus, blocked at its existing module routes, disabled in its HTTP client and excluded from scheduler jobs unless the installation owner sets CAMPAIGN_PORTAL_SMS_VISIBLE=true. This is a launch switch, not a secret feature in public source. It has no campaign-admin UI toggle. Existing hosted module permissions still apply when enabled. Future hosted-service activation requires its separate operational and entitlement review.

Provider contract references: https://developers.termii.com/messaging-api, https://developers.termii.com/balance, https://developers.termii.com/history and https://www.bulksmsnigeria.com/api. Automated tests use fake HTTP responses; production verification requires owner-supplied provider credentials and an explicitly consented small live send.
