# Workforce reporting increment

This adds standard workforce summaries, state/LGA/ward/polling-unit distribution, registration-date and geographic filters, polling-unit membership coverage and streaming CSV exports. Both endpoints require an active advanced_reports entitlement; CSV additionally requires reports.export and reports.view. All member and polling-unit queries apply LocationScopeService, which intersects the viewer's jurisdiction with the licensed installation scope. Exports contain aggregate counts, not member contact or bank details.

Routes: enterprise.reports.workforce and enterprise.reports.workforce.export. Geographic filters accept state_id, lga_id, ward_id and polling_unit_id and only narrow already authorized queries.

This increment does not make the Reports & AI module commercially complete: AI, PDF, election/incident report integration and release acceptance remain outstanding. Purchase availability and pricing are unchanged. AI is explicitly unavailable on this screen. No production data, payments or external AI requests are used by the added tests.

Validation: automated regression tests are provided in EnterpriseWorkforceReportTest.php for jurisdiction/licence intersections, filters, separate export permission, entitlement denial, dashboard rendering, validation and spreadsheet formula neutralization. Local reporting tests passed: 6 tests, 34 assertions on PHP 8.4/SQLite. This run exposed and fixed the pre-existing duplicate middleware alias registration that discarded enterprise.feature. Existing Application checks CI remains required for supported PHP/database/browser validation.
