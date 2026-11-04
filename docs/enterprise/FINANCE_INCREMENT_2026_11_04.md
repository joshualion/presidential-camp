# Campaign Finance implementation increment

This is a reviewable operational increment, not a production-ready v1.2.5 release. Keep Portal purchase availability disabled until the paired release acceptance tests pass.

Implemented: scoped financial accounts, NGN income ledger, immutable transaction and audit history, budgets and utilization, payment requests, independent request approval and expenditure recording, private PDF/JPEG/PNG supporting documents, account summaries, dashboard and full CSV statements. No banking transfer is initiated.

Amounts are integer kobo. Account and budget writes run in database transactions using an account-first row-lock order. Posted requests cannot be posted twice; submissions have unique idempotency keys. Requesters cannot approve/reject their own requests, and approvers cannot execute their own approvals. The requester may record an approved request if independently permitted. Expenditures require exact approved amounts, available recorded funds and remaining budget allocation.

Existing Expense, FundingRequest and Transaction data are unchanged; those models lack the required enterprise ledger contract. New campaign_finance_* tables use InnoDB without foreign keys to legacy user/location tables, preserving compatibility with installations that retain MyISAM legacy tables. Geographic jurisdiction is captured from a permitted custodian at account creation, remains unchanged when the custodian moves, and every account lookup intersects user and installation scope using existing LocationScopeService. New permissions reuse the enterprise capability list; no role is automatically granted finance permissions.

Supporting files are saved on the existing private local disk, never the public disk. Downloads verify account scope, permission, entitlement and SHA-256, and force attachment/octet-stream delivery. File writes are removed when the document transaction fails. A process crash between disk write and DB commit can leave an unreferenced private file; periodic orphan cleanup remains a follow-up.

Validation: 30 targeted Finance tests, verified through full and targeted batches on SQLite and MariaDB cover independent permissions and licence checks, all administrative/user geographic boundaries and five licensed campaign scopes, inconsistent-member-location rejection, geographic parent derivation, immutable scope on custodian movement, HTTP approval permission/scope/self-approval protection, state isolation, income idempotency, approved expenditure/budget utilization, duplicate execution refusal, self-approval/approver execution refusal, atomic overbudget and insufficient-funds rejection, private documents and unsafe MIME rejection, dashboard rendering and CSV formula-injection protection.

Actual simultaneous MariaDB worker processes also pass competing account-fund exhaustion, competing budget-allocation exhaustion, and identical-submission idempotency scenarios (`php scripts/verify-finance-concurrency.php`). The script refuses non-testing or non-local databases and requires a database name ending `_test`.

Request, transaction, supporting-document and audit histories are paginated so older records remain accessible. Monthly income/expenditure summaries accompany lifetime balances and totals.

Remaining release gates: live browser responsive QA; complete installer/upgrade rehearsal and recovery; account correction/reversal workflow; configurable approval limits and document retention/orphan cleanup policies; final navigation and paired Portal purchase activation. The routes are functional after inclusion through routes/web.php but this branch does not enable sale or issue licences.
