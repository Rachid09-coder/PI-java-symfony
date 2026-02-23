<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Integrates the EasyPost Trackers API to allow students to follow their shipments.
 *
 * @see https://www.easypost.com/docs/api#trackers
 */
class ShippingService
{
    private const BASE_URL = 'https://api.easypost.com/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(EASYPOST_API_KEY)%')]
        private readonly string $apiKey,
    ) {}

    /**
     * Detects if the provided API key is for "Test" or "Production" mode.
     * EasyPost test keys start with 'EZTK'.
     */
    public function isTestMode(): bool
    {
        return str_starts_with($this->apiKey, 'EZTK');
    }

    // ── Public API ───────────────────────────────────────────────

    /**
     * Creates a new Tracker for a given tracking number.
     *
     * @param string $trackingCode The courier tracking number
     * @param string $carrier      Carrier name, or 'auto' for auto-detection
     *
     * @throws \RuntimeException on HTTP or API errors
     */
    public function createTracker(string $trackingCode, string $carrier = 'auto'): array
    {
        if (empty(trim($trackingCode))) {
            throw new \InvalidArgumentException('Tracking code cannot be empty.');
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                self::BASE_URL . '/trackers',
                [
                    'auth_basic' => [$this->apiKey, ''],
                    'headers'    => ['Content-Type' => 'application/json'],
                    'json'       => [
                        'tracker' => [
                            'tracking_code' => $trackingCode,
                            'carrier'       => $carrier === 'auto' ? null : $carrier,
                        ],
                    ],
                ]
            );

            $data = $response->toArray();
        } catch (ClientExceptionInterface $e) {
            $body    = json_decode($e->getResponse()->getContent(false), true);
            $message = $body['error']['message'] ?? $e->getMessage();
            throw new \RuntimeException('EasyPost API error: ' . $message, $e->getCode(), $e);
        } catch (ServerExceptionInterface | TransportExceptionInterface $e) {
            throw new \RuntimeException('Could not reach EasyPost API: ' . $e->getMessage(), 0, $e);
        }

        return $this->normalizeTracker($data);
    }

    /**
     * Retrieves the current tracking status for an existing EasyPost tracker ID.
     *
     * @param string $trackerId The EasyPost tracker ID (e.g. "trk_xxxxxxxxxxxx")
     *
     * @throws \RuntimeException on HTTP or API errors
     */
    public function getTrackingStatus(string $trackerId): array
    {
        if (empty(trim($trackerId))) {
            throw new \InvalidArgumentException('Tracker ID cannot be empty.');
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                self::BASE_URL . '/trackers/' . urlencode($trackerId),
                [
                    'auth_basic' => [$this->apiKey, ''],
                ]
            );

            $data = $response->toArray();
        } catch (ClientExceptionInterface $e) {
            $body    = json_decode($e->getResponse()->getContent(false), true);
            $message = $body['error']['message'] ?? $e->getMessage();
            throw new \RuntimeException('EasyPost API error: ' . $message, $e->getCode(), $e);
        } catch (ServerExceptionInterface | TransportExceptionInterface $e) {
            throw new \RuntimeException('Could not reach EasyPost API: ' . $e->getMessage(), 0, $e);
        }

        return $this->normalizeTracker($data);
    }

    /**
     * Generates a unique, never-hardcoded tracking number in USPS 22-digit format.
     */
    public function generateTrackingNumber(): string
    {
        // 4-digit USPS-style prefix
        $prefix = '9400';

        // 18 cryptographically-random digits, zero-padded on the left
        $randomDigits = str_pad(
            (string) random_int(0, 999_999_999_999_999_999),
            18,
            '0',
            STR_PAD_LEFT
        );

        return $prefix . $randomDigits; // total: 22 digits
    }

    /**
     * Rotates EasyPost test tracking codes (EZ1000000001 - EZ7000000007).
     * Useful for Standalone Tracking testing without external carrier delays.
     */
    public function generateTestTrackingNumber(): string
    {
        $testCodes = [
            'EZ1000000001', 'EZ2000000002', 'EZ3000000003',
            'EZ4000000004', 'EZ5000000005', 'EZ6000000006', 'EZ7000000007'
        ];

        return $testCodes[array_rand($testCodes)];
    }

    /**
     * Creates an EasyPost tracker and writes the resulting shipping fields onto
     * the given Order entity. The caller is responsible for flushing.
     *
     * Environment Awareness:
     * - If in Test Mode: Automatically generates an 'EZ...' test code if no code is provided.
     * - If in Production Mode: Generates a USPS-style number or uses the provided one.
     *
     * @param Order       $order        The order to attach tracking to
     * @param string|null $trackingCode Courier tracking number, or null to auto-generate
     * @param string      $carrier      Carrier name, or 'auto' for auto-detection
     */
    public function attachTrackerToOrder(Order $order, ?string $trackingCode = null, string $carrier = 'auto'): void
    {
        // 1. Determine local behavior based on API Key (Test vs Production)
        $isTest = $this->isTestMode();

        if (null === $trackingCode) {
            // Auto-generate the appropriate type of code for the environment
            $trackingCode = $isTest 
                ? $this->generateTestTrackingNumber() 
                : $this->generateTrackingNumber();
        }

        // 2. Set the code on the Order immediately for persistence.
        $order->setTrackingNumber($trackingCode);

        try {
            // 3. Create the tracker in EasyPost
            $tracker = $this->createTracker($trackingCode, $carrier);

            // 4. Map ALL returned metadata back to our Order entity
            $order
                ->setTrackerId($tracker['id'])
                ->setTrackingNumber($tracker['tracking_code']) // Use the canonical form from EasyPost
                ->setCarrier($tracker['carrier'])
                ->setShippingStatus($tracker['status'])
                ->setStatusDetail($tracker['status_detail'])
                ->setPublicUrl($tracker['public_url'])
                ->setShippingUpdatedAt(new \DateTimeImmutable());

        } catch (\Throwable $e) {
            // Log for the developer/admin, but don't crash the student's checkout
            error_log(sprintf(
                '[ShippingService] Tracker creation failed (%s mode): %s', 
                $isTest ? 'TEST' : 'PROD', 
                $e->getMessage()
            ));
        }
    }

    // ── Internals ────────────────────────────────────────────────

    /**
     * Normalises a raw EasyPost Tracker response into a clean, predictable structure.
     */
    private function normalizeTracker(array $data): array
    {
        return [
            'id'                => $data['id'] ?? '',
            'tracking_code'     => $data['tracking_code'] ?? '',
            'carrier'           => $data['carrier'] ?? 'Unknown',
            'status'            => $data['status'] ?? 'unknown',
            'status_detail'     => $data['status_detail'] ?? '',
            'public_url'        => $data['public_url'] ?? '',
            'est_delivery_date' => $data['est_delivery_date'] ?? null,
            'signed_by'         => $data['signed_by'] ?? null,
            'tracking_details'  => array_map(
                fn (array $event): array => [
                    'message'  => $event['message'] ?? '',
                    'datetime' => $event['datetime'] ?? '',
                    'status'   => $event['status'] ?? '',
                    'status_detail' => $event['status_detail'] ?? '',
                    'source'   => $event['source'] ?? '',
                ],
                $data['tracking_details'] ?? []
            ),
            'created_at' => $data['created_at'] ?? '',
            'updated_at' => $data['updated_at'] ?? '',
        ];
    }
}
