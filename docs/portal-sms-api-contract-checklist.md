# Portal SMS API contract audit

Audit date: 5 July 2026.

The core was compared directly with the portal source in the sibling `campaigmanager.ng` repository. The authoritative portal files were `routes/api.php`, `SmsPortalClientAuthService`, `AuthenticateSmsPortalClient`, and the API SMS controllers. No portal files were changed.

## Base URL and endpoints

The core SMS base URL must end with `/api/core/v1`. It is independent of the licensing base URL ending in `/api/v2`.

| Core operation | Method and audited portal path | Status |
|---|---|---|
| Estimate | `POST /api/core/v1/sms/estimate` | Verified |
| Create batch | `POST /api/core/v1/sms/batches` | Verified |
| List batches | `GET /api/core/v1/sms/batches` | Verified |
| Batch detail | `GET /api/core/v1/sms/batches/{batch_reference}` | Verified |
| Batch messages | `GET /api/core/v1/sms/batches/{batch_reference}/messages` | Verified |
| Acknowledge message sync | `POST /api/core/v1/sms/batches/{batch_reference}/messages/mark-synced` | Corrected from the previous unscoped path |
| Master wallet | `GET /api/core/v1/sms/wallet` | Verified |
| List sender IDs | `GET /api/core/v1/sms/sender-ids` | Verified |
| Request sender ID | `POST /api/core/v1/sms/sender-ids` | Verified |
| Initiate top-up | `POST /api/core/v1/sms/topups/initiate` | Verified |
| List allocations | `GET /api/core/v1/sms/allocations` | Verified |
| Allocation detail | `GET /api/core/v1/sms/allocations/{allocation_reference}` | Verified |
| Mark allocation synced | `POST /api/core/v1/sms/allocations/{allocation_reference}/mark-synced` | Verified |
| Create credit request | `POST /api/core/v1/sms/credit-requests` | Verified |
| List credit requests | `GET /api/core/v1/sms/credit-requests` | Verified and client-scoped |
| Credit request detail | `GET /api/core/v1/sms/credit-requests/{request_reference}` | Verified and client-scoped |

All paths live in `config/portal_sms.php`. If the portal changes a path, update that map rather than controllers or synchronization services.

## HMAC checklist

- Headers are exactly `X-CM-Client-Key`, `X-CM-Timestamp`, `X-CM-Signature`, and optional `X-CM-Idempotency-Key`.
- Timestamp is a decimal Unix epoch timestamp in seconds. Epoch time is timezone-independent.
- Portal clock-skew allowance is 300 seconds.
- Signature algorithm is HMAC-SHA256 with the decrypted client secret as the HMAC key; the secret is not base64-decoded.
- Canonical string is `timestamp`, uppercase method, request path, SHA-256 raw-body digest, and idempotency key joined by newline characters in that order.
- Request target includes `/api/core/v1/...` and the exact raw query string when present.
- GET body is empty; its canonical digest is the SHA-256 digest of an empty string.
- JSON is encoded once, then those exact transmitted bytes are hashed.
- Portal permits a duplicate signature only for safe reads, estimate, or whitelisted idempotent mutations carrying a business key. Same-second retries reuse the same key and payload; conflicting reuse remains rejected.

## Mutation idempotency

Batch creation, top-up initiation, and credit-request creation require `idempotency_key` in the JSON body and the same value in `X-CM-Idempotency-Key`. Sender-ID creation accepts the body key and validates it against the header when supplied. The core now supplies both.

The batch UUID-backed key is stored before submission. Top-up, credit-request, and sender-ID local mirrors retain their operation key before or during submission so an uncertain response can be reconciled without silently changing the key. Allocation and message-sync acknowledgements use deterministic keys.

## Request fields verified

- Estimate: `client_key`, `route`, `sender_id`, `message`, `recipients_count`; optional `idempotency_key`.
- Batch: `route`, `sender_id`, `message`, recipient objects containing `phone`, optional `name`, `local_reference`, and `metadata`; optional `source_context`, `scheduled_at`, and charge-allocation fields; required `idempotency_key`.
- Sender ID: `requested_sender_id`, `organization_name`, `purpose`, `route_preference`, optional `supporting_note` and `idempotency_key`.
- Top-up: `amount`, `currency`, `allocation_type`, optional core user/wallet references, payer fields, `return_url`, `metadata`, and required `idempotency_key`.
- Credit request: `allocation_type`, optional core user/wallet references, `requested_amount`, `currency`, optional `reason`, `urgency`, `metadata`, and required `idempotency_key`.
- Allocation acknowledgement has no required body fields.
- Message acknowledgement accepts `message_references`; the batch reference is a path parameter.
- The audited message acknowledgement validator accepts at most 10,000 references, so the core acknowledges large batches in deterministic chunks.

The portal ignores `core_identity` because its controllers use validated fields only. It remains signed context for installation traceability and does not affect portal business idempotency hashes.

## Response fields verified

- Wallet: `wallet_balance`, `available_balance`, `reserved_balance`, `currency`, and `active_route_prices` entries containing `route`, `retail_price_per_unit`, and `currency`.
- Estimate: top-level `total_units`, `estimated_total`, rate, encoding/page details, wallet balance, and `can_afford`.
- Sender ID: `id`, `requested_sender_id`, status, preference, notes, timestamps, and rejection reason.
- Top-up: `payment_session_reference`, `payment_reference`, status, amount, currency, payment URL, and payment instructions.
- Allocation: allocation/core references, amount/currency, status, `source_type`, `source_reference`, and confirmation/sync timestamps.
- Batch: `batch_reference`, status, `counts`, `estimated_total`, reserved/debited/refunded/reversed/released amounts, billing-review count, settlement status, currency, schedule/timestamps, and master-wallet charge audit fields.
- Message: `message_reference`, `phone`, name/local reference, delivery and billing statuses, nullable-compatible `refund_eligible`, `billing_review_required`, `refunded`, refund reason/evidence, units, `amount`, and event timestamps.

The core keeps `refund_eligible` nullable for backward compatibility but mirrors it when portal policy provides it. `released_amount` is pre-debit reservation release evidence; `refunded_amount`/`reversed_amount` are post-debit reversal evidence. Failed delivery never supplies either value.

## Error handling verified

Portal business endpoints use the `error` string for codes, while framework validation and authentication responses may provide only a message. The core now reads string `error`, `code`, or nested error code and safely normalizes 401/422 responses. The portal deliberately combines an invalid and inactive API client into the same authentication response, so that case maps to `unauthorized_api_client`; an explicitly inactive linked installation maps to `suspended_api_client`. User-facing mappings cover insufficient wallet, unapproved sender, route/provider configuration, duplicate key, invalid recipients/signature, validation failure, and network failure. Raw portal authentication, provider, and stack details are not returned to users.

## Safe diagnostic

“Test Portal SMS Connection” signs `GET /api/core/v1/sms/wallet`. If authentication succeeds but the portal reports that no master wallet exists, it safely confirms authentication through `GET /api/core/v1/sms/sender-ids` and records `connected_wallet_missing`. It validates local configuration, reads only, stores connection status and successful-sync time, and creates no batch, payment, allocation, credit, or wallet transaction.

## Remaining staging checks

No listed endpoint or settlement-field gap remains. Controlled staging must verify exact raw-query signing, same-second idempotent retry behavior, client scoping on credit-request reads, and ledger reconciliation of release versus post-debit reversal aggregates.
