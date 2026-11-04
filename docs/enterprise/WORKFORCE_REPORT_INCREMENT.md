# Workforce reporting and optional AI

Standard reports include scoped membership totals, active-account and registered-voter counts, state/LGA/ward/polling-unit distribution, hierarchical drilldowns, registration-date filters, polling-unit membership coverage, streaming CSV and paginated PDF exports. Date filters select member registration dates. Coverage means at least one matching member; it does not certify operational readiness.

Routes: enterprise.reports.workforce, enterprise.reports.workforce.export and enterprise.reports.workforce.pdf. Geographic filters accept state_id, lga_id, ward_id and polling_unit_id and only narrow authorized queries. Both view and exports apply LocationScopeService, intersecting the viewer's jurisdiction with the licensed installation scope. Export requires both reports.view and reports.export plus an active advanced_reports entitlement. Reports contain aggregates rather than member contact/bank details. CSV neutralizes spreadsheet formulas. PDF supports up to 5,000 locations per request to bound shared-hosting memory; narrow filters or use CSV for larger reports. Built-in PDF fonts transliterate unsupported characters; CSV retains UTF-8 names.

## AI configuration and cost control

Super Admin opens the AI settings link in Workforce reports, adds an OpenAI API key and model ID, sets per-user and installation-wide daily request/token limits, and enables AI. The provider key is encrypted using the installation application key, hidden from serialization, omitted from browser output and retained when the form key is blank. Changing an application's encryption key requires saving the provider credential again. Invalid stored credentials fail gracefully.

AI charges are paid directly to the operator's OpenAI account; there is no Campaign Manager AI wallet or credit sale in this implementation. Module licensing and provider charges remain separate. Standard reports, CSV and PDF continue working with AI disabled, provider unavailable, quota exhausted or no provider key.

Each invocation requires reports.ai.generate, reports.view, fresh online advanced_reports verification and explicit consent to share aggregate data. The server sends scoped counts and up to 50 geographic location labels, never member names, email, phone, bank details or member IDs. The fixed HTTPS OpenAI Responses endpoint uses TLS verification, no redirects, no retries, bounded connection/request timeouts, max_output_tokens and store=false. Generated text is escaped in the browser and must be reviewed against campaign records.

UTC daily counters are reserved atomically in InnoDB, locking installation quota before user quota. Input bytes plus instructions/framing overhead conservatively bound input tokens; maximum output tokens are added before the request. Reservations remain consumed after failures/timeouts because provider processing may already have happened. Provider usage is recorded separately. These limits bound requests/tokens, not a currency amount; model prices vary, and provider account spending limits remain advisable.

Audit rows contain user ID, model, safe lifecycle status, reserved/actual tokens and hashes. They exclude provider keys, request headers, source report content and generated analysis text. Settings changes are audited without secrets. Super Admin can review the latest 25 audit records in AI settings. No audit deletion endpoint is exposed, and rollback retains tables.

## Validation

Local PHP 8.4/SQLite report/AI/PDF tests: 17 passed, 108 assertions. This covers jurisdiction/licence isolation, date/group validation, separate export grants, entitlement rejection, CSV formula neutralization, PDF pagination/cross-reference offsets, encrypted write-only settings, aggregate input isolation, usage caps, provider failure sanitation, consent and output escaping. Provider tests use Http mocks and never incur charges. A two-page synthetic PDF passed Poppler extraction and visual rendering review.

Earlier reporting tests exposed and fixed duplicate middleware alias registration that discarded enterprise.feature. Full existing CI, supported PHP/MySQL/MariaDB validation, cross-module browser acceptance and production release gates remain required. Purchase availability, configured prices and production systems are unchanged by this increment.

