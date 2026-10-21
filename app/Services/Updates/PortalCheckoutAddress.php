<?php

namespace App\Services\Updates;

class PortalCheckoutAddress
{
    public function validate(string $url, array $binding): void
    {
        $parts = parse_url($url);
        $origin = $this->origin($url, false);
        $trusted = array_merge([(string) config('campaign.portal_web_base')], (array) config('campaign.portal_checkout_origins', []));
        $origins = array_map(fn ($value) => $this->origin((string) $value, true), $trusted);
        if (! in_array($origin, $origins, true) || ($parts['path'] ?? '') !== '/clientarea/automatic-update'
            || array_key_exists('fragment', $parts)) {
            throw new UpdateException('Invalid automatic-update checkout address.');
        }
        // Reject duplicate/array keys and browser/PHP parsing ambiguities.
        $query = [];
        foreach (explode('&', $parts['query'] ?? '') as $pair) {
            $pieces = explode('=', $pair, 2);
            $key = rawurldecode($pieces[0]);
            if (isset($query[$key]) || ! in_array($key, ['installation', 'version', 'sha256', 'package_type', 'quote_token', 'return_url', 'return_token', 'return_expires', 'return_state'], true)) {
                throw new UpdateException('Invalid automatic-update checkout parameters.');
            }
            $query[$key] = rawurldecode($pieces[1] ?? '');
        }
        foreach ($binding as $key => $value) {
            if (($query[$key] ?? null) !== $value) {
                throw new UpdateException('The checkout does not match this installation and release.');
            }
        }
        $path = config('updates.return_path', '/superadmin/updates/automatic');
        if (! in_array($path, ['/superadmin/updates/automatic', '/campaign-update-bootstrap.php'], true)) {
            throw new UpdateException('Invalid installation update address.');
        }
        if (isset($query['return_url']) && ($query['return_url'] !== rtrim(config('app.url'), '/').$path
            || ! preg_match('/^[a-f0-9]{64}$/D', $query['return_token'] ?? ''))) {
            throw new UpdateException('Invalid installation return address.');
        }
        if (isset($query['return_expires']) || isset($query['return_state'])) {
            if (! ctype_digit($query['return_expires'] ?? '') || (int) $query['return_expires'] < now()->timestamp || (int) $query['return_expires'] > now()->addHour()->timestamp || ! preg_match('/^[a-f0-9]{64}$/D', $query['return_state'] ?? '')) {
                throw new UpdateException('This checkout has expired. Choose Update Automatically again.');
            }
        }
        if (! preg_match('/^[a-f0-9]{64}$/D', $query['quote_token'] ?? '')) {
            throw new UpdateException('Invalid automatic-update quote.');
        }
    }

    private function origin(string $url, bool $originOnly): string
    {
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || preg_match('/[\\\\\x00-\x20\x7f]/', $url) || ! $parts
            || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || array_key_exists('user', $parts) || array_key_exists('pass', $parts)
            || ($parts['port'] ?? 443) !== 443
            || ($originOnly && (! empty($parts['path']) && $parts['path'] !== '/' || isset($parts['query']) || isset($parts['fragment'])))) {
            throw new UpdateException('Automatic updates require explicitly trusted HTTPS Portal origins.');
        }

        return 'https://'.strtolower($parts['host']);
    }
}
