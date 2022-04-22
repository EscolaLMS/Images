<?php

namespace EscolaLms\Images\Feature;

use EscolaLms\Images\Models\ImageCache;
use EscolaLms\Images\Tests\Mock\TestImageChanged;
use EscolaLms\Images\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ClearImageCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
    }

    public function testClearImageCacheByPath(): void
    {
        $img = UploadedFile::fake()->image('img.png');
        $hashPath1 = $img->storeAs('/', sha1('hash_path_img1.png'));
        $hashPath2 = $img->storeAs('/', 'hash_path_img2.png');

        ImageCache::factory()->create([
            'path' => 'img.png',
            'hash_path' => $hashPath1,
        ]);

        ImageCache::factory()->create([
            'path' => 'img.png',
            'hash_path' => $hashPath2,
        ]);

        Storage::assertExists($hashPath1);
        Storage::assertExists($hashPath2);
        $this->assertDatabaseHas('image_caches', [
            'path' => 'img.png',
        ]);

        event(new TestImageChanged('img.png'));

        Storage::assertMissing($hashPath1);
        Storage::assertMissing($hashPath2);
        $this->assertDatabaseMissing('image_caches', [
            'path' => 'img.png',
        ]);
    }
}
