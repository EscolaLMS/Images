<?php

namespace Api;

use EscolaLms\Images\Events\FileStored;
use EscolaLms\Images\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

class ImagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $path = Storage::disk('local')->path('imgcache');
        File::cleanDirectory($path);
        Config::set('images.private.rate_limit_global', 100);
        Config::set('images.private.rate_limit_per_ip', 100);
    }

    public function test_image_get_redirect(): void
    {
        $filename = $path = 'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        $sizes_original = getimagesize($filepath);

        copy($filepath, $storage_path);

        $response = $this->call('GET', '/api/images/img', ['path' => $path]);

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode([]));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes_original[0], $sizes[0]);
        $this->assertEquals($sizes_original[1], $sizes[1]);
        Storage::exists($output_path);
    }

    public function test_image_post_results(): void
    {
        $filename = 'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $json = [
            "paths" => [
                [
                    "path" => "test.jpg",
                    "params" => [
                        "w" => 100
                    ]
                ], [
                    "path" =>  "test.jpg",
                    "params" => [
                        "w" => 200
                    ]
                ], [
                    "path" => "test.jpg",
                    "params" => [
                        "w" => 300
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/images/img', $json);

        $response->assertOk();

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $response->assertJsonFragment(['hash' => $this->getHash($json, 0)]);
        $response->assertJsonFragment(['hash' => $this->getHash($json, 1)]);
        $response->assertJsonFragment(['hash' => $this->getHash($json, 2)]);

        Storage::assertExists('imgcache/' . $this->getHash($json, 0) . '.jpg');
        Storage::assertExists('imgcache/' . $this->getHash($json, 1) . '.jpg');
        Storage::assertExists('imgcache/' . $this->getHash($json, 2) . '.jpg');

        $this->assertDatabaseHas('image_caches', [
            'path' => 'test.jpg',
            'hash_path' => 'imgcache/' . $this->getHash($json, 0) . '.jpg',
        ]);

        $this->assertDatabaseHas('image_caches', [
            'path' => 'test.jpg',
            'hash_path' => 'imgcache/' . $this->getHash($json, 1) . '.jpg',
        ]);

        $this->assertDatabaseHas('image_caches', [
            'path' => 'test.jpg',
            'hash_path' => 'imgcache/' . $this->getHash($json, 2) . '.jpg',
        ]);
    }

    public function test_invalid_image_get_redirect(): void
    {
        $filename = $path =  'invalid.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);
        copy($filepath, $storage_path);

        $response = $this->call('GET', '/api/images/img', ['path' => $path]);

        $hash = sha1($path . json_encode([]));
        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '_error.svg';
        $output_path = $disk->path($output_file);
        $contents = file_get_contents($output_path);

        Storage::exists($output_path);
        $this->assertNotFalse(strpos($contents, 'Error: Unable to init from given binary data.'));
    }

    public function test_invalid_image_post_results(): void
    {
        Event::fake([FileStored::class]);
        $disk = Storage::disk('local');

        $invalidFileName = 'invalid.jpg';
        $fileName = 'test.jpg';
        $invalidFilePath = realpath(__DIR__ . '/' . $invalidFileName);
        $filePath = realpath(__DIR__ . '/' . $fileName);

        $invalidFileStoragePath = $disk->path($invalidFileName);
        $storagePath = $disk->path($fileName);
        copy($invalidFilePath, $invalidFileStoragePath);
        copy($filePath, $storagePath);

        $json = [
            "paths" => [
                [
                    "path" => $fileName,
                    "params" => [
                        "w" => 100
                    ]
                ], [
                    "path" => $invalidFileName,
                    "params" => [
                        "w" => 200
                    ]
                ], [
                    "path" => $fileName,
                    "params" => [
                        "w" => 300
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/images/img', $json);
        $response->assertOk();

        $response->assertJsonFragment(['hash' => $this->getHash($json, 0)]);
        $response->assertJsonFragment(['hash' => $this->getHash($json, 1)]);
        $response->assertJsonFragment(['hash' => $this->getHash($json, 2)]);

        $response->assertJsonFragment(['path' => 'imgcache/' . $this->getHash($json, 0) . '.jpg']);
        $response->assertJsonFragment(['path' => 'imgcache/' . $this->getHash($json, 1) . '_error.svg']);
        $response->assertJsonFragment(['path' => 'imgcache/' . $this->getHash($json, 2) . '.jpg']);

        Storage::assertExists('imgcache/' . $this->getHash($json, 0) . '.jpg');
        Storage::assertExists('imgcache/' . $this->getHash($json, 1) . '_error.svg');
        Storage::assertExists('imgcache/' . $this->getHash($json, 2) . '.jpg');
        Event::assertDispatched(FileStored::class);
    }

    public function test_max_width(): void
    {
        $max_width = 500;
        Config::set('images.public.max_width', $max_width);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 1000;
        $params = [
            'w' => $width
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes[0], $max_width);
        $this->assertNotEquals($sizes[0], $width);
    }

    public function test_min_width(): void
    {
        $min_width = 100;
        Config::set('images.public.min_width', $min_width);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = -1;
        $params = [
            'w' => $width
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes[0], $min_width);
        $this->assertNotEquals($sizes[0], $width);
    }

    public function test_predefined_sizes(): void
    {
        Config::set('images.public.size_definitions', [
            'thumbnail' => [
                'w' => 400,
                'h' => 300
            ]
        ]);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $params = [
            'size' => 'thumbnail',
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial because frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        // Less than or equal because aspect ratio is always preserved
        $this->assertEquals(400, $sizes[0]);
        $this->assertLessThanOrEqual(300, $sizes[1]);
    }

    public function test_max_height(): void
    {
        $max_height = 200;
        Config::set('images.public.max_height', $max_height);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $height = 1000;
        $params = [
            'h' => $height
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($max_height, $sizes[1]);
        $this->assertNotEquals($height, $sizes[1]);
    }

    public function test_min_height(): void
    {
        $min_height = 100;
        Config::set('images.public.min_height', $min_height);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $height = -1;
        $params = [
            'h' => $height
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($min_height, $sizes[1]);
        $this->assertNotEquals($height, $sizes[1]);
    }

    public function test_prevent_upscale(): void
    {
        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $sizes_original = getimagesize($filepath);

        $width = $sizes_original[0] * 2;
        $params = [
            'w' => $width
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes_original[0], $sizes[0]);
        $this->assertNotEquals($width, $sizes[0]);
    }

    public function test_allowed_width(): void
    {
        $allowed_width = 300;
        Config::set('images.public.allowed_widths', [$allowed_width]);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 400;
        $params = [
            'w' => $width,
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($allowed_width, $sizes[0]);
        $this->assertNotEquals($width, $sizes[0]);
    }

    public function test_allowed_height(): void
    {
        $allowed_height = 200;
        Config::set('images.public.allowed_heights', [$allowed_height]);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $height = 250;
        $params = [
            'h' => $height,
        ];

        /** @var TestResponse $response */
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL
        $hash = sha1($path . json_encode($params));

        $response->assertRedirectContains($hash);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($allowed_height, $sizes[1]);
        $this->assertNotEquals($height, $sizes[1]);
    }

    public function test_convert_jpg_to_png(): void
    {
        $filename = 'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $response = $this->getJson('/api/images/img?format=png&path=' . $filename);

        $hash = sha1($filename . json_encode(['format' => 'png']));
        $response->assertRedirectContains($hash . '.png');
    }

    private function getHash($json, $index): string
    {
        return sha1($json['paths'][$index]['path'] . json_encode($json['paths'][$index]['params']));
    }
}
