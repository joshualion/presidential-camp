# Production Demo Deployment

This runbook prepares the Campaign Manager presidential demo at:

```text
https://app.campaignmanager.ng
```

## Environment

Use `.env.production.example` as the safe template, then fill in real secrets directly on the server. Do not commit the real `.env`.

Important corrections for the provided production env:

```env
APP_URL=https://app.campaignmanager.ng
LOG_LEVEL=warning
SESSION_DOMAIN=.campaignmanager.ng

WEB_SOCKET_AVAILABILITY=true
CAMPAIGN_PACKAGE_TYPE=presidential
```

The websocket variables must be two separate lines. This is invalid and must not be used:

```env
WEB_SOCKET_AVAILABILITY = trueCAMPAIGN_PACKAGE_TYPE=presidential
```

For Reverb, keep Laravel's server-side broadcast connection local if Reverb runs on the same server:

```env
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Compile browser-facing Vite values for the public HTTPS hostname:

```env
VITE_REVERB_HOST=app.campaignmanager.ng
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

If you build assets while `VITE_REVERB_HOST=localhost`, prospects' browsers will try to connect to their own computers instead of the demo server.

## Server Setup

Install PHP dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

Ensure Laravel can write runtime files:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Use the web server user for your host. On cPanel/shared hosting this may be your account user instead of `www-data`.

Install and build frontend assets:

```bash
npm ci
npm run build
```

Prepare Laravel:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Run the scheduler every minute from cron:

```cron
* * * * * cd /home/USER/presidential-camp && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler prunes expired campaign notifications hourly.

## Required Workers

Run the queue worker continuously:

```bash
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

Run Reverb continuously:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Use Supervisor, systemd, or the hosting control panel process manager to keep both commands alive.

## Reverse Proxy

Expose the Laravel app over HTTPS at `app.campaignmanager.ng`.

Proxy websocket traffic for Reverb to `127.0.0.1:8080` and preserve upgrade headers. Example Nginx location:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_pass http://127.0.0.1:8080;
}
```

Laravel now forces generated URLs to HTTPS in production and trusts proxy headers.

## Smoke Checks

After deployment:

```bash
php artisan about
php artisan route:list --path=notifications
php artisan queue:failed
curl -I https://app.campaignmanager.ng/up
```

Then log in as a demo admin, create a scoped event such as a polling unit result or incident, and confirm:

- the notification row is created
- the recipient sees the unread badge
- the queue has no failed jobs
- Reverb stays running
