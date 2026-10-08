<?php

namespace Tests\Feature;

use App\Services\Api\OtpService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'flatcare.otp_channel' => 'whatsapp',
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '12345',
            'services.whatsapp.otp_template' => 'flatcare_otp',
            'services.twofactor.api_key' => null,
        ]);
    }

    public function test_whatsapp_otp_is_sent_with_the_template_and_verifies_once(): void
    {
        $sentCode = null;
        Http::fake(function (Request $request) use (&$sentCode) {
            $sentCode = $request['template']['components'][0]['parameters'][0]['text'];

            return Http::response(['messages' => [['id' => 'wamid.1']]]);
        });

        $otp = app(OtpService::class);
        $otp->send('+91 98765 43210');

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/12345/messages')
            && $r['to'] === '919876543210'
            && $r['template']['name'] === 'flatcare_otp'
            && $r->hasHeader('Authorization', 'Bearer test-token'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sentCode);

        $this->assertFalse($otp->verify('9876543210', $sentCode === '000000' ? '111111' : '000000'));
        $this->assertTrue($otp->verify('9876543210', $sentCode));
        $this->assertFalse($otp->verify('9876543210', $sentCode), 'a code is used up once matched');
    }

    public function test_whatsapp_code_is_dropped_after_too_many_wrong_guesses(): void
    {
        $sentCode = null;
        Http::fake(function (Request $request) use (&$sentCode) {
            $sentCode = $request['template']['components'][0]['parameters'][0]['text'];

            return Http::response(['messages' => [['id' => 'wamid.1']]]);
        });

        $otp = app(OtpService::class);
        $otp->send('9876543210');
        $wrong = $sentCode === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($otp->verify('9876543210', $wrong));
        }
        $this->assertFalse($otp->verify('9876543210', $sentCode));
    }

    public function test_whatsapp_send_failure_is_a_friendly_validation_error(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Template not found']], 400)]);

        $this->expectException(ValidationException::class);
        app(OtpService::class)->send('9876543210');
    }

    public function test_sms_channel_still_goes_through_2factor(): void
    {
        config(['flatcare.otp_channel' => 'sms', 'services.twofactor.api_key' => 'k']);
        Http::fake([
            '2factor.in/*/SMS/VERIFY/*' => Http::response(['Status' => 'Success']),
            '2factor.in/*' => Http::response(['Status' => 'Success', 'Details' => 'sess-1']),
        ]);

        $otp = app(OtpService::class);
        $this->assertSame('sms', $otp->channel());
        $otp->send('9876543210');
        $this->assertTrue($otp->verify('9876543210', '1234'));

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'graph.facebook.com'));
    }

    public function test_without_whatsapp_credentials_a_2factor_key_keeps_sms_going(): void
    {
        config(['services.whatsapp.token' => null, 'services.twofactor.api_key' => 'k']);

        $this->assertSame('sms', app(OtpService::class)->channel());
        $this->assertTrue(app(OtpService::class)->isLive());
    }

    public function test_without_whatsapp_credentials_the_fixed_dev_code_works(): void
    {
        config(['services.whatsapp.token' => null]);

        $this->assertSame('whatsapp', app(OtpService::class)->channel());
        $this->assertFalse(app(OtpService::class)->isLive());
        $this->assertTrue(app(OtpService::class)->verify('9876543210', (string) config('flatcare.otp_default_code')));
    }
}
