# Finance v1.2.6 — Work Mode implementation handoff
Last checked: 2026-11-06 18:53 Africa/Lagos. This is an engineering continuation contract, not an assertion of test success.

## Entry point and repository ownership
- Core: `joshualion/presidential-camp`; PR **#24**, `feat/finance-v126-foundation` → `main`.
- Current development HEAD **082a6d20a53766fe6a2c5792dff9f74881149cfe** at handoff creation (this file's own creation creates a newer HEAD; always resolve HEAD first).
- Portal: `joshualion/campaigmanager.ng`; public distribution: `joshualion/Campaign-Manager`.
- Campaign Manager v1.2.5 is last published; **do not merge, delete branches or release v1.2.6 until ALL work and testing are complete**.
- User prefers one `main` branch after merge (delete merged feature branches), cPanel-friendly production upgrade without Composer, protected customer data and official signed updates.
- Check `docs/finance-v126-delivery-contract.md` and `docs/finance-rebuild.md`.

## Actual code completed so far (read code; do not assume validated)
1. Geographic Finance accounts, journal pairs, reversals, requests, budgets, approvals, expenses, internal transfers/advances and entitlement checks already had an earlier foundation.
2. Central/delegated Finance governance: `campaign_finance_governance`, `CampaignFinancePolicy`, finance settings UI, granular role and level privileges, request-only leadership, deny-by-default ordinary users.
3. Upstream proposals: `campaign_finance_funding_proposals`, `CampaignFundingProposalService`, `campaign_finance_proposal_locations`, `campaign_finance_proposal_controls`, `campaign_finance_proposal_events`. Requester submits from branch without HQ account access; reviewers return/reject, requester resubmits, authorized officer assigns source account / budget / branch destination; creates a distinct pending standard Finance request requiring independently configured approval chain; requesting location and recipients retained; allocation officer cannot approve own assignment. Requester/reviewer UI and event history.
4. Stage 4 **only partially started**: `CampaignFinanceStatementService`, `campaign_finance_statement_checks` and forms for supplied statement balance vs ledger comparison, independent reviewer, immutable check/audit, no automatic balance mutation. **Not yet** full financial period close, external verified reconciliation, statements ingestion, complete accounting safeguards.
5. New migrations `2026_11_06_000015` through `000019` must retain reviewed SHA256 entries and created-table manifests in `config/updates.php`. Verify every migration hash after any edit. Additive/idempotent only; preserve financial records.
6. Tests added to `tests/Feature/CampaignFinanceTest.php` for finance governance, proposals, multi-recipients, geographic isolation, statement checks, audits. **New tests are not yet CI-verified.**
7. Paystack test/live mode **not implemented yet**. Earlier discussions are requirements only.

## CI investigation — BLOCKER; do this FIRST
- Repository was switched to **public** by the user. As of 2026-11-06 18:53 Africa/Lagos, runs remained queued with zero jobs executed:
  - Application run https://github.com/joshualion/presidential-camp/actions/runs/42257596688
  - DB backup run https://github.com/joshualion/presidential-camp/actions/runs/42257596833
  - Release-candidate run https://github.com/joshualion/presidential-camp/actions/runs/42257596905
- Earlier Step 2 commit `11c0eb5d4b9f15e7b3f97f3131c9321a7fddd94d` passed all application jobs (PHP 8.2/8.3, MySQL/MariaDB, browser) and six DB-backup matrix jobs while public. This is **NOT evidence** that Steps 3/4 work.
- Diagnose via GitHub website UI/settings: actions allowed, enabled workflows, billing/usage eligibility and spending limits, GitHub-hosted runner availability, organization policies, workflow concurrency, environment approval gates, workflow events, queue and run details. Do not assert a cause without evidence. Avoid triggering more redundant workflows until queue cause known; inspect tests locally/work computer first. Fix code/test errors revealed by execution and rerun on current HEAD.
- Public repo exposes code; do not paste secrets or sign with public/placeholder keys. User controls making repo private afterward; handle public-release/signed artifact secrecy carefully.
- Gate signed update preparation and distribution on passing real checks, migrations, PHP versions, MySQL/MariaDB, browser tests, and new feature tests.

## Remaining implementation (complete end-to-end in stages)
### Finish Stage 3 verification and review
- Validate maker/checker, return/resubmit/assign, immutable beneficiaries, geographic access, branch transfer vs direct expense, threshold approval stages, reservations and cancellation, concurrency, lack of accidental bank payouts.
- Fix *actual* bugs, do not weaken security or delete failing tests.

### Stage 4 — accounting and closing
- Complete double-entry integrity checks, general ledger, source vs beneficiary geography, draft/approval accounting, reconciliation to external evidence, variance resolution, accounting-period close/reopen with privileged documented reasons, consistent budgeting/reservations and audit.
- Check potential edge cases in supplied-statement snapshots, currency precision, past-dated transactions and concurrent writes. No silent balance adjustments. Add full tests and UI.

### Stage 5 — Paystack and gateway abstraction
- Gateway provider/adapter interfaces so Flutterwave can later be added without changing finance ledger/request core; only Paystack implemented and tested now.
- **Persistent and separate** encrypted Paystack test public+secret and live public+secret keys; switching modes does not erase either set. Blank edit fields must never clear existing keys; explicit rotation/revocation only; never log/show decrypted secrets. Super Admin alone can select mode, manage keys, test connections and authorize live operation.
- Environment isolation per transaction and verified webhook; independent credentials/event routes for late test/live events. Secure signatures, provider-side verification, idempotency, refunds/reversals, audit, origin/donor attribution, status states, failure handling, replay prevention.
- Test mode full functionality, live mode separate explicit acknowledgement/enablement; don't initiate real transactions without user authorization. Avoid assumptions about specific provider eligibility; reflect actual errors.

### Stage 6 — payouts and beneficiaries
- Verified recipient setup and identity, individual and batch transfer planning, beneficiary allocations, independent approval/execute, transaction retries/idempotency, provider reference checks, reconciliation, partial failure/refund/reversal, branch transfers, audit and permission/geographic visibility.
- Test/live isolation as above, never mark paid until externally confirmed.

### Final stage — signed release and deployment
- Finish tests and CI, check migrations/upgrade safety from v1.2.5, build signed artifacts using secure existing release pipeline, validate public distribution, update portal for entitlement/pricing where necessary, merge PR to main, remove merged topic branch, release official v1.2.6 and document cPanel in-app upgrade steps. Do not publish before all gates. User expects clear evidence, links and status, not statements of success without verification.

## Specific user decisions
- Super Admin may choose central HQ Finance only, or explicitly delegate capabilities to leadership/finance officers per access level and geography; leaders can be read-only and/or request-only; no finance to ordinary members by default.
- Branch/ward coordinator may request one or multiple payees or general branch account allocation. Routing HQ bank money is not the same as revealing HQ accounts to requesters.
- Financial records always retain geographic origin and receiving beneficiary location separately, including review chain.
- Paystack switching must keep **four stored credentials** forever until deliberately changed; both test and live should work, foundation for future Flutterwave.
- Keep this Finance initiative focused; do not independently deploy unrelated products.

## Completion reporting contract
Provide results by repository/branch/commit, exact test commands and CI links, passing/failing counts, high-severity outstanding defects, schema changes, security review, environment readiness, signed artifact verification and whether release is published. Never imply background progress or claim completion before verified.


## 2026-11-07 release completion pass

The preceding candidate at bd0a791 passed all application jobs, six MySQL/MariaDB backup jobs and the official signing/fresh-install/upgrade rehearsal. The follow-up adds independent review and posting for an existing single-payment live Paystack settlement. Read-only provider calls must confirm the receipt and its membership in the matched settlement. A reviewed bank CSV must have an unchanged journal snapshot and an unposted variance equal to the net credit. Gross income and processing fees post atomically and idempotently. Evidence references are encrypted, geographic restrictions apply, and donor totals include declared off-platform contributions.

Live payment initiation stays disabled. Sandbox collection and disbursement tests use synthetic details. Merchant eligibility and donor evidence require the customer’s authorized compliance review. Multi-payment settlements and refund adjustments use separate reconciliation and must not be booked through the single-payment donation shortcut. The release notes describe these boundaries.

Run the full quality and private candidate workflows for the follow-up commit before merging and publishing through community-release.yml. The publisher rechecks signatures, backups, fresh installs and supported upgrades, including 1.2.5, before creating the public 1.2.6 release. Do not replace 1.2.5 assets.
