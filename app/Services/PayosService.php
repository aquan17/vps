<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayosService
{
    private $clientId;
    private $apiKey;
    private $checksumKey;
    private $baseUrl;

    public function __construct()
    {
        $this->clientId = (string) config('deposit.payos.client_id');
        $this->apiKey = (string) config('deposit.payos.api_key');
        $this->checksumKey = (string) config('deposit.payos.checksum_key');
        $this->baseUrl = rtrim((string) config('deposit.payos.base_url'), '/');
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->apiKey !== '' && $this->checksumKey !== '';
    }

    public function createPaymentLink(array $data): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayOS chưa được cấu hình.');
        }

        $payload = [
            'orderCode' => (int) $data['orderCode'],
            'amount' => (int) $data['amount'],
            'description' => (string) $data['description'],
            'cancelUrl' => (string) $data['cancelUrl'],
            'returnUrl' => (string) $data['returnUrl'],
        ];

        if (!empty($data['buyerName'])) {
            $payload['buyerName'] = (string) $data['buyerName'];
        }

        if (!empty($data['buyerEmail'])) {
            $payload['buyerEmail'] = (string) $data['buyerEmail'];
        }

        if (!empty($data['expiredAt'])) {
            $payload['expiredAt'] = (int) $data['expiredAt'];
        }

        $payload['signature'] = $this->makePaymentRequestSignature($payload);

        $response = Http::timeout(20)
            ->withHeaders([
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl . '/v2/payment-requests', $payload);

        $body = $response->json();

        if (!$response->successful() || data_get($body, 'code') !== '00') {
            throw new RuntimeException(data_get($body, 'desc', 'Không tạo được link thanh toán PayOS.'));
        }

        return $body;
    }

    public function getPaymentLinkInformation($orderCode): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayOS chưa được cấu hình.');
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->get($this->baseUrl . '/v2/payment-requests/' . (int) $orderCode);

        $body = $response->json();

        if (!$response->successful() || data_get($body, 'code') !== '00') {
            throw new RuntimeException(data_get($body, 'desc', 'Không lấy được trạng thái thanh toán PayOS.'));
        }

        return $body;
    }

    public function verifyWebhook(array $data, ?string $signature): bool
    {
        if (!$this->isConfigured() || !$signature) {
            return false;
        }

        $expected = $this->makeSignature($data);

        return hash_equals($expected, (string) $signature);
    }

    private function makePaymentRequestSignature(array $payload): string
    {
        $data = [
            'amount' => $payload['amount'],
            'cancelUrl' => $payload['cancelUrl'],
            'description' => $payload['description'],
            'orderCode' => $payload['orderCode'],
            'returnUrl' => $payload['returnUrl'],
        ];

        return $this->makeSignature($data);
    }

    private function makeSignature(array $data): string
    {
        ksort($data);

        $parts = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === 'null' || $value === 'undefined') {
                $value = '';
            }

            if (is_array($value)) {
                $value = $this->normalizeArrayValue($value);
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $parts[] = $key . '=' . $value;
        }

        return hash_hmac('sha256', implode('&', $parts), $this->checksumKey);
    }

    private function normalizeArrayValue(array $value): string
    {
        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                ksort($item);
            }
            $items[] = $item;
        }

        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }
}
