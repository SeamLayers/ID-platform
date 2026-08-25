<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_is_persisted_before_the_sms_is_sent(): void
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $response = $this->postJson('/api/v1/auth/send-otp', [
            'country_code' => '+20',
            'phone' => '1012345678',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('verification_codes', [
            'country_code' => '+20',
            'phone_number' => '1012345678',
            'is_used' => false,
        ]);

        $this->assertDatabaseMissing('verification_codes', [
            'code' => null,
        ]);
    }
}
