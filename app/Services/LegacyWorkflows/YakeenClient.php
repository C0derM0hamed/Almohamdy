<?php

namespace App\Services\LegacyWorkflows;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

final class YakeenClient
{
    private const SERVICE_IDS = [
        1 => '934bf427-1115-42fc-936b-85e70e7355f7', // Saudi NIN; date is Hijri.
        2 => '397da347-142a-4d98-a664-0abbbf61e754', // Iqama; date is Gregorian.
        3 => '85bab40a-899b-49c4-b541-0686d1060ef1', // GCC NIN.
        4 => '6f77ef3c-6fc5-41eb-8948-1bd785f21cbf', // Non-Saudi passport.
        5 => 'cc70c017-6cbc-4671-9a36-ff06c86ed3d1', // Border number.
    ];

    /** @return array<string, mixed> */
    public function lookup(int $idType, string $idNumber, ?int $year = null, ?int $month = null, ?int $nationality = null): array
    {
        $this->ensureConfigured();
        abort_unless(isset(self::SERVICE_IDS[$idType]), 422, 'نوع الهوية غير مدعوم في خدمة يقين.');

        $idNumber = trim($idNumber);
        abort_if($idNumber === '', 422, 'رقم الهوية مطلوب للاستعلام من يقين.');

        $query = match ($idType) {
            1 => ['nin' => $idNumber, 'dateString' => $this->yearMonth($year, $month)],
            2 => ['iqama' => $idNumber, 'birthDateG' => $this->yearMonth($year, $month)],
            3 => ['gccNin' => $idNumber, 'nationalityCode' => (string) ($nationality ?? '')],
            4 => ['passportNo' => $idNumber, 'nationality' => (string) ($nationality ?? '')],
            5 => ['borderNo' => $idNumber, 'birthDateG' => $this->yearMonth($year, $month)],
        };

        if (in_array($idType, [3, 4], true) && ($query['nationalityCode'] ?? $query['nationality'] ?? '') === '') {
            abort(422, 'الجنسية مطلوبة للاستعلام من يقين.');
        }

        try {
            $response = $this->request($query, self::SERVICE_IDS[$idType]);
        } catch (ConnectionException) {
            Log::warning('Yakeen transport failure', [
                'id_type' => $idType,
                'service_identifier' => self::SERVICE_IDS[$idType],
            ]);

            throw new RuntimeException('تعذر الاتصال بخدمة يقين من السيرفر. تأكد من السماح لعنوان IP الخاص بالسيرفر في خدمة يقين ثم أعد المحاولة.');
        }

        $person = $this->personFromResponse($response, $idType);

        $this->audit($idType, $idNumber, $query, $response);

        if ($person === []) {
            $message = data_get($response, 'errorDetail.errorMessage')
                ?: data_get($response, 'message')
                ?: 'لم يتم العثور على بيانات الهوية في خدمة يقين.';
            throw new RuntimeException((string) $message);
        }

        return $this->normalize($person, $idType, $year, $month, $nationality);
    }

    /** @return array<string, mixed> */
    private function request(array $query, string $serviceIdentifier): array
    {
        $token = $this->token();
        $response = $this->client()
            ->withHeaders([
                'usage-code' => (string) config('services.yakeen.usage_code'),
                'operator-id' => (string) config('services.yakeen.operator_id'),
                'operation-ref' => (string) config('services.yakeen.operator_id'),
                'app-id' => (string) config('services.yakeen.app_id'),
                'app-key' => (string) config('services.yakeen.app_key'),
                'service-identifier' => $serviceIdentifier,
                'Authorization' => 'Bearer '.$token,
            ])
            ->get('/api/v1/yakeen/data', $query);

        if ($response->failed()) {
            throw new RuntimeException('تعذر الاتصال بخدمة يقين (HTTP '.$response->status().').');
        }

        return $response->json() ?: [];
    }

