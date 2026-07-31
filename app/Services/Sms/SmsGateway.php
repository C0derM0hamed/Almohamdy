<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Corporate / portal SMS sender (legacy mshastra + synapse providers).
 */
class SmsGateway
{
    public function isConfigured(): bool
    {
        $provider = $this->provider();

        if ($provider === 'log') {
            return true;
        }

        if ($provider === 'mshastra') {
            return filled(config('hm.sms.mshastra.user'))
                && filled(config('hm.sms.mshastra.password'));
        }

        if ($provider === 'synapse') {
            return filled(config('hm.sms.synapse.user'))
                && filled(config('hm.sms.synapse.password'));
        }

        return false;
    }

    public function provider(): string
    {
        return strtolower((string) config('hm.sms.provider', 'log'));
    }

    /**
     * @return array{ok:bool,provider:string,response:?string,error:?string}
     */
    public function send(string $mobile, string $message, ?string $sender = null): array
    {
        $sender = trim((string) ($sender ?: config('hm.sms.sender', 'ALHAMMADI')));
        if ($sender === '') {
            $sender = 'ALHAMMADI';
        }

        $message = $this->sanitizeMessage($message);
        $mobile = trim($mobile);

        if ($mobile === '' || $message === '') {
            return [
                'ok' => false,
                'provider' => $this->provider(),
                'response' => null,
                'error' => 'Missing mobile or message',
            ];
        }

        $provider = $this->provider();

        try {
            return match ($provider) {
                'mshastra' => $this->sendMshastra($mobile, $message, $sender),
                'synapse' => $this->sendSynapse($mobile, $message, $sender),
                default => $this->sendLog($mobile, $message, $sender),
            };
        } catch (\Throwable $e) {
            Log::error('sms.send_failed', [
                'provider' => $provider,
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'provider' => $provider,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok:bool,provider:string,response:?string,error:?string}
     */
    private function sendLog(string $mobile, string $message, string $sender): array
    {
        Log::info('sms.log_provider', [
            'mobile' => $mobile,
            'sender' => $sender,
            'message' => $message,
        ]);

        return [
            'ok' => true,
            'provider' => 'log',
            'response' => 'logged',
            'error' => null,
        ];
    }

    /**
     * @return array{ok:bool,provider:string,response:?string,error:?string}
     */
    private function sendMshastra(string $mobile, string $message, string $sender): array
    {
        $user = (string) config('hm.sms.mshastra.user');
        $password = (string) config('hm.sms.mshastra.password');

        if ($user === '' || $password === '') {
            return [
                'ok' => false,
                'provider' => 'mshastra',
                'response' => null,
                'error' => 'Mshastra credentials are not configured',
            ];
        }

        $url = 'https://saudi.mshastra.com/sendurl.aspx?'.http_build_query([
            'user' => $user,
            'pwd' => $password,
            'senderid' => $sender,
            'mobileno' => $mobile,
            'msgtext' => $message,
            'priority' => 'High',
            'CountryCode' => '+966',
        ]);

        $response = Http::timeout(15)->get($url);
        $body = $response->body();

        Log::info('sms.mshastra_response', [
            'mobile' => $mobile,
            'status' => $response->status(),
            'body' => $body,
        ]);

        return [
            'ok' => $response->successful(),
            'provider' => 'mshastra',
            'response' => $body,
            'error' => $response->successful() ? null : 'HTTP '.$response->status(),
        ];
    }

    /**
     * @return array{ok:bool,provider:string,response:?string,error:?string}
     */
    private function sendSynapse(string $mobile, string $message, string $sender): array
    {
        $user = (string) config('hm.sms.synapse.user');
        $password = (string) config('hm.sms.synapse.password');
        $endpoint = (string) config(
            'hm.sms.synapse.url',
            'https://api.synapse4sa.com/v1/multichannel/messages/sendsms'
        );

        if ($user === '' || $password === '') {
            return [
                'ok' => false,
                'provider' => 'synapse',
                'response' => null,
                'error' => 'Synapse credentials are not configured',
            ];
        }

        $payload = [
            'userName' => $user,
            'password' => $password,
            'priority' => 0,
            'dlrUrl' => null,
            'msgType' => 8,
            'senderId' => $sender,
            'message' => $message,
            'mobileNumbers' => [
                'messageParams' => [
                    ['mobileNumber' => $this->formatSaudiMobile($mobile)],
                ],
            ],
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        $body = $response->body();

        Log::info('sms.synapse_response', [
            'mobile' => $mobile,
            'status' => $response->status(),
            'body' => $body,
        ]);

        return [
            'ok' => $response->successful(),
            'provider' => 'synapse',
            'response' => $body,
            'error' => $response->successful() ? null : 'HTTP '.$response->status(),
        ];
    }

    private function sanitizeMessage(string $message): string
    {
        $message = strip_tags($message);
        $message = str_replace(['(', ')'], '', $message);

        return trim($message);
    }

    private function formatSaudiMobile(string $mobile): string
    {
        $mobile = preg_replace('/[\s+\-]/', '', $mobile) ?? $mobile;

        if (str_starts_with($mobile, '0')) {
            $mobile = '966'.substr($mobile, 1);
        }

        if (! str_starts_with($mobile, '966')) {
            $mobile = '966'.$mobile;
        }

        return $mobile;
    }
}
