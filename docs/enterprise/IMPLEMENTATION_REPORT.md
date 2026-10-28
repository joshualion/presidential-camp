# Enterprise expansion implementation report — 28 October 2026

> Historical foundation snapshot: the statements below describe the initial draft, not current merge, release or deployment status. See [the dated status addendum](STATUS_2026_10_28.md) for subsequent validation, shipped scope and remaining work.

**Status: PARTIAL IMPLEMENTATION. Draft foundation increment; not a release candidate.**

The full master assignment is not complete. This increment implements a reviewable entitlement boundary, explicit capabilities, Super Admin catalogue, existing-panel pricing configuration, and shared theme corrections. Reports, AI billing, Finance, donations, Merchandise, purchase orchestration and provisioning still need implementation. Do not merge or deploy this increment as a finished enterprise release.

## Repository baselines and scope

| Application | Baseline | Temporary development branch | Related issues |
| --- | --- | --- | --- |
| Core | `028d11c5cb4a6e8f33763798f289d544232d9c27` | `enterprise/installation-entitlements-20261028` | Core #12, #8 |
| Portal | `bbb6b7ced95acc20d3d5d620ed50064e29744964` | `enterprise/installation-entitlements-20261028` | Portal #27, #23 |

The original main branches, public distribution repository, production systems, repository visibility and protection settings are unchanged. Prior UI fixes are extended. Both contact-confirmation implementations were already present at these baselines; their existing tests were run, rather than creating a parallel flow. The issues remain open pending owner review.

No dependency manifests or lockfiles are changed. Composer/npm installations and databases were local test infrastructure only. Generated build outputs are excluded from the proposed source changes; the existing packaging/build process must regenerate production assets.

## Completion matrix

“Partial” means the complete backend/UI/payment/provisioning chain is not available.

| Workstream | Backend | UI | Migration | Authorization | Integration | Validation / outstanding work |
| --- | --- | --- | --- | --- | --- | --- |
| Shared theme corrections | Shared validated hex and WCAG foreground helpers | Buttons, sidebar, primary text, active/dropdown states | Not needed | Unchanged | Existing AdminLTE/Bootstrap | Scoped browser checks pass; full screen/modal/loading/widget audit remains |
| Enterprise foundation | Signed issuance, payment recheck, cache, middleware | Super Admin locked catalogue | Entitlement/event tables; permission registration | Explicit grants plus signed entitlement | Existing installation credentials | Partial: no purchase creation, activation/provisioning, renewal UI or history workflow |
| Services & Pricing | Existing controller extended | Same form, separate per-feature scope prices and duration | Disabled catalogue rows | Existing Super Admin protection | Same offering/price tables | Configurable prices and invalid-price rejection tested; checkout still disabled |
| Standard Reports / PDF | Not implemented | Not implemented | Not implemented | Capability names only | Not implemented | Complete scoped report generation, history and export still required |
| AI gateway / prepaid wallet | Not implemented | Not implemented | Not implemented | Capability names / online gate primitive only | Not implemented | Reservation, settlement, reconciliation, limits, encrypted provider settings and mocks still required |
| Campaign Finance | Not implemented | Not implemented | Not implemented | Capability names only | Not implemented | Ledger, budgets, accounts, evidence, approval limits, separation of duties and adapters outstanding |
| Donations | Not implemented | Not implemented | Not implemented | Capability name only | Not implemented | Legal/provider verification and disabled-by-default fundraising workflow outstanding |
| Merchandise / inventory | Not implemented | Not implemented | Not implemented | Capability names only | Not implemented | Storefront, orders, stock reservations, transfers, audit and reconciliation outstanding |
| Verification return | Existing baseline retained | Existing baseline retained | No change | Existing installation/session protection | Existing paired protocol | Relevant Core and Portal regression tests pass |
| Upgrader safety | Existing updater retained | Existing updater retained | New migrations additive | Existing update coverage retained | No updater replacement | Populated Core MyISAM compatibility test passes; direct old-version-to-new-release upgrades NOT established |

No operational premium menu shortcuts are added for ordinary administrators because the corresponding working modules do not yet exist. The Add-ons menu is Super Admin only and sits beside existing administrative settings/update navigation. No purchase link is shown for an unavailable checkout.

## Main implementation files

Core: `app/Services/Enterprise/{SignedEntitlement,EntitlementService}.php`, `app/Http/Middleware/EnsureEnterpriseFeature.php`, `app/Http/Controllers/EnterpriseAddonController.php`, `config/enterprise.php`, `app/Services/CampaignCapabilityService.php`, `app/Services/DeploymentRegistrationService.php`, `app/Support/ThemeColor.php`, shared Blade header/sidebar/catalogue, `campaign-ui.css`, routes and quality workflow.

Portal: `app/Services/EnterpriseEntitlementService.php`, `app/Http/Controllers/API/EnterpriseEntitlementController.php`, `config/enterprise.php`, existing `FrontendManagementController`, existing Services & Pricing view and its new enterprise partial, API route, migration, tests and quality workflow.

## Database changes