    private function token(): string
    {
        $cacheKey = 'legacy-yakeen-access-token';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->client()
            ->withHeaders([
                'app-id' => (string) config('services.yakeen.app_id'),
                'app-key' => (string) config('services.yakeen.app_key'),
            ])
            ->get('/api/v1/yakeen/login', [
                'Username' => (string) config('services.yakeen.username'),
                'Password' => (string) config('services.yakeen.password'),
            ]);

        $token = (string) ($response->json('access_token') ?? '');
        if ($response->failed() || $token === '') {
            Log::warning('Yakeen login failed', ['status' => $response->status()]);
            throw new RuntimeException('تعذر تسجيل الدخول في خدمة يقين.');
        }

        $expires = strtotime((string) ($response->json('expires_on') ?? '')) ?: (time() + 3600);
        Cache::put($cacheKey, $token, max(60, $expires - time() - 60));

        return $token;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.yakeen.base_url'))
            ->timeout((int) config('services.yakeen.timeout', 30))
            ->acceptJson()
            ->retry(2, 500, static fn ($exception): bool => $exception instanceof ConnectionException)
            ->withOptions([
                'curl' => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1],
            ]);
    }

    /** @return array<string, mixed> */
    private function personFromResponse(array $response, int $idType): array
    {
        $person = data_get($response, 'personBasicInfo');
        if (is_array($person)) {
            return $person;
        }

        if ($idType === 4) {
            foreach ($response as $value) {
                if (is_array($value) && (isset($value['firstName']) || isset($value['birthDateG']))) {
                    return $value;
                }
            }
        }

        return is_array($response) && (isset($response['firstName']) || isset($response['birthDateG'])) ? $response : [];
    }

    /** @return array<string, mixed> */
    private function normalize(array $person, int $idType, ?int $year, ?int $month, ?int $nationality): array
    {
        $arabic = $this->joinName($person, ['firstName', 'fatherName', 'grandFatherName', 'familyName']);
        $english = $this->joinName($person, ['firstNameT', 'fatherNameT', 'grandFatherNameT', 'familyNameT']);
        $birthDateG = (string) ($person['birthDateG'] ?? '');
        $dateType = $idType === 1 ? 1 : 2;
        $birthDay = $year;
        $birthMonth = $month;
        $birthYear = $year;

        if ($idType !== 1 && $birthDateG !== '') {
            $timestamp = strtotime($birthDateG);
            if ($timestamp !== false) {
                $birthDay = (int) date('d', $timestamp);
                $birthMonth = (int) date('m', $timestamp);
                $birthYear = (int) date('Y', $timestamp);
            }
        } elseif ($idType === 1) {
            $converted = (string) data_get($person, 'convertDate.dateString', '');
            $parts = explode('-', $converted);
            $birthDay = (int) ($parts[2] ?? 0);
        }

        return [
            'name_ar' => $arabic,
            'name_en' => $english,
            'nationality' => (int) ($person['nationalityCode'] ?? ($idType === 1 ? config('services.yakeen.saudi_nationality_code', 113) : ($nationality ?? 0))),
            'birth_day' => (int) $birthDay,
            'birth_month' => (int) $birthMonth,
            'birth_year' => (int) $birthYear,
            'date_type' => $dateType,
            'sex_code' => (int) ($person['sexCode'] ?? 0),
            'birth_date_g' => $birthDateG,
            'id_type' => $idType,
            'source' => 'yakeen',
        ];
    }

    private function joinName(array $person, array $fields): string
    {
        return trim(implode(' ', array_filter(array_map(
            static fn (string $field): string => trim((string) ($person[$field] ?? '')),
            $fields,
        ))));
    }

    private function yearMonth(?int $year, ?int $month): string
    {
        abort_if(!$year || !$month, 422, 'سنة وشهر الميلاد مطلوبان للاستعلام من يقين.');

        return sprintf('%04d-%02d', $year, $month);
    }

    private function ensureConfigured(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (!(bool) config('services.yakeen.enabled')) {
            throw new RuntimeException('خدمة يقين غير مفعّلة. اضبط YAQEEN_ENABLED=true بعد إضافة مفاتيح التكامل.');
        }

        foreach (['username', 'password', 'usage_code', 'operator_id', 'app_id', 'app_key'] as $key) {
            if ((string) config('services.yakeen.'.$key) === '') {
                throw new RuntimeException('إعدادات خدمة يقين غير مكتملة. راجع متغيرات YAQEEN_* في ملف البيئة.');
            }
        }
    }

    private function audit(int $idType, string $idNumber, array $query, array $response): void
    {
        try {
            if (!Schema::hasTable('yaqeen_service_transactions')) {
                return;
            }

            $errorMessage = data_get($response, 'errorDetail.errorMessage') ?: 'success';
            DB::table('yaqeen_service_transactions')->insert([
                'companies_groups_id' => (int) session('companies_groups_id', 0),
                'branch_id' => (int) session('hr_branch_id', 0),
                'param1' => $idNumber,
                'param2' => (string) ($query['dateString'] ?? $query['birthDateG'] ?? $query['nationality'] ?? $query['nationalityCode'] ?? ''),
                'type' => match ($idType) { 1 => 'SaudiByNin', 2 => 'byIgama', 3 => 'GccByNin', 4 => 'NonSaudiPassport', default => 'BorderNumber' },
                'errorMessage' => (string) $errorMessage,
                'created_by' => (int) session('hr_user_id', 0),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not write Yakeen audit row', ['message' => $e->getMessage()]);
        }
    }
}
