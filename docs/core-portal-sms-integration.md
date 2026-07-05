# Core application portal SMS integration

## Ownership boundary

The Campaign Manager core application is a thin client of the SMS system hosted by Campaign Manager Portal. The portal owns provider credentials, provider routing, rate cards, the installation/API-client master wallet, payment confirmation, provider settlement, and refund eligibility. The core application must never call Termii, BulkSMSNigeria, RouteSMS, WireDirectSMS, or another provider directly.

The core application owns the organization allocation wallet, optional user/admin allocation wallets, their immutable local ledgers, recipient/message history, access and scope enforcement, and local audit history. A local allocation balance does not guarantee that a send can proceed: the portal master wallet, configured route, approved sender ID, and provider must also be available.

## Required settings

Only a super administrator can configure portal SMS. The settings are stored on the existing `system_settings` singleton:

- enable portal SMS;
- portal SMS API base URL;
- core API client key;
- encrypted core API client secret;
- default regular or priority route;
- system-route, sender-ID-request, sending, top-up, and credit-request feature switches;
- last connection result and last successful synchronization time.

The secret uses Laravel's encrypted model cast. It is accepted only as a password field, is retained when that field is blank, is hidden from serialization, and is never rendered after save. Provider credentials are not core settings.

The existing local license is reused as installation identity. Requests can include the license key, activation ID, app URL/domain, existing server fingerprint, package, and licensed scope. No second activation system is introduced.

## HMAC authentication

Each request includes `X-CM-Client-Key`, `X-CM-Timestamp`, and `X-CM-Signature`. Idempotent mutations also use `X-CM-Idempotency-Key`.

The canonical signature input is five newline-separated values, matching `SmsPortalClientAuthService` in the portal:

1. Unix timestamp;
2. uppercase HTTP method;
3. absolute request path beginning with `/api/core/v1`, followed by the exact raw query string when present;
4. lowercase SHA-256 hex digest of the exact raw request body; GET requests hash the empty string;
5. `X-CM-Idempotency-Key`, or an empty string when the header is absent.

Objects in the transmitted JSON body are recursively key-sorted for stable retries. GET query strings are encoded once with RFC 3986 escaping and those exact bytes are both signed and transmitted. The portal does not re-encode JSON or query strings during verification. The signature is the lowercase SHA-256 HMAC hex digest using the decrypted portal client secret. The timestamp is Unix epoch seconds and the portal permits 300 seconds of clock skew. The client secret, signature, and portal exception details must not be logged.

The portal replay cache permits an identical signature only for safe reads, estimate, or a whitelisted idempotent mutation carrying the same business idempotency key. Same-second mutation retries therefore reuse the original idempotency key and payload. Conflicting payload reuse remains rejected by portal business-idempotency hashes, while non-idempotent repeated POST signatures remain blocked.

The core client normalizes portal responses into `ok`, HTTP `status`, stable error `code`, safe user `message`, `data`, and the idempotency key. Known errors receive local friendly messages. Portal stack traces and arbitrary response internals are discarded.

## Wallet and allocation synchronization

There is one organization wallet, enforced by the stable owner key `organization`. A user wallet uses `user:{local user id}` and includes the stable user UUID, access level, and wallet UUID in portal requests.

Portal allocation synchronization follows this order:

1. list confirmed portal allocations that have not been acknowledged by the core;
2. resolve the organization wallet or the user by stable core user reference;
3. create the local allocation mirror;
4. lock the local wallet, credit it, and create its immutable transaction in one database transaction;
5. commit the local transaction;
6. mark the allocation synchronized on the portal.

The portal allocation reference and ledger idempotency key prevent a retry from crediting twice. If portal acknowledgement fails, the local credit remains safe and a later retry only repeats acknowledgement.

## Top-up flow

A permitted user can fund their own wallet or contribute to the campaign organization wallet. The core sends the user UUID, wallet UUID, display identity, access level, and useful scope metadata to the portal. It stores a pending local top-up mirror and presents the returned Paystack URL or manual-bank instructions.

