<?php

namespace App\Services\Updates;

/** Server-side gate for every package download, including legacy bootstrap entry points. */
class UpdateDownloadAccess
{
    public function __construct(private AutomaticUpdateService $automatic, private UpdatePortalClient $portal) {}

    public function quote(array $status): array
    {
        $this->automatic->readiness();
        $quote = $this->portal->status($status['latest_version'], $status['manifest']['checksum_sha256']);
        if (($quote['contact_confirmed'] ?? null) !== true) {
            throw new UpdateFlowException('registration', 'Confirm the installation contact email before using automatic updates.');
        }

        $quote['entitled'] = ($quote['entitled'] ?? null) === true;
        $quote['payment_available'] = ($quote['payment_available'] ?? null) === true;

        return $quote;
    }

    public function assertAllowed(array $status): void
    {
        $quote = $this->quote($status);
        if (empty($quote['entitled'])) {
            throw new UpdateFlowException('coverage', 'Automatic-update coverage is required before downloading an update to this server. Continue to secure payment, or download the free manual package from GitHub.');
        }
    }
}