1. Core `2026_10_28_000001_register_enterprise_capabilities.php` registers enterprise capabilities in the existing Spatie permission engine. It does not assign financial access to geographic ranks, rename roles, delete permissions or change member records.
2. Portal `2026_10_28_000001_add_enterprise_entitlement_foundation.php` creates installation-bound entitlement and event tables, with unique purchase references/idempotency keys and restrictive foreign keys. It seeds three disabled offerings and five unpriced, disabled scope rows per offering in the existing catalogue.
3. Repeating the Portal migration preserves operator prices. The rollback methods deliberately retain data; an operator-reviewed restoration plan is required for schema removal.
4. The event table is a schema foundation. A complete append-only lifecycle/audit service is still outstanding. No writable entitlement admin endpoint or callback activation hook is exposed in this increment.
5. No production migration was executed. Local test databases contained synthetic fixtures only.

## Core–Portal API contract

`GET /api/v2/campaign-manager/deployments/{uuid}/enterprise-entitlements`

- Uses the existing deployment bearer token, hashed at rest in Portal. Core keeps credential handling inside `DeploymentRegistrationService`; tokens never enter links or Blade output.
- Requires matching installation authentication, confirmed contact and no pending domain change.
- Portal is disabled by default via `ENTERPRISE_ENTITLEMENTS_ENABLED=false`.
- Success: JSON `{ "payload": "<base64 JSON bytes>", "signature": "<base64 RSA-SHA256 signature>" }`.
- Signed JSON fields: protocol (1), installation_id, domain, issued_at, refresh_after, valid_until, features and catalogue.
- Feature map entries contain strict active=true, integer starts_at and nullable integer expires_at. Credits, update coverage and unrelated licences never substitute for a feature.
- Catalogue prices expose scope, decimal amount string, currency, billing label and duration. Every catalogue entry currently has purchasable=false.
- 401: authentication mismatch. 403: contact/binding prerequisites. 503: disabled service or unavailable signing configuration. Responses include no-store.
- This is a read/status API. There is no browser-return activation, checkout or AI invocation endpoint.

Use a dedicated RSA key of at least 2048 bits, separate from release-signing keys. Portal loads only an encrypted PEM from `ENTERPRISE_ENTITLEMENT_SIGNING_KEY_ENCRYPTED` and decrypts it with its application key. Encrypt the PEM using Laravel Crypt in an approved secret-management workflow; do not paste plaintext keys into source, tickets or logs. Core receives only the independently trusted public PEM via `ENTERPRISE_ENTITLEMENT_PUBLIC_KEY`. Key provisioning/rotation has NOT been performed on production.

## Security and offline policy

- Core verifies the exact signed bytes, RSA key type/strength, protocol, installation, current HTTPS domain, strict types and maximum lifetime before caching.
- Cache is encrypted and atomically replaced in private storage. Refresh writes are serialized by a filesystem lock.
- Normal refresh interval: 15 minutes. Transport/5xx outages can preserve previously signed grants until the earlier of their individual expiry or the signed 24-hour validity limit.
- Offline grace does not extend licence expiry. Explicit authentication rejection or invalid signature/response removes cached authorization. Domain changes and registration revocation invalidate identity.
- Operations requiring fresh online authorization must use `active(feature, true)`. New purchasing and AI paths do not exist yet and must use online checks when implemented.
- This is bounded offline revocation latency, not instant offline revocation. The owner must review that policy before release.
- Enterprise capability checks reuse existing roles, direct grants, individual restrictions and inactive-account handling. Geographic administrator rank supplies no automatic enterprise grant.
- Future module controllers/jobs/exports MUST additionally use the existing LocationScopeService. The new middleware does not itself scope datasets, and no new reporting/financial dataset endpoints are exposed.
- A manually marked active entitlement cannot pass signing without a matching order, installation/customer identity, commercial snapshot, correct currency/amount and a reconciled successful/manual-confirmed payment ledger. Refund/revocation removes it from subsequent issuance.
- No real payment, transfer, donation or external AI processing occurred.

## Actual validation

All tests used local synthetic data and mocked external HTTP/mail.

| Check | Result |
| --- | --- |
| Core new entitlement/signature/theme/capability/offline/render tests | 39 passed, 8,468 assertions |
| Existing Core capability, verification-return, jurisdiction and permissions UI regression batch | 125 passed, 994 assertions; one MyISAM-specific test initially skipped on SQLite |
| Core populated MyISAM migration test, rerun on MariaDB 10.11 | 1 passed, 12 assertions |
| Portal new entitlement/pricing/payment-recheck tests plus existing confirmation/registration tests | 17 passed, 98 assertions |
| Rendered Blade catalogue and source CSS in Chromium | 20 scenarios; 720 semantic-button contrast checks >=4.5:1 |
| Core and Portal frontend builds | Both passed |
| Changed PHP native syntax checks | Passed; rerun before commit |
| Blade cache compilation | Both applications passed |
| Full GitHub CI / full application suites | Not executed |
| Complete paid purchase-to-provisioning flow | Not implemented / not tested |
| Direct upgrades from published older installations | Not tested |