Initiating a top-up never credits a local wallet. Portal payment confirmation creates a confirmed allocation event. Only allocation synchronization credits the destination wallet.

## Credit-request flow

A permitted user chooses an allowed destination wallet and submits amount, reason, and urgency to the portal. The core stores the portal request reference and mirrored status. Approval/fulfilment remains a portal decision. A fulfilled request creates an `admin_credit` allocation event; allocation synchronization performs the local credit and marks the local request fulfilled. The portal exposes client-scoped paginated list and detail routes, and the core client maps both operations centrally.

## Local transfers

An authorized administrator can move local allocation from the organization wallet to a user wallet, or back when policy permits. Both wallets are locked in deterministic order. The operation rejects insufficient available balance and creates paired `admin_transfer_out` and `admin_transfer_in` immutable ledger rows plus an audit event. It does not change the portal master wallet because it only redistributes the installation's local allocation.

## Batch reserve and submission

`SmsBatchService` validates and normalizes phone numbers, deduplicates recipients, requires an approved mirrored sender ID, and asks the portal for an estimate. It then locks the selected wallet, checks available local balance, creates long-term local batch/message records, and reserves the estimate with an immutable `sms_reserve` entry.

The portal batch request includes the selected local allocation type, core user reference, and core wallet reference for charge-allocation auditing. It uses the local batch UUID as a stable idempotency key. If the portal confirms no batch was created, the local reservation is released with an `sms_reversal` entry. The code does not fall back to another wallet.

## Settlement and message synchronization

Batch synchronization reads portal batch settlement and recipient messages. Delivery state and billing state are stored separately. Local reserved value is debited only from the portal's cumulative `debited_amount`. A local post-debit refund is created only from a larger portal-reported `refunded_amount`; a pre-debit reservation release is applied only from a larger `released_amount`. `reversed_amount`, `billing_review_count`, `settlement_status`, and currency are mirrored for reporting. The core never derives any aggregate from delivery status. Every applied settlement evidence key is idempotent.

Message resources mirror portal-policy `refund_eligible`, explicit `refunded`, `refund_reason`, `refund_evidence`, `billing_status`, and `billing_review_required`. The core keeps eligibility nullable for backward-compatible responses and never treats a missing value or failed delivery as evidence that a refund is due.

Failed, expired, rejected, undelivered, and DND-blocked delivery states do not imply a refund. Reports display `Delivered`, `Failed`, `DND Blocked`, `Charged`, `Refunded`, and `Billing Review Required` based on portal fields. The core does not calculate a refund from delivery state.

After local message state is committed, the core calls `POST /sms/batches/{batch_reference}/messages/mark-synced` with the synchronized message references. Recipient/message records remain in the core for long-term reporting even if portal retention later anonymizes its copies.

## Sender ID synchronization

The core lists sender IDs from the portal and mirrors pending, submitted, approved, rejected, blocked, or disabled status plus rejection/admin notes. New requests are submitted only when the corresponding setting and permission are enabled. `SmsBatchService` accepts only locally mirrored `approved` sender IDs; portal validation remains authoritative.

## Access, module gate, and auditing

SMS uses the existing `sms` local-license module gate, Spatie permission system, access levels, shared access-level route prefixes, and sidebar. Permission records are installed for:

`sms.view`, `sms.compose`, `sms.send`, `sms.sender_id.request`, `sms.reports`, `sms.settings`, `sms.wallet.view`, `sms.wallet.topup`, `sms.wallet.transfer`, `sms.credit.request`, `sms.organization_wallet.view`, and `sms.organization_wallet.use`.

Super administrators retain full access. Conservative access-level fallbacks preserve legacy installations only until granular permission records are installed. SMS navigation is hidden until the module is licensed and portal SMS is enabled. Portal settings remain superadmin-only.

