<?php

namespace Tests\Feature;

use App\Services\Api\OtpService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twofactor.api_key' => 'secret-key',
            'services.twofactor.otp_template' => 'FlatCare',
        ]);
    }

    private function fake2Factor(string $verifyDetails = 'OTP Matched'): void
    {
        Http::fake([
            '2factor.in/*/SMS/VERIFY/*' => Http::response([
                'Status' => $verifyDetails === 'OTP Matched' ? 'Success' : 'Error',
                'Details' => $verifyDetails,
            ]),
            '2factor.in/*' => Http::response(['Status' => 'Success', 'Details' => 'f3a1c2d4-session-0001']),
        ]);
    }

    public function test_otp_is_sent_with_the_template_and_verifies_once(): void
    {
        $this->fake2Factor();

        $otp = app(OtpService::class);
        $otp->send('+91 98765 43210');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://2factor.in/API/V1/secret-key/SMS/919876543210/AUTOGEN/FlatCare');

        $this->assertTrue($otp->verify('9876543210', '123456'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/SMS/VERIFY/f3a1c2d4-session-0001/123456'));
        $this->assertFalse($otp->verify('9876543210', '123456'), 'a code is used up once matched');
    }

    public function test_session_is_dropped_after_too_many_wrong_guesses(): void
    {
        $this->fake2Factor('OTP Mismatch');

        $otp = app(OtpService::class);
        $otp->send('9876543210');

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($otp->verify('9876543210', '111111'));
        }

        Http::assertSentCount(6);
        $this->assertFalse($otp->verify('9876543210', '111111'));
        Http::assertSentCount(6); // no session left - 2Factor isn't asked again
    }

    public function test_resend_too_soon_is_refused(): void
    {
        $this->fake2Factor();

        $otp = app(OtpService::class);
        $otp->send('9876543210');

        $this->expectException(ValidationException::class);
        $otp->send('09876543210');
    }

    public function test_send_failure_is_a_friendly_error_and_keeps_the_key_out_of_logs(): void
    {
        Http::fake(['*' => Http::response(['Status' => 'Error', 'Details' => 'Invalid key secret-key'], 400)]);
        Log::spy();

        try {
            app(OtpService::class)->send('9876543210');
            $this->fail('Expected a ValidationException');
        } catch (ValidationException) {
        }

        Log::shouldHaveReceived('warning')->withArgs(
            fn ($message, $context) => !str_contains(json_encode($context), 'secret-key')
        );
    }

    public function test_invalid_numbers_and_codes_are_rejected_without_calling_2factor(): void
    {
        Http::fake();

        $this->assertNull(OtpService::normalize('12345'));
        $this->assertNull(OtpService::normalize('5876543210'));
        $this->assertSame('9876543210', OtpService::normalize('+91-98765-43210'));

        $this->assertFalse(app(OtpService::class)->verify('9876543210', 'abcd'));
        $this->assertFalse(app(OtpService::class)->verify('9876543210', '12'));
        Http::assertNothingSent();
    }

    public function test_without_an_api_key_the_fixed_dev_code_works_off_production(): void
    {
        config(['services.twofactor.api_key' => null]);

        $this->assertFalse(app(OtpService::class)->isLive());
        $this->assertTrue(app(OtpService::class)->verify('9876543210', (string) config('flatcare.otp_default_code')));
    }

    public function test_without_an_api_key_production_never_accepts_the_fixed_code(): void
    {
        config(['services.twofactor.api_key' => null]);
        $this->app['env'] = 'production';

        $this->assertFalse(app(OtpService::class)->verify('9876543210', (string) config('flatcare.otp_default_code')));
    }

    public function test_store_review_number_takes_its_fixed_code_in_production_without_an_sms(): void
    {
        $this->fake2Factor();
        config(['flatcare.otp_review_phone' => '9999999999', 'flatcare.otp_review_code' => '1234']);
        $this->app['env'] = 'production';

        $otp = app(OtpService::class);
        $otp->send('+91 99999 99999');
        $otp->send('9999999999'); // no resend cooldown either

        $this->assertTrue($otp->verify('9999999999', '1234'));
        $this->assertTrue($otp->verify('09999999999', '1234'), 'reusable for every review');
        $this->assertFalse($otp->verify('9999999999', '0000'));
        Http::assertNothingSent();

        $this->assertFalse($otp->verify('9876543210', '1234'), 'other numbers still go through 2Factor');
    }

    public function test_store_review_login_is_off_unless_both_number_and_code_are_set(): void
    {
        $this->fake2Factor('OTP Mismatch');
        config(['flatcare.otp_review_phone' => '9999999999', 'flatcare.otp_review_code' => '']);

        $otp = app(OtpService::class);
        $otp->send('9999999999');

        Http::assertSentCount(1);
        $this->assertFalse($otp->verify('9999999999', '0000'));
    }
}
