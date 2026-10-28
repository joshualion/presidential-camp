# Campaign Manager Self-Hosted Package

Community self-hosted installation is free. Optional geography provisioning and automatic-update coverage are separate services. A new installation requires an installation-registration receipt after licence/privacy acceptance; no payment or existing Portal customer account is required for that registration.

## New installation

1. Upload the complete `campaign-manager.zip` release asset. It contains production PHP dependencies and built frontend assets; Composer, npm and Vite are not needed on the customer server.
2. Point the domain document root to this application's `public` directory. Keep `.env`, `vendor` and `storage/app` outside the public document root. Use the supplied `.htaccess.example` only where required by your host.
3. Select PHP 8.2 or newer for this domain and enable the required extensions. CLI PHP and the domain's website PHP can use different settings. Do not change PHP globally for unrelated domains.
4. If SSH is available, run `php tools/install/diagnose.php` from the application folder before setup. This read-only command works without booting Laravel or connecting to a database and prints no environment values, passwords, keys or setup tokens. `--json` produces a support-friendly report.
5. Open `/install/access`. Obtain the private setup code from `storage/app/installer/access-token` using your hosting file manager or SSH and enter it locally. Do not share the code. This is hosting ownership proof, not a paid provisioning key.
6. Complete the installer using a new, empty database and the site's correct HTTPS `APP_URL`. Select the campaign type and geography. For a House of Representatives campaign, choose **Federal Constituency** and the candidate's constituency.
7. Manual geography setup is free. QuickStart geography provisioning is optional and requires its own purchased provisioning code.
8. Enter the Super Administrator details, accept the licence/privacy notice and run installation. Keep the browser session open until completion. Confirm the contact email afterward when the verification message arrives.

The installer generates a unique `APP_KEY` when `.env` is writable. Retain that key throughout installation and afterward. Changing it invalidates encrypted registration, credentials and sessions.

## If setup is interrupted

- Preserve `.env`, `APP_KEY`, the database, `storage/app/installer`, registration state, uploads and backups. Do not delete files to bypass a setup or update lock.
- A registration outage must stop before database migrations. Retry the saved installation step once the official Portal is reachable; the existing installation identity is reused.
- Failure to send the final completion-status report does not invalidate an already accepted registration receipt. Its status can be retried later.
- The Super Administrator password stays in the browser session and is not saved in installer progress files. A lost session can require ownership confirmation and re-entering the administrator password.
- If the database already contains campaign records or users, stop and diagnose the existing installation. Do not use `migrate:fresh`, `db:wipe`, database deletion or a new `APP_KEY` to repair it.
- When requesting support, send the release version, exact error, current installer step and the diagnostic output. Never send `.env`, database passwords, setup codes, bearer tokens or registration credentials.

## Existing installations and updates

Do not run the new-install wizard to upgrade an existing installation. Use **Super Admin -> Official Updates** and the established signed update process, or the free manual upgrade procedure for the release. Automatic installation requires the applicable update coverage, authenticated Super Admin consent and a verified private backup. A verification-email return does not itself purchase, download or install an update.

Keep the original application key, environment, customer records, uploads, registration identity, role grants and backups. The public distribution repository supplies the downloadable release assets; the source repositories can remain private after verification.

## Enterprise scope

The Add-ons catalogue and licensing foundation do not constitute working Reports/PDF, AI billing, Finance, donations, Merchandise, paid activation or provisioning modules. Unfinished enterprise functionality remains disabled. Existing Community functionality and update coverage remain independent.
