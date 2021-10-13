<?php

namespace EscolaLms\Images\Tests\Api;

use Illuminate\Support\Facades\Storage;
use EscolaLms\Images\Tests\TestCase;

class ContentApiTest extends TestCase
{
    public function test_image_get_redirect()
    {
        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__.'/'.$filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 100;

        $params = [           
            'w' => $width
        ];
    
        $response = $this->call('GET', '/api/images/img', array_merge($params, ['path'=>$path]));

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 
        $hash = sha1($path.json_encode($params));

        $cachedImageUrl = $response->getTargetUrl();

        $this->assertStringContainsString($hash, $cachedImageUrl);

        $output_file = 'imgcache/'.$hash.'.jpg'; 

        $output_path = $disk->path($output_file);

        $sizes = getimagesize($output_path);

        $this->assertEquals($sizes[0], $width);

    }

    public function test_image_post_results()
    {
        $filename = $path =  'test.jpg';
        $filepath = realpath(__DIR__.'/'.$filename);

        $disk = Storage::disk('local');
        $storage_path = $disk->path($filename);

        copy($filepath, $storage_path);

        $width = 100;

        $params = [           
            'w' => $width
        ];

        $json = [
            "paths"=> [
                [
                    "path"=> "test.jpg",
                    "params"=> [
                        "w"=> 100
                    ]
                ], [
                    "path"=>  "test.jpg",
                    "params"=> [
                        "w"=> 200
                    ]
                ], [
                    "path"=> "test.jpg",
                    "params"=> [
                        "w"=> 300
                    ]
                ]
            ]
        ];
    
        $response = $this->postJson('/api/images/img', $json);

        $response->assertOk();

        // THIS is crutial becuase frontend is using the same algoritm to guess cached URL 

        $response->assertJsonFragment(['hash' => sha1($json['paths'][0]['path'].json_encode($json['paths'][0]['params']))]);
        $response->assertJsonFragment(['hash' => sha1($json['paths'][1]['path'].json_encode($json['paths'][1]['params']))]);
        $response->assertJsonFragment(['hash' => sha1($json['paths'][2]['path'].json_encode($json['paths'][2]['params']))]);



    }

    
}