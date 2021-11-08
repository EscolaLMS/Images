<?php

namespace EscolaLms\Images\Tests\Api;

use Illuminate\Support\Facades\Storage;
use EscolaLms\Images\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ContentApiTest extends TestCase
{
    public function test_image_get_redirect()
    {
        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 100;

        $params = [
            'w' => $width
        ];

        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 
        $hash = sha1($path . json_encode($params));

        $cachedImageUrl = $response->getTargetUrl();

        $this->assertStringContainsString($hash, $cachedImageUrl);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes[0], $width);
    }

    public function test_image_post_results()
    {
        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 100;

        $params = [
            'w' => $width
        ];

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

        $response->assertJsonFragment(['hash' => sha1($json['paths'][0]['path'] . json_encode($json['paths'][0]['params']))]);
        $response->assertJsonFragment(['hash' => sha1($json['paths'][1]['path'] . json_encode($json['paths'][1]['params']))]);
        $response->assertJsonFragment(['hash' => sha1($json['paths'][2]['path'] . json_encode($json['paths'][2]['params']))]);
    }

    public function test_max_width()
    {
        $max_width = 500;
        Config::set('images.max_width', $max_width);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 1000;
        $params = [
            'w' => $width
        ];

        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 
        $hash = sha1($path . json_encode($params));

        $cachedImageUrl = $response->getTargetUrl();

        $this->assertStringContainsString($hash, $cachedImageUrl);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes[0], $max_width);
        $this->assertNotEquals($sizes[0], $width);
    }


    public function test_min_width()
    {
        $min_width = 100;
        Config::set('images.min_width', $min_width);

        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__ . '/' . $filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = -1;
        $params = [
            'w' => $width
        ];

        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 
        $hash = sha1($path . json_encode($params));

        $cachedImageUrl = $response->getTargetUrl();

        $this->assertStringContainsString($hash, $cachedImageUrl);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes[0], $min_width);
        $this->assertNotEquals($sizes[0], $width);
    }

    public function test_predefined_sizes()
    {
        Config::set('images.size_definitions', [
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

        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 
        $hash = sha1($path . json_encode($params));

        $cachedImageUrl = $response->getTargetUrl();

        $this->assertStringContainsString($hash, $cachedImageUrl);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        // Less than or equal because aspect ratio is always preserved
        $this->assertEquals(400, $sizes[0]);
        $this->assertLessThanOrEqual(300, $sizes[1]);
    }

    public function test_prevent_upscale()
    {
        Config::set('images.allow_upscale', false);

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

        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path' => $path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 
        $hash = sha1($path . json_encode($params));

        $cachedImageUrl = $response->getTargetUrl();

        $this->assertStringContainsString($hash, $cachedImageUrl);

        $output_file = 'imgcache/' . $hash . '.jpg';

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes_original[0], $sizes[0]);
        $this->assertNotEquals($width, $sizes[0]);
    }
}
