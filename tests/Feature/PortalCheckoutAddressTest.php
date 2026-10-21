<?php

use App\Services\Updates\PortalCheckoutAddress;
use App\Services\Updates\UpdateException;

function checkoutBinding(): array
{
    return ['installation' => '5fbd084e-bfbb-4d65-bfe5-dd2ecfd7012c', 'version' => '1.1.6', 'sha256' => str_repeat('a', 64), 'package_type' => 'presidential'];
}

function checkoutAddress(string $origin = 'https://campaignmanager.ng', array $overrides = []): string
{
    return $origin.'/clientarea/automatic-update?'.http_build_query(array_replace(checkoutBinding() + ['quote_token' => str_repeat('b', 64)], $overrides));
}

test('only explicitly approved HTTPS checkout origins are accepted', function ($origin) {
    app(PortalCheckoutAddress::class)->validate(checkoutAddress($origin), checkoutBinding());
    expect(true)->toBeTrue();
})->with(['https://campaignmanager.ng', 'https://www.campaignmanager.ng', 'https://CAMPAIGNMANAGER.NG', 'https://campaignmanager.ng:443']);

test('checkout destinations reject downgrade spoofing credentials ports and unrelated paths', function ($url) {
    expect(fn () => app(PortalCheckoutAddress::class)->validate($url, checkoutBinding()))->toThrow(UpdateException::class);
})->with([
    fn () => checkoutAddress('http://campaignmanager.ng'),
    fn () => checkoutAddress('https://campaignmanager.ng.attacker.test'),
    fn () => checkoutAddress('https://attacker.test'),
    fn () => checkoutAddress('https://user@campaignmanager.ng'),
    fn () => checkoutAddress('https://:secret@campaignmanager.ng'),
    fn () => checkoutAddress('https://campaignmanager.ng:444'),
    fn () => checkoutAddress('https://campaignmanager.ng.主站.test'),
    fn () => str_replace('/clientarea/automatic-update', '/redirect', checkoutAddress()),
    fn () => checkoutAddress().'#https://attacker.test',
    fn () => checkoutAddress().'&return_url=https://attacker.test&return_url=https://campaignmanager.ng',
]);

test('checkout binding cannot substitute an installation release checksum or scope', function ($key, $value) {
    expect(fn () => app(PortalCheckoutAddress::class)->validate(checkoutAddress(overrides: [$key => $value]), checkoutBinding()))->toThrow(UpdateException::class);
})->with([
    ['installation', 'other'], ['version', '1.1.5'], ['sha256', str_repeat('c', 64)], ['package_type', 'lga'], ['quote_token', ''],
]);

test('custom portal aliases are explicit and do not trust arbitrary www variants', function () {
    config(['campaign.portal_web_base' => 'https://portal.example.test', 'campaign.portal_checkout_origins' => []]);
    app(PortalCheckoutAddress::class)->validate(checkoutAddress('https://portal.example.test'), checkoutBinding());
    expect(fn () => app(PortalCheckoutAddress::class)->validate(checkoutAddress('https://www.portal.example.test'), checkoutBinding()))->toThrow(UpdateException::class);
});

test('a modern checkout return must match this installations configured update page', function () {
    config(['app.url' => 'https://installation.example.test/campaign']);
    $parameters = ['return_url' => 'https://installation.example.test/campaign/superadmin/updates/automatic', 'return_token' => str_repeat('c', 64)];
    app(PortalCheckoutAddress::class)->validate(checkoutAddress(overrides: $parameters), checkoutBinding());
    $parameters['return_url'] = 'https://other.example.test/superadmin/updates/automatic';
    expect(fn () => app(PortalCheckoutAddress::class)->validate(checkoutAddress(overrides: $parameters), checkoutBinding()))->toThrow(UpdateException::class);
});