Representative commands (run in each appropriate checkout with the local PHP 8.3 runtime):

```bash
composer install --prefer-dist --no-interaction --no-scripts
php artisan test --filter='EnterpriseFoundation|EnterpriseOfflineEntitlement|SignedEnterpriseEntitlement|ThemeColor' --compact
php artisan test --filter='ManagedCampaignCapabilities|ContactConfirmationReturn|V120MigrationCompatibility|LicensedScopeQuery|IntegratedPermissionsUi' --compact
# MariaDB isolated Core test database:
php artisan test --filter=V120MigrationCompatibility --compact
# MariaDB isolated Portal test database:
php artisan test --filter='EnterpriseEntitlementFoundation|ContactConfirmationReturn|DeploymentRegistration' --compact
php artisan view:cache
php artisan view:clear
npm ci --no-audit --no-fund
npm run build
PLAYWRIGHT_MODULE=/path/to/playwright/index.mjs node tests/Browser/enterprise-foundation.mjs
```

The first Portal attempt with SQLite failed on the pre-existing MySQL-only MODIFY migration; no historical migration was changed. Local database restarts also required fresh synthetic data directories because stale tablespaces were not reusable in this workspace. The final clean-database Portal run passed. Browser checks use application-rendered Blade and source CSS, strip application scripts, and add synthetic semantic button fixtures; they are not end-to-end navigation/payment tests. Five brand colours, two widths and light/dark modes are covered. Full-screen audit, dropdown interactions, modals, DataTables, loading states, live account navigation and end-to-end workflows remain outstanding.

The standard browser download initially failed. A local Chromium binary was recovered from a development-only package; it is not an application dependency.

## CI preparation

Core quality checks now lint migrations, include foundation tests in the existing MySQL/MariaDB matrix, export rendered catalogue fixtures, and run the new contrast checks using the existing browser job. Portal quality checks now lint PHP/migrations/tests before its existing full suite. Production workflow guards are unchanged.

Commits use `[skip ci]` to honor the owner's request to review development before comprehensive GitHub CI. No workflow was manually dispatched. Required checks may remain pending until the owner authorizes CI.

## Remaining implementation sequence

1. Complete installation-bound quote/checkout and immutable commercial snapshots through existing payment methods; idempotent verified activation, refunds/revocation/renewals, audited lifecycle, recoverable versioned provisioning and customer history.
2. Implement non-AI reports using actual scoped records, periods, filters, history/templates and professional PDF exports; add normal permission-aware navigation after Elections only when provisioned and entitled.
3. Add Portal AI settings, wallet/reservations/settlements, reconciliation, per-user allocations/limits, privacy disclosure, mocked provider gateway and editable AI report drafts. Keep standard reports independent of credits.
4. Implement Finance records, balanced accounting, budgets, bank-data controls, limits and separation of duties; keep live adapters disabled.
5. Verify current Nigerian electoral/financial rules and provider eligibility, then implement configurable disabled-by-default donations. No legal conclusion or provider approval is claimed.
6. Implement Merchandise public catalogue/order/payment lifecycle, stock reservations and distribution; add authorized operational navigation and controlled Finance interfaces.
7. Complete broader customer catalogue/checkout/history polish without unrelated Portal cleanup. Legacy public prices remain split between frontend packages and service rows; broader consolidation remains a follow-up.
8. Run full SQLite/MySQL/MariaDB, application-browser, payment idempotency/concurrency, cross-repository and direct-upgrade fixtures; then finalize packaging, backup/recovery and deployment procedures.

## Deployment / upgrade / rollback handover

This increment must not be deployed as the requested finished release. Exact production deployment instructions and a new release version are not finalized. Existing production paths, secrets and updater state were not accessed.

Before any future deployment: finish the outstanding modules, obtain owner approval, pass comprehensive CI/security review, verify a restorable application/database backup, rehearse the additive migrations against populated copies, and validate the paired Portal/Core contract. Retain the current protected Portal deployment workflow. Core dependencies and frontend assets must be built into the existing signed release package; no new server-side Composer requirement is introduced.

Use only the normal reviewed additive `php artisan migrate --force` upgrade path and the existing signed updater/backup/recovery process. Do not run destructive schema resets against installations. Do not run automatic migration rollback for these retained-data migrations. Disable enterprise availability and restore the prior code plus a verified backup only through an owner-reviewed recovery plan. Existing Community services must remain available.

## READY FOR OWNER REVIEW

- Review the paired draft PRs, this honest completion matrix, the signing/offline policy, new permissions and scoped theme changes.
- This increment is ready for owner review and an authorized broader CI pass; the full enterprise assignment is not complete.
- Before merge: review actual diffs, complete cross-application/security validation and require passing authorized checks. Do not close the foundation issues as complete.
- Before Portal deployment: complete checkout/lifecycle/provisioning/customer UI and validate backup/rollback plus dedicated encrypted signing-key configuration.
- Before public Core release: complete modules, normal user navigation, full testing, packaged dependencies/assets, manifest signing and upgrade rehearsals.
- Direct upgrades from supported older published releases are not yet proven by these tests.
