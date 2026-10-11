# Campaign Manager Self-Hosted Package

This package is the universal Campaign Manager core app for licensed self-hosted installs.

## Production Deployment

- Do not expose the Laravel root publicly.
- Point the domain document root directly to the package `public` directory.
- The production URL should look like `https://clientdomain.com`, not `https://clientdomain.com/public`.
- On shared hosting, use the bridge sample in `docs/self-hosted-shared-hosting-guide.md` when the core app must live outside `public_html`.
- Prefer a `public_html` symbolic link to the package `public` directory when the host supports it.
- Real `.htaccess` files are excluded from customer ZIPs; `.htaccess.example` is the safe optional template.

## Install

1. Upload and extract the ZIP on the server.
2. Copy `.env.example` to `.env`.
3. Set database credentials and `APP_URL`.
4. Open the browser installer.
5. Complete package checks, database setup, license activation, geography provisioning, and first superadmin creation.

The package includes `vendor` and built frontend assets, so customers should not need Composer, Node.js, npm, or Vite for a normal install.

`APP_KEY` is generated uniquely by the browser installer when `.env` is writable. Do not change it after installation.

## Optional Features

- Community Forum is optional and disabled by default.
- Community realtime uses polling by default.
- Reverb is an optional advanced mode that requires additional server setup.

See `docs/self-hosted-installation-guide.md` for the full customer install process.

### Confirm hosting ownership before setup

The installer now requires a local server setup code before accepting configuration. Open `/install/access`, then read `storage/app/installer/access-token` through your own hosting file manager or SSH and enter that code. Do not share it or expose the storage directory through the web server. Alternatively, set a random `CAMPAIGN_INSTALLER_ACCESS_TOKEN` of at least 32 characters in the server environment before setup. This protects installation from visitors; it is not a licence key, paid service or remote activation. Existing completed installations keep the installer closed.

### Update and support foundation (not yet a production upgrade runner)

Super Admin → Official Updates checks the official GitHub release and verifies signed metadata against the installed trusted public key. Preparation downloads and verifies a package into private staging without replacing application files or running migrations. An apply/backup/rollback runner is not enabled in this implementation checkpoint. Releases older than this updater require an initial manual upgrade.

Portal registration is optional and explains the deployment ID, domain, administrator contact email, installed version and status before submission. Marketing has a separate opt-in and requires email confirmation. Revocation disables local polling immediately and retries remote deletion if the Portal is unavailable. No supporter, voter, password or campaign records are transmitted by this registration service. Public Community ZIP downloads remain free. Paid assistance credits are separate from software activation.
