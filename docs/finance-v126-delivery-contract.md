# Finance v1.2.6 — implementation contract and gated delivery

Status: approved product requirements; implementation remains incomplete. This document is not evidence of passed tests or released software.

## Delivery sequence (say “next step” to advance)
1. Audit baseline, freeze the privilege and accounting contract, map acceptance tests. **Architecture checkpoint.**
2. Implement deny-by-default geographic RBAC, finance appointments, delegated leadership read/request privileges and independent action authorization; add attack/regression tests.
3. Build request workflow: event/project budgets, one or many beneficiary recipients, or unspecified branch-level allocation, draft/return/resubmit/escalate, configurable threshold approvers, reservations, evidence and status notifications.
4. Build source/beneficiary accounting: immutable double-entry journal, allocations, advances and settlements, balances, chart of accounts, period close, audit and statement reconciliation.
5. Implement campaign-owned Paystack onboarding, donation checkout and payment verification: source/beneficiary attribution, donor due diligence, refunds/reversals, signed event handling and idempotency. No production money movement until operator and merchant approvals.
6. Implement verified recipient onboarding, single/bulk disbursement and transfer state machine, payout approvals, reconciliation and recovery. Sandbox first; feature flags default OFF.
7. Complete operational reports, mobile/browser UI, migrations from 1.2.5, independent security review, CI on PHP 8.2/8.3 and MySQL/MariaDB, signed release candidate, fresh install and supported upgrade tests; only then merge into main, delete merged topic branches and publish v1.2.6 via official release workflow.

## Access-control contract
- Every campaign has a licensed root jurisdiction: national, state, senatorial, federal constituency, LGA or smaller supported scope. Root is not synonymous with national.
- Super Admin owns finance configuration and can select **Central finance only** (default) or **Delegated finance**.
- Central finance only: finance administration, account management, deposits, disbursements and approval authority remain with explicitly appointed root-office finance personnel. Super Admin may delegate *request-only* and *scoped read-only* access to lower leadership without delegating control of bank accounts or authorization.
- Delegated finance: Super Admin enables finance functions separately per access level, role/appointment, territory and action; a sublevel may never elevate itself or widen its scope. Root can revoke immediately.
- No implicit Finance access for ordinary members, polling-unit members, or coordinators merely by their campaign access level. Explicit grants required; absent rule means deny.
- Separate rights for section visibility, scoped dashboard, request creation, request submission for another unit, receipt/document reading, recipient maintenance, review, approval, execution, ledger posting, account management, settlement, reconciliation, policy editing and audit export.
- Effective access = active enterprise entitlement AND active user AND explicit finance capability AND policy grant for function AND jurisdiction intersection AND amount/approval constraints. Menu hiding is not authorization. Deny on API/service, query, export and download paths too.
- Leadership can receive scoped view/request grants irrespective of finance office. A ward chairperson may request for their ward only, subject to campaign root grant; they cannot approve their own requests, silently create recipients or transfer funds.
- For approval: sequential threshold stages, distinct human approvers, request creator not approver, recipient/payee-change maker not sole approver, executor not approver, reconciliation reviewer independent of posting. Record immutable approver policy snapshot, actor, territorial scope and timestamps.
- Approval rules distinguish **request**, **approval**, **payment authorization**, **execution**, **confirmation**, **reconciliation**. Financial authority is never inferred from a job title.

## Funding requests, branch operations and recipients
- Request contains requesting jurisdiction, benefiting jurisdiction, source paying account, purpose/project/event, budget/cost center, amount in minor units, due date, supporting evidence, requester, beneficiary list/route and idempotency key.
- Supports (a) single person/vendor, (b) multiple named recipients with per-recipient amounts summing exactly to the approved total, (c) transfer to a verified branch-controlled account with *no named recipients yet*. In (c), the receiving branch must later produce a sub-disbursement/expense and proof; funds are not immediately counted as final expense.
- Branches may ask an upstream office for funding; policy chooses the escalation chain and which office ultimately bears the cost. The root financial team can allocate a budget/advance rather than pay individuals directly.
- Record both payment-origin territory and credited beneficiary/benefiting territory separately from settlement destination. Aggregated reports roll up by chosen dimension with no duplicate totals.
- For multi-recipient requests, recipient changes after approval trigger reapproval if they alter amount or bank identity. No partial execution without explicit policy, item-level reconciliation, failure recovery and accurate unspent balance.
- A fund request may be returned, rejected, cancelled or re-submitted with an immutable decision history. Lock concurrent approvals and reservations. Every obligation has an audit trail.

## Payment gateway and banking safety
- Merchant account belongs to the campaign/eligible campaign entity, never to the software vendor. Root chooses one settlement account or expressly verified downstream accounts subject to provider eligibility and operator policy.
- Paystack account/subaccount/split functionality must not be misrepresented as unrestricted arbitrary inbound routing. Collect under campaign-owned merchant credentials and reconcile the actual provider settlement. A regional origin tag is accounting metadata, not proof that money settled in that region.
- Separate API secrets per merchant in encrypted storage; no credentials in repository, logs or exports. Test/live separation; SSRF-safe outbound URLs; retry timeouts and circuit breakers.
- Verify incoming notifications using current Paystack signature format and server-side transaction/transfer verification; enforce unique provider event, transaction, transfer and recipient references and replay protection.
- Payment states: initiated, pending, succeeded, failed, reversed/refunded, disputed. Only authenticated provider-confirmed successful collections post to cash ledger. Failed or pending transfers never count as completed expenses.
- Transfers require verified beneficiary account, explicit approved request, budget availability and separate authorized executor. Persist transfer intent before API call and reconcile ambiguous network failures by reference without resending blindly. Support OTP/pending, success, failed, reversed statuses.
- Reconcile Paystack transactions, payout balance, fees, settlements, bank statements and internal journal as distinct objects. Investigate variance instead of mutating posted entries. Immutable reversing entries for corrections.
- Default production collection and payout flags OFF until merchant activation, compliance policy, environment-specific credentials, sandbox tests and explicit root Super Admin activation.
- Paystack identifies political parties among supported registered non-profits, but campaign-specific acceptance, payout and cross-border eligibility must be confirmed with provider. Applicable donor identification, source-of-funds, political-finance limits and INEC reporting rules require legal review prior to live launch.

## Release acceptance criteria
- Test central-only vs delegated modes for each campaign root scope and every access level including ordinary member; request-only does not leak balances, documents, donor information or approval actions.
- Test cross-jurisdiction bypass, forged beneficiary, changed recipient account, double approval, concurrent budgets, retry/webhook duplication, missing webhook, failed transfer, reversal and refunds.
- Reconcile each historical v1.2.5 account through additive migrations; verify backup/restore, no lost users/uploads/entitlements; verify installer/update using signed official 1.2.5 artifacts.
- Finance-specific unit/feature tests, browser tests and full repository workflows green before merge. Candidate workflow must succeed and release archive signatures/checksums must be checked.
- No claim of publication until the official public release and updater feed are demonstrably live.

## External references, verified 2026-11-06
- Paystack single transfer reference / status / OTP: https://paystack.com/docs/api/transfer/
- Paystack transfer lifecycle: https://paystack.com/docs/transfers/single-transfers/
- Paystack webhook signing: https://paystack.com/docs/payments/webhooks/
- Paystack split payments: https://paystack.com/docs/payments/split-payments/
- Paystack supported businesses: https://support.paystack.com/en/articles/2129730
- Electoral Act 2026 campaign finance factsheet: https://placng.org/i/wp-content/uploads/2026/02/PLAC-Factsheet-on-Campaign-Spending-2026-2.pdf
