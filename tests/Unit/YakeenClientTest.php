<?php

namespace Tests\Unit;

use App\Services\LegacyWorkflows\YakeenClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class YakeenClientTest extends TestCase
{
    public function test_transport_errors_are_returned_as_a_safe_arabic_message(): void
    {
        Http::fake(static function (): never {
            throw new ConnectionException('connection reset');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('تعذر الاتصال بخدمة يقين من السيرفر');

        app(YakeenClient::class)->lookup(1, '0000000000', 1405, 8);
    }
}
