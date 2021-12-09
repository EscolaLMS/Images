<?php

namespace EscolaLms\Images\Tests\Api;

use EscolaLms\Images\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

class RateLimitImagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $path = Storage::disk('local')->path('imgcache');
        File::cleanDirectory($path);
        Config::set('images.private.rate_limit_global', 0);
        Config::set('images.private.rate_limit_per_ip', 0);
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('images.private.rate_limit_global', 0);
        $app['config']->set('images.private.rate_limit_global', 0);
    }



    public function test_rate_limit()
    {
        Config::set('images.private.rate_limit_global', 0);
        Config::set('images.private.rate_limit_per_ip', 0);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', ['path' => $path]);

        $response->assertStatus(429);
    }
}
