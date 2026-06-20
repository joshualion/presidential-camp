# Phase 2A+ Self-Hosted Installer Integration Smoke Checklist

## Portal Preparation
- Create a Campaign Manager order with `deployment_mode=self_hosted`.
- Complete payment through Paystack or mark manual bank transfer as fully confirmed.
- Approve the order from the portal admin workflow.
- Confirm a license entitlement exists for the approved order.
- Call or inspect `POST /api/v2/licenses/entitlement` with the license key.
- Expected: package type, deployment mode, scope, modules, limits, and maintenance/update fields are returned.
- Copy the license key for the core app installer.

## Core App Fresh Install
- Set `DEPLOYMENT_MODE=self_hosted`.
- Set `CAMPAIGN_PORTAL_API_BASE=https://campaignmanager.ng/api/v2` or the target portal base URL.
- Set `SESSION_DRIVER=file`, `CACHE_STORE=file`, and `QUEUE_CONNECTION=sync` during first install, or confirm the Environment step writes these safe defaults before continuing.
- Remove `storage/app/installed.lock`.
- Use a fresh database or a database without an existing completed installation.
- Visit `/install` through the Laravel public entry point, for example `http://local-domain.test/install` where the virtual host document root points to `public`.
- Recommended local setup: create a virtual host whose document root is the app's `public` directory. Do not smoke test by exposing the full Laravel source directory as the web root.
- Pass requirement checks.
- Enter environment details.
- Enter and test database details before saving.
- Use the Previous button from Database back to Environment, then continue forward again and confirm saved values remain available.
- Enter optional mail settings.
- Confirm storage status.
- Preview the license.
- Expected: `/licenses/entitlement` is called first and no activation is consumed.
- Confirm the step progress UI marks completed/current/pending states and only completed safe steps are clickable.
- Create the first superadmin.
- Confirm the Run Installation step shows prepared location CSV preflight status from `storage/app/imports/inec/prepared/all`.
- Expected: normal customers do not need to run `php artisan campaign:provision-locations`; the browser installer provisions licensed location data.
- Run installation.
- Expected: migrations run, installer-safe seed data is applied, `/licenses/activate` is called, local license tables are populated, licensed location data is provisioned, superadmin is created, and `installed.lock` is written last.
- Expected: Complete step shows package type, deployment mode, licensed scope, imported/updated counts for regions, states, senatorial districts, federal constituencies, LGAs, wards, polling units, and any warnings.
- Expected: final run does not fail with "Environment settings are missing" if required environment values are present in `.env`.
- Log in as the superadmin.
- Visit `/admin/license`.
- Expected: local license details display with a masked license key, package/scope helper display, dashboard label, resolved boundaries, maintenance/update dates, and enabled modules.
- Confirm `/install` redirects away after installation is complete while `/install/complete` can show the completion message.

## Retry And Failure Scenarios
- Submit an invalid license key.
- Expected: preview fails cleanly without running migrations or writing `installed.lock`.
- Submit a managed license key to the self-hosted installer.
- Expected: portal rejects activation/eligibility and the installer remains retryable.
- Force a local failure after remote activation in a non-production test environment, then retry with the same app URL, domain, and server fingerprint.
- Expected: portal returns the existing activation payload and the installer can continue.
- Activate the same license from a different domain or server fingerprint after activation limit is reached.
- Expected: portal rejects with activation limit reached.
- Run `php artisan campaign:installer-reset --force`.
- Expected: only `storage/app/installed.lock` is removed.
- Visit `/install` after `installed.lock` exists.
- Expected: installer redirects away and cannot rerun.
- Run `php artisan campaign:installer-status`.
- Expected: non-sensitive installer diagnostics show APP_URL, APP_KEY present/missing, SESSION_DRIVER, CACHE_STORE, QUEUE_CONNECTION, `.env` readability, deployment mode, installer enabled state, DB configuration/reachability, lock state, license table presence, storage writability, and activation domain.
- Run `php artisan campaign:scope-status`.
- Expected: package type, deployment mode, scope type/name, boundary IDs/names, enabled modules, fallback status, and local license table status are shown without exposing the full license key.

## Scenario A: Managed Mode
- Set `DEPLOYMENT_MODE=managed`.
- Remove or ignore `storage/app/installed.lock`.
- Visit `/login` and `/`.
- Expected: no redirect to `/install`; existing managed behavior is preserved.
- Visit `/install`.
- Expected: installer is blocked or redirected away.

## Scenario B: Self-Hosted Redirect
- Set `DEPLOYMENT_MODE=self_hosted`.
- Ensure `storage/app/installed.lock` does not exist.
- Visit `/login` or `/`.
- Expected: normal web routes redirect to `/install`.

## Scenario C: Requirements Without Database
- Temporarily set invalid database credentials or point to a missing database.
- Visit `/install/requirements`.
- Expected: installer requirements page loads without a database crash.

