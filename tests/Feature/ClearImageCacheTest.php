<?php

namespace EscolaLms\Images\Feature;

use EscolaLms\Images\Enum\ConstantEnum;
use EscolaLms\Images\Models\ImageCache;
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

    public function testClearImageCacheAfterDelete(): void
    {
        $img = UploadedFile::fake()->image('img.png');
        $path = $img->store('test');
        $hashPath = $img->storeAs(ConstantEnum::CACHE_DIRECTORY, sha1('hash_path_img1.png'));

        ImageCache::factory()->create([
            'path' => $path,
            'hash_path' => $hashPath,
        ]);

        Storage::assertExists($path);
        Storage::assertExists($hashPath);

        Storage::delete($path);

        Storage::assertMissing($hashPath);
        $this->assertDatabaseMissing('image_caches', [
            'path' => $path,
        ]);
    }

    public function testClearImageCacheByDirectory(): void
    {
        $img = UploadedFile::fake()->image('img.png');
        $path = $img->store('test');
        $hashPath = $img->storeAs(ConstantEnum::CACHE_DIRECTORY, sha1('hash_path_img1.png'));

        ImageCache::factory()->create([
            'path' => $path,
            'hash_path' => $hashPath,
        ]);

        Storage::assertExists($path);
        Storage::assertExists($hashPath);

        Storage::put('test', $img);

        Storage::assertMissing($hashPath);
        $this->assertDatabaseMissing('image_caches', [
            'path' => $path,
        ]);
    }
}
