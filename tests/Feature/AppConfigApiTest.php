<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use Tests\TestCase;

class AppConfigApiTest extends TestCase
{
    public function test_app_config_is_public_and_returns_the_super_admin_contact_details(): void
    {
        $contact = PlatformSetting::contact();

        $response = $this->getJson('/api/v1/app-config');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.support.email', $contact['email'])
            ->assertJsonCount(count($contact['phones']), 'data.support.phones')
            ->assertJsonStructure(['data' => ['support' => ['email', 'phones' => [['display', 'dial', 'whatsapp']]]]]);

        foreach ($response->json('data.support.phones') as $i => $phone) {
            $this->assertSame($contact['phones'][$i], $phone['display']);
            $this->assertMatchesRegularExpression('/^\+\d{10,15}$/', $phone['dial']);
            $this->assertMatchesRegularExpression('/^\d{10,15}$/', $phone['whatsapp']);
        }
    }
}
