<?php

namespace App\Services\Enterprise\Payments;

interface PaymentGateway
{
    public function checkConnection(string $secret): void;
    public function initializeCollection(string $secret, array $details): array;
    public function listSettlements(string $secret, string $from, string $to): array;
    public function fetchRefund(string $secret, int $refundId): array;
    public function verifyCollection(string $secret, string $reference): array;
    public function fetchRecipient(string $secret, string $code): array;
    public function initiateTransfer(string $secret, array $details): array;
    public function initiateBulkTransfer(string $secret, array $details): array;
    public function verifyTransfer(string $secret, string $reference): array;
    public function authenticates(string $body, string $header, string $legacy, ?string $webhookKey, string $secret): bool;
}
