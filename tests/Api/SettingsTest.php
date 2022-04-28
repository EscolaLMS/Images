<?php

namespace EscolaLms\Images\Tests\Api;

use EscolaLms\Core\Tests\CreatesUsers;
use EscolaLms\Images\Enum\PackageStatusEnum;
use EscolaLms\Images\Providers\SettingsServiceProvider;
use EscolaLms\Images\Tests\TestCase;
use EscolaLms\Settings\Database\Seeders\PermissionTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsTest extends TestCase
{
    use CreatesUsers, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\EscolaLms\Settings\EscolaLmsSettingsServiceProvider::class)) {
            $this->markTestSkipped('Settings package not installed');
        }

        $this->seed(PermissionTableSeeder::class);
    }

    public function testAdministrableConfigApi(): void
    {
        $configKey = SettingsServiceProvider::CONFIG_KEY;
        $user = $this->makeAdmin();

        $this->response = $this->actingAs($user, 'api')->json(
            'POST',
            '/api/admin/config',
            [
                'config' => [
                    [
                        'key' => "{$configKey}.private.rate_limiter_status",
                        'value' => PackageStatusEnum::ENABLED,
                    ],
                    [
                        'key' => "{$configKey}.private.rate_limit_global",
                        'value' => 100,
                    ],
                    [
                        'key' => "{$configKey}.private.rate_limit_per_ip",
                        'value' => 40,
                    ],
                ]
            ]
        );
        $this->response->assertOk();

        $this->response = $this->actingAs($user, 'api')->json(
            'GET',
            '/api/admin/config'
        )->assertOk();

        $this->response->assertJsonFragment([
            $configKey => [
                'private' => [
                    'rate_limiter_status' => [
                        'full_key' => "$configKey.private.rate_limiter_status",
                        'key' => 'private.rate_limiter_status',
                        'rules' => [
                            'required',
                            'string',
                            'in:' . implode(',', PackageStatusEnum::getValues())
                        ],
                        'public' => false,
                        'value' => PackageStatusEnum::ENABLED,
                        'readonly' => false,
                    ],
                    'rate_limit_global' => [
                        'full_key' => "$configKey.private.rate_limit_global",
                        'key' => 'private.rate_limit_global',
                        'rules' => [
                            'required',
                            'numeric',
                        ],
                        'public' => false,
                        'value' => 100,
                        'readonly' => false,
                    ],
                    'rate_limit_per_ip' => [
                        'full_key' => "$configKey.private.rate_limit_per_ip",
                        'key' => 'private.rate_limit_per_ip',
                        'rules' => [
                            'required',
                            'numeric',
                        ],
                        'public' => false,
                        'value' => 40,
                        'readonly' => false,
                    ],
                ],
            ],
        ]);

        $this->response = $this->json(
            'GET',
            '/api/config'
        )->assertJsonMissing([
            'rate_limiter_status' => PackageStatusEnum::ENABLED,
            'rate_limit_global' => 100,
            'rate_limit_per_ip' => 40,
        ]);
    }
}
