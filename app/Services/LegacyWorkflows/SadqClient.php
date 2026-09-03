<?php

namespace App\Services\LegacyWorkflows;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SadqClient
{
    private string $baseUrl;

    private ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.sadq.base_url');
    }

    public function authenticate(): string
    {
        $this->ensureConfigured();
        $request = Http::baseUrl($this->baseUrl)
            ->timeout((int) config('services.sadq.timeout', 60))
            ->asForm()
            ->acceptJson();

        $basic = (string) config('services.sadq.auth_basic');
        if ($basic !== '') {
            $request = $request->withHeaders(['Authorization' => 'Basic '.$basic]);
        } else {
            $request = $request->withBasicAuth(
                (string) config('services.sadq.client_username'),
                (string) config('services.sadq.client_password'),
            );
        }

        $response = $request->post('/Authentication/Authority/Token');
        $token = (string) ($response->json('access_token') ?? '');

        if ($response->failed() || $token === '') {
            Log::warning('Sadq authentication failed', ['status' => $response->status()]);
            throw new RuntimeException('تعذر تسجيل الدخول إلى منصة صادق.');
        }

        return $this->accessToken = $token;
    }

    /** @return array<string, mixed> */
    public function initiateEnvelopeBase64(string $fileName, string $base64File, string $referenceNumber): array
    {
        return $this->request('POST', '/IntegrationService/Document/Initiate-envelope-Base64', [
            'referenceNumber' => $referenceNumber,
            'File' => [
                'FileName' => $fileName,
                'File' => $base64File,
                'password' => '',
                'hideEnvelopData' => false,
            ],
        ]);
    }

    /** @param array<int, array<string, mixed>> $destinations */
    public function sendInvitation(string $documentId, array $destinations, string $message, string $subject): array
    {
        return $this->request('POST', '/IntegrationService/Invitation/Send-Invitation', [
            'DocumentId' => $documentId,
            'Destinations' => array_map(static fn (array $destination): array => [
                'DestinationName' => (string) ($destination['DestinationName'] ?? ''),
                'DestinationEmail' => (string) ($destination['DestinationEmail'] ?? ''),
                'DestinationPhoneNumber' => (string) ($destination['DestinationPhoneNumber'] ?? ''),
                'NationalId' => (string) ($destination['NationalId'] ?? ''),
                'SigneOrder' => (int) ($destination['SigneOrder'] ?? 0),
                'ConsentOnly' => (bool) ($destination['ConsentOnly'] ?? true),
                'Signatories' => (array) ($destination['Signatories'] ?? []),
                'AvailableTo' => (string) ($destination['AvailableTo'] ?? config('services.sadq.available_to')),
                'AuthenticationType' => (int) ($destination['AuthenticationType'] ?? 2),
                'InvitationLanguage' => (int) ($destination['InvitationLanguage'] ?? 1),
                'RedirectUrl' => (string) ($destination['RedirectUrl'] ?? ''),
                'AllowUserToAddDestination' => (bool) ($destination['AllowUserToAddDestination'] ?? false),
                'DailyNotify' => (bool) ($destination['DailyNotify'] ?? false),
            ], $destinations),
            'InvitationMessage' => $message,
            'InvitationSubject' => $subject,
        ]);
    }

    /** @return array<string, mixed> */
    public function getEnvelopeStatusByReference(string $referenceNumber): array
    {
        return $this->request('GET', '/IntegrationService/Document/envelope-status/referenceNumber/'.rawurlencode($referenceNumber));
    }

    /** @return array<string, mixed> */
    public function downloadDocumentBase64(string $documentId): array
    {
        return $this->request('GET', '/IntegrationService/Document/DownloadBase64/'.rawurlencode($documentId));
    }

    /** @return array<string, mixed> */
    public function sendSignReminder(string $requestId, string $destinationPhone, string $destinationEmail = ''): array
    {
        return $this->request('POST', '/IntegrationService/Invitation/Signe-Reminder', [
            'requestId' => $requestId,
            'DestinationPhone' => $destinationPhone,
            'DestinationEmail' => $destinationEmail,
        ]);
    }

    /** @return array<string, mixed> */
    public function cancelEnvelope(string $envelopeId): array
    {
        return $this->request('POST', '/IntegrationService/Document/Cancel-envelope', ['envelopId' => $envelopeId]);
    }

    /** @return array<string, mixed> */
    private function request(string $method, string $endpoint, ?array $payload = null): array
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $request = Http::baseUrl($this->baseUrl)
            ->timeout((int) config('services.sadq.timeout', 60))
            ->acceptJson()
            ->withToken($this->accessToken);

        $response = $method === 'GET' ? $request->get($endpoint) : $request->post($endpoint, $payload ?? []);
        if ($response->failed()) {
            Log::warning('Sadq API request failed', ['endpoint' => $endpoint, 'status' => $response->status()]);
            throw new RuntimeException('تعذر تنفيذ طلب منصة صادق (HTTP '.$response->status().').');
        }

        return $response->json() ?: [];
    }

    private function ensureConfigured(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (!(bool) config('services.sadq.enabled')) {
            throw new RuntimeException('منصة صادق غير مفعّلة. اضبط SADQ_ENABLED=true بعد إضافة مفاتيح التكامل.');
        }

        $hasBasic = (string) config('services.sadq.auth_basic') !== '';
        if (!$hasBasic && ((string) config('services.sadq.client_username') === '' || (string) config('services.sadq.client_password') === '')) {
            throw new RuntimeException('إعدادات منصة صادق غير مكتملة. راجع متغيرات SADQ_* في ملف البيئة.');
        }
    }
}