Once an SMS permission record exists, that granular permission is authoritative: a false role/user permission check does not fall through to access-level authorization. The access-level fallback applies only to legacy installations where the requested permission record does not yet exist. Super administrators retain the explicit bypass.

SMS audit records cover settings changes, connection tests, allocation credits, top-ups, credit requests, local transfers, sender requests/sync, batch submission/sync, and local settlement ledger activity. Audit metadata redacts secret/signature keys.

## Compose and recipient selection

The core compose workflow is the only place that resolves campaign recipients. It supports members, polling-unit agents, executives/administrators, and manually pasted phone numbers. Database audiences are always passed through the existing `LocationScopeService`, licensed-scope enforcement, and structural location option services before optional state, LGA, ward, polling-unit, access-level, account-status, onboarding, phone-availability, or agent-status filters are applied.

Regional, state, senatorial, federal, LGA, ward, and polling-unit administrators must have their corresponding assigned scope. Missing scope fails closed. Submitted location identifiers are revalidated against the user's structural and licensed scope; hidden-field changes cannot broaden the query. Super and national administrators remain limited by the installation's licensed campaign scope.

Phone numbers are normalized locally from `0XXXXXXXXXX`, `234XXXXXXXXXX`, or `+234XXXXXXXXXX` into E.164-style values. Invalid values are counted and skipped, and duplicates are removed after normalization. The estimate confirmation displays matched, valid, invalid, duplicate, and final chargeable counts plus a small recipient preview. Manual numbers do not grant access to or reveal any campaign user record.

## Estimate, confirmation, and send

Estimate requests resolve the audience, call the portal estimate endpoint, compare the estimate with the selected local wallet, and create no reservation or ledger entry. The confirmation state is held server-side under a short-lived random token. Confirmation posts only that token; the core reloads the saved selection, revalidates the approved sender ID and wallet permission, and resolves the audience again under the current user's scope before sending.

The sender explicitly selects their own active SMS wallet or, when permitted, the organization wallet. The core never falls back to another wallet. Insufficient personal allocation links to top-up and credit-request actions; insufficient organization allocation is reported without changing the charge source.

On confirmation, `SmsBatchService` obtains a fresh portal estimate, creates the local batch and recipient history, and reserves the estimate in the selected local wallet. It submits the portal batch with the persisted idempotency key and charge-allocation audit references. A definitive portal rejection releases the reservation. An uncertain network/result state retains the reservation for reconciliation. Portal acceptance retains the reservation until settlement evidence reports debit, refund, or an explicit release.

The confirmation warns that failed, rejected, expired, undelivered, and DND-blocked delivery states do not establish a refund. Delivery state and billing state remain separate throughout the report UI.

## Large and scheduled batches

The core does not impose a 100-recipient provider-chunk limit. `SMS_MAX_BATCH_RECIPIENTS=0` leaves the core audience uncapped; a positive value applies an installation-level safety limit independently of provider dispatch chunking. `SMS_LARGE_BATCH_WARNING` controls the confirmation warning threshold. The portal remains responsible for provider chunking and its own accepted contract limits.

Optional scheduling is exposed because the audited portal accepts `scheduled_at`. The core rejects past dates and sends an ISO-8601 UTC value. The scheduled value is retained in batch metadata for local reporting.

## Report authorization and synchronization

Report access is fail-closed. A sender can view their own batches and recipient history. Super administrators and users with organization-wallet visibility can view organization-wide batches. Route-model access is checked again for list, detail, and manual synchronization, preventing a lower administrator from changing a batch identifier to read another sender's recipients.

Reports show sender, source context, target scope, wallet source, timestamps, delivery counts, billing counts, estimated/reserved/debited/refunded amounts, billing review state, and paginated recipient history. Manual synchronization is available on batch detail. Existing allocation and batch synchronization jobs are scheduled for dispatch so normal operation does not depend only on the manual control; queue and scheduler operations remain deployment responsibilities.