## Scenario D: Database Test Before Save
- Submit invalid database credentials on `/install/database`.
- Expected: clean validation error; `.env` is not permanently updated with failing DB credentials by this step.
- Submit valid database credentials.
- Expected: success message and continuation to mail setup; valid DB settings are written to `.env` so final run can recover if the browser session changes.

## Scenario D2: Installer Navigation
- Complete Requirements and Environment.
- Click a pending future step such as License before Database is complete.
- Expected: redirect to the first incomplete step with a clear message.
- Use Previous from each step: Requirements to Welcome, Environment to Requirements, Database to Environment, Mail to Database, Storage to Mail, License to Storage, Admin to License, Run Installation to Admin.
- Expected: completed values remain available where safe; passwords may need to be re-entered.

## Scenario D3: Environment Session Fallback
- Complete Environment and Database so `.env` contains the required values.
- Simulate a lost environment session key in a local test session while keeping `.env` intact.
- Continue to Run Installation.
- Expected: final run reconstructs `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`, and `DEPLOYMENT_MODE` from `.env` or config and does not fail only because `installer.environment` is missing.

## Scenario D4: Database Session Fallback
- Complete Database with valid credentials so `.env` contains `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
- Simulate a lost database session key in a local test session while keeping `.env` intact.
- Continue to Run Installation.
- Expected: final run tests the current `.env` database connection and continues if reachable.

## Scenario D5: Failed Step Recovery
- Remove or corrupt required environment values in `.env` and clear the environment session key in a local test session.
- Submit Run Installation.
- Expected: error identifies Environment Setup and shows a Back to Environment button.
- Repeat for missing database values and missing admin session password.
- Expected: Database errors show Back to Database; admin session expiry shows Back to Super Admin and explains the password is kept only in session.

## Scenario E: License Preview
- Submit a valid license key on `/install/license`.
- Expected: installer calls `/licenses/entitlement`, falls back to `/licenses/check` if needed, and displays package/scope/module details.
- Expected: no remote activation is consumed during this step.
- If the license preview session is lost but the installer state still has the license key, submit final Run Installation in a controlled smoke test.
- Expected: entitlement preview may be refreshed, but `/licenses/activate` is still called only during Run Installation.

## Scenario F: Final Install Runs Migrations Seeders And Provisioning
- Complete all steps and submit `/install/run`.
- Expected: migrations run with `--force`; baseline roles, permissions, settings, required reference data seed, license activation, local license persistence, package sync, licensed location provisioning, and super admin creation complete without destructive commands.
- Expected: `installed.lock` is created only after successful migration, seeding, license activation, local license persistence, package sync, location provisioning, and super admin creation.
- Remove one required prepared CSV file in a disposable smoke environment and submit `/install/run`.
- Expected: browser shows "Location data files are missing. Please upload the complete Campaign Manager package or contact support." and does not call activation or write `installed.lock`.

## Scenario G: Activation Only During Run
- Confirm `/install/license` preview does not consume an activation.
- Confirm `/install/run` calls `/licenses/activate`.
- Retry `/install/run` after a local failure with the same app URL/domain/fingerprint.
- Expected: portal returns the same active activation instead of consuming another activation.

## Scenario H: Local License Storage
- After successful install, inspect `local_licenses`, `local_license_scopes`, `local_license_modules`, and `license_check_logs`.
- Expected: activation payload is stored locally with sensitive values redacted or masked for display/logs.

## Scenario I: First Super Admin
- After successful install, inspect the `users` table.
- Expected: first superadmin is created with `firstname`, `lastname`, `email`, optional `phone`, `access_level=superadmin`, `status=active`, and verified email timestamp.

## Scenario J: Install Lock
- After successful install, inspect `storage/app/installed.lock`.
- Expected: JSON metadata includes installed time, app name/url/version if available, deployment mode, package type, scope type, and masked license key.

## Scenario K: Installer Blocked After Lock
- Visit `/install` after successful install.
- Expected: redirect to login or dashboard; installer does not rerun.

## Scenario K2: Branded Installer Visual Check
- Review the installer on desktop and mobile widths.
- Expected: Campaign Manager branding/logo or graceful text fallback is visible, the product name reads "Campaign Manager Self-Hosted Installer", the security subtitle is visible, buttons are clear, status badges are readable, and the layout works without npm, Vite, or external CDN assets.

## Scenario L: Admin License Page
- Sign in as superadmin or nationaladmin and visit `/admin/license`.
- Expected: masked license key, status, deployment mode, package type, scope, modules, maintenance, updates, activation, app/domain details, dashboard label, helper display, and resolved boundary IDs/names are shown from local tables.

## Scenario M: Installer Reset Command
- Run `php artisan campaign:installer-reset --force`.
- Expected: only `storage/app/installed.lock` is removed. Database tables and users remain unchanged.

## Scenario N: Provisioning Recovery Command
- Normal customers should use the browser installer for location provisioning.
- Use `php artisan campaign:provision-locations --dry-run` or `--force` only for already-installed systems, recovery, or developer/admin maintenance.

## Phase 2B-2: Licensed Read-Path Scope
- In managed mode with no local license, run `php artisan campaign:scope-status`.
- Expected: fallback remains active, package resolves from `system_settings.package`, and read-path enforcement active is `no`.
- In self-hosted mode with a presidential/national local license, sign in as superadmin and open dashboard/location lists.
- Expected: states, senatorial districts, federal constituencies, LGAs, wards, polling units, people counts, result counts, incident counts, and evidence counts remain nationwide.
- In self-hosted mode with a governorship/state local license, open dashboard/location lists and AJAX dropdowns.
- Expected: only the licensed state and its LGAs, wards, polling units, senatorial districts, and federal constituencies are visible.
- In self-hosted mode with a senatorial district license, open dashboard/location lists and AJAX dropdowns.
- Expected: only the licensed senatorial district, parent state context, and related LGAs/wards/polling units where relationship data exists are visible.
- In self-hosted mode with a federal constituency license, open dashboard/location lists and AJAX dropdowns.
- Expected: only the licensed federal constituency, parent state context, and related LGAs/wards/polling units where relationship data exists are visible.
- In self-hosted mode with an LGA license, open dashboard/location lists and AJAX dropdowns.
- Expected: only the licensed LGA, parent state context, wards, and polling units inside the LGA are visible.
- Confirm member/location AJAX tables for wards and polling units do not show out-of-scope rows.
- Confirm election dashboard metrics for votes, polling unit results, incidents, and evidence reflect the licensed scope.
- Confirm installer routes still load in self-hosted uninstalled state and do not execute read-path enforcement before install.

## Phase 2B-3: Detail Guards And Write Validation
- In managed mode, open existing state/LGA/ward/polling unit/member/result/incident detail pages.
- Expected: existing managed behavior is unchanged and no local license record is required.
- In self-hosted mode with a governorship/state license, try to open another state's LGA, ward, polling unit, or member detail/edit URL directly.
- Expected: the request is blocked with 403 or a clear permission message.
- In self-hosted mode with an LGA license, try to open another LGA's detail/edit URL directly.
- Expected: the request is blocked.
- In self-hosted mode with a senatorial or federal constituency license, try to open another district/constituency detail/edit URL directly.
- Expected: the request is blocked where direct boundary data exists.
- Submit a member/user create or update form using an out-of-scope state, LGA, ward, or polling unit ID.
- Expected: validation fails and the record is not created or moved out of licensed scope.
- Submit polling unit agent nomination/direct assignment using an out-of-scope polling unit ID.
- Expected: validation/authorization fails.
- Submit election result or incident creation using an out-of-scope polling unit/location payload.
- Expected: validation/authorization fails and no result, vote, incident, or evidence record is created.
- Repeat the same actions with in-scope IDs.
- Expected: legitimate in-scope actions still work.
- Run `php artisan campaign:scope-status`.
- Expected: read-path, detail-route, and write payload helper availability is shown without exposing license secrets.

## Phase 2B-4: Dashboard, Import, Messaging, And Chart Cleanup
- In self-hosted scoped mode, open out-of-scope role dashboard drilldown URLs for state, senatorial district, federal constituency, LGA, ward, and polling unit.
- Expected: out-of-scope dashboard details are blocked; in-scope dashboard details still load.
- Open chart/AJAX endpoints for out-of-scope region, state, LGA, ward, polling unit, senatorial district, or federal constituency UUIDs.
- Expected: scoped deployments receive a 403 or clean failed response; managed mode behavior is unchanged.
- Compare national chart counts in a scoped self-hosted install.
- Expected: gender, age, region, religion, voter, state, and election result chart data reflect the licensed local scope.
- Import LGAs, wards, or polling units with rows outside the licensed scope.
- Expected: out-of-scope rows are rejected/skipped by the importer and not inserted; in-scope rows continue to import under existing duplicate rules.
- Use bulk SMS filters with out-of-scope state/LGA/ward/polling unit values.
- Expected: request fails with a scope error or recipient list resolves to in-scope users only; no out-of-scope phone numbers are queued.
- Search message recipients and try to start a direct conversation with an out-of-scope user.
- Expected: out-of-scope recipients are not listed and direct conversation creation is blocked.
- Trigger campaign notifications from a scoped action.
- Expected: notification recipient resolution is constrained to in-scope admin users.
- Review volunteer/support group pages.
- Expected: group records remain global because the current table has no geography fields, while member/recipient actions through those groups remain scoped.
