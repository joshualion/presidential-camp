# Finance rebuild checkpoint

| Area | v1.2.5 baseline | Implemented in this change | Remaining work |
| --- | --- | --- | --- |
| Entitlement and geography | Signed Finance feature claim, capability checks and geographic account snapshots | Reuses the existing licence identifier and intersects all dashboard and account totals with the existing scope query | Independently attribute source and beneficiary jurisdictions and settlement accounts |
| Finance navigation | One account screen | Finance sidebar and dedicated sections with role and access-level visibility settings, using existing role capabilities for actions | More granular reporting controls and browser validation |
| Appointments | Campaign roles and explicit finance capabilities | Separately recorded, revocable, expiring Finance appointments can qualify a scoped user for a configured approval stage | Broader appointment-specific action policies and delegated approvals |
| Accounting | Immutable single-entry rows, account locks and integer balances | Linked paired journal postings, historical continuity check, full reversals by an independent officer, audit and CSV debit/credit codes | Configurable chart, period closing, general adjustment journals, clearing and bank statement reconciliation |
| Funding | Requests, one independent approver, manual expenditure posting | Threshold-selected approval snapshots, distinct actors, final-approval reservations for configured workflows and requester cancellation | Drafts, returned requests, notifications, exceptions, escalation and advanced routing |
| Payments | Manual records | Records remain manual | Donor compliance records, campaign merchant onboarding, collections, bank account verification, batch disbursements, verified transfers, refunds and reversals |
| Upgrade | Signed in-app updater | Three additive migrations are reviewed in the updater migration allowlist | Candidate packaging and official v1.2.5 upgrade testing before publication |

## Preserving existing records

The migrations create new tables without editing any of the v1.2.5 Finance tables. Historical records are compared with the recorded balance when an officer runs **Check historical balance** in Reports & Audit or makes the next posting. The check is per account, runs under the account lock and retains every original transaction. A discrepancy rolls back the entire check and blocks new postings for the account. Use the existing statement and source records to investigate an unexplained balance.

Existing requests and new requests created before an approval chain is configured follow the original single-stage flow. Requests created with a chain retain the policy version and ordered stages. Final approval reserves funds and budget against configured requests, cancellation releases the reservation and posting consumes it. Amounts and balances on the dashboard come from internal records.

Finance page visibility and write capabilities are independent. Review **Roles & Permissions** when granting a coordinator read-only access. Grant **finance.documents.view** explicitly to roles that need to view and download supporting documents. Finance appointments do not extend the member's ordinary geographic access.

## Payment prerequisites

The 2026 INEC regulations cover source-of-funds records, donor identification and audited returns for covered campaign organizations. Before accepting live donations, confirm the applicable entity, election, donor restrictions, contribution limits and reporting process. Obtain Paystack approval for the campaign-owned merchant and proposed collections or transfers, then validate payment verification and reconciliation in test mode. This checkpoint has no provider money movement.

- [INEC 2026 Regulations and Guidelines, Part 5](https://inecnigeria.org/wp-content/uploads/Regulations-and-Guidelines-2026_UPDATED.pdf)
- [Paystack terms](https://paystack.com/terms)
- [Paystack merchant activation](https://support.paystack.com/en/articles/2125506)
