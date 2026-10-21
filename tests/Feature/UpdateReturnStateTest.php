<?php

use App\Services\Updates\PortalCheckoutAddress;
use App\Services\Updates\UpdateException;
use App\Services\Updates\UpdatePortalClient;
use Illuminate\Http\Request;

test('payment return requires the original session nonce and proof', function (string $variation) {
    $context = ['state' => str_repeat('a', 64), 'proof' => str_repeat('b', 64), 'expires' => now()->addMinutes(30)->timestamp];
    $parameters = ['campaign_update_return' => $context['state'], 'campaign_update_proof' => $context['proof']];
    if ($variation === 'nonce') {
        $parameters['campaign_update_return'] = str_repeat('c', 64);
    }
    if ($variation === 'proof') {
        $parameters['campaign_update_proof'] = str_repeat('c', 64);
    }
    if ($variation === 'expired') {
        $context['expires'] = now()->subSecond()->timestamp;
    }
    if ($variation === 'missing') {
        unset($parameters['campaign_update_proof']);
    }
    if ($variation === 'array') {
        $parameters['campaign_update_return'] = [$context['state']];
    }
    if ($variation === 'session') {
        $context = [];
    }
    session()->put('campaign_update_return', $context);
    $request = Request::create('/superadmin/updates/automatic', 'GET', $parameters);
    $request->setLaravelSession(session()->driver());
    expect(UpdatePortalClient::validBrowserReturn($request))->toBe($variation === 'valid');
})->with(['valid', 'nonce', 'proof', 'expired', 'missing', 'array', 'session']);

test('ordinary browsing needs no payment return state', function () {
    expect(UpdatePortalClient::validBrowserReturn(Request::create('/superadmin/updates/automatic')))->toBeTrue();
});

test('checkout binds the expiring return to the registered bootstrap mount', function () {
    config(['app.url' => 'https://customer.example/campaign', 'updates.return_path' => '/campaign-update-bootstrap.php', 'campaign.portal_web_base' => 'https://campaignmanager.ng']);
    $parameters = ['installation' => 'installation-id', 'quote_token' => str_repeat('a', 64), 'return_token' => str_repeat('b', 64), 'return_state' => str_repeat('c', 64), 'return_expires' => (string) now()->addMinutes(30)->timestamp, 'return_url' => 'https://customer.example/campaign/campaign-update-bootstrap.php'];
    $url = 'https://campaignmanager.ng/clientarea/automatic-update?'.http_build_query($parameters);
    app(PortalCheckoutAddress::class)->validate($url, ['installation' => 'installation-id', 'return_state' => str_repeat('c', 64)]);
    $this->travel(31)->minutes();
    expect(fn () => app(PortalCheckoutAddress::class)->validate($url, ['installation' => 'installation-id']))->toThrow(UpdateException::class, 'expired');
});
