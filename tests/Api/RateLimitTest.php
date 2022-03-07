<?php

namespace Api;

use EscolaLms\Images\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

class RateLimitTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $path = Storage::path('imgcache');
        File::cleanDirectory($path);
        Config::set('escola_settings.use_database', true);
        Config::set('images.private.rate_limit_global', 0);
        Config::set('images.private.rate_limit_per_ip', 0);
    }

    public function test_rate_limit()
    {
        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $storagePath = Storage::path($filename);

        copy($filepath, $storagePath);
        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', ['path' => $path]);

        $response->assertStatus(429);
    }
}
