<?php

namespace Kreatif\BrevoMailer\Brevo;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Kreatif\BrevoMailer\Exceptions\BrevoApiException;

class BrevoApiClient
{
    private ClientInterface $client;

    public function __construct(
        private readonly string $apiKey,
        string $baseUri,
        int $timeout = 10,
        ?ClientInterface $client = null,
    ) {
        $this->client = $client ?? new Client([
            'base_uri' => $baseUri,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Send a single transactional email via `POST /v3/smtp/email`.
     *
     * @param array $payload Brevo `smtp/email` request body (sender, to, subject, htmlContent, ...).
     * @return array{messageId?: string} Decoded Brevo response body.
     *
     * @throws BrevoApiException on a non-2xx response or a transport-level failure (timeout, DNS, ...).
     */
    public function sendTransactionalEmail(array $payload): array
    {
        try {
            $response = $this->client->post('smtp/email', [
                'headers' => [
                    'api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new BrevoApiException(
                "Brevo API request failed: {$e->getMessage()}",
                statusCode: 0,
                responseBody: '',
                previous: $e,
            );
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new BrevoApiException(
                "Brevo API responded with status {$statusCode}: {$body}",
                statusCode: $statusCode,
                responseBody: $body,
            );
        }

        return json_decode($body, true) ?? [];
    }
}