Compose estimates, confirmations, wallet reserves, portal submissions, definitive reservation reversals, report views, settlement sync, debits, releases, and refunds use the existing SMS audit and immutable-ledger paths.

## Migration and storage safety

The SMS migration adds portal settings and dedicated `sms_*` tables. It does not remove or alter columns in the historical `bulk_sms_settings` table. Those legacy provider columns are inert because their controllers, jobs, models, routes, views, and runtime calls are removed; physical credential cleanup must be a separate reversible operation after staging. This avoids destructive production changes and the indexed-column failure previously observed with SQLite schema reconstruction. The forward-only `2026_07_05_000001_finalize_portal_sms_staging_contract` migration adds missing contract mirror fields, synchronization indexes, and reservation uniqueness, so environments that applied the original migration are hardened without column drops.

Portal allocation references, operation idempotency keys, wallet owner keys, batch references, message references, and settlement evidence keys have unique constraints. High-volume allocation sync, batch scheduling, report ownership, delivery-status, and billing-status paths are indexed. Runtime wallet changes additionally use row locks and immutable ledger entries.

## Final pre-runtime safety rules

- Portal estimate and allocation currencies must match the selected local wallet currency.
- Invalid, zero, or malformed portal estimates cannot reserve local funds.
- A portal `can_afford=false` estimate cannot create a local reservation.
- Portal cumulative debited and refunded amounts cannot decrease locally, and refunded value cannot exceed reported debited value.
- The core no longer treats an undocumented `billable_amount` field as debit evidence.
- Missing reservations, malformed settlement amounts, decreasing release evidence, or amounts beyond the reservation enter billing review without changing wallet balances.
- Message synchronization acknowledgements are split into at most 10,000 references, matching the audited portal validator. Completed batches remain eligible for scheduled retry until acknowledgement succeeds.
- Queue jobs convert unsuccessful synchronization results into retryable job failures and use bounded backoff. Ledger and allocation idempotency make those retries financially safe.

## Controlled staging verification checklist

1. Save the portal SMS base URL, client key, client secret, default route, and feature switches; confirm the secret is never rendered after save.
2. Use the connection diagnostic and verify both the normal wallet response and the authenticated-but-wallet-missing response.
3. Synchronize sender IDs and confirm only portal-approved values appear in compose; verify a stale local approval is still rejected by the portal.
4. Preview member, agent, administrator, and manual audiences at national and lower access levels. Attempt out-of-scope location identifiers and confirm they fail closed.
5. Verify Nigerian phone normalization, invalid counts, duplicate counts, and the ten-row-only confirmation preview with a large audience.
6. Estimate against personal and authorized organization wallets. Verify local insufficiency and portal-master insufficiency create no reservation.
7. Submit one immediate and one scheduled low-volume batch. Confirm a single local reservation, stable batch idempotency key, portal batch reference, and long-term recipient records.
8. Exercise a definitive portal rejection and an uncertain/unreachable response. The former must release the reservation; the latter must retain it for reconciliation.
9. Repeat allocation and settlement synchronization. Confirm no duplicate credits, debits, refunds, or releases and verify acknowledgement retry behavior.
10. Compare delivery, billing, refund, and billing-review labels against portal data, including failed and DND-blocked messages with no refund evidence.
11. Verify report ownership with two lower administrators and organization-wide visibility with an explicitly permitted top administrator.
12. Verify top-up initiation stores only a safe HTTP(S) payment URL, applies no immediate local credit, and credits exactly once after a confirmed allocation.

## Contract items to recheck during staging

The remaining checks are behavioral rather than missing contract fields:

- same-second identical idempotent mutations return the existing portal resource while conflicting payload reuse remains rejected;
- exact raw query bytes signed by the core match the bytes received by the portal;
- message acknowledgement continues to accept chunks of no more than 10,000 references;
- credit-request list/detail remain scoped to the authenticated API client;
- released, debited, and post-debit reversed/refunded aggregates reconcile to the portal immutable ledger;
- message refund eligibility and evidence reflect portal billing policy only.
