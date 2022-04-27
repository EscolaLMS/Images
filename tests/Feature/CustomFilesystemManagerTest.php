<?php

namespace EscolaLms\Images\Feature;

use EscolaLms\Images\Events\FileDeleted;
use EscolaLms\Images\Events\FileStored;
use EscolaLms\Images\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class CustomFilesystemManagerTest extends TestCase
{
    public function testDeleteFile(): void
    {
        Storage::fake();
        Event::fake([FileDeleted::class]);
        $path = UploadedFile::fake()->image('test.png')->store('test.png');
        Storage::assertExists($path);

        Storage::delete($path);
        Storage::assertMissing($path);
        Event::assertDispatched(FileDeleted::class, function (FileDeleted $event) use ($path) {
            $this->assertEquals($path, $event->getPath());
            return true;
        });
    }

    public function testPut(): void
    {
        Storage::fake();
        Event::fake([FileStored::class]);
        $file = UploadedFile::fake()->image('test.png');

        $path = Storage::put('test', $file);
        Storage::assertExists($path);
        Event::assertDispatched(FileStored::class, function (FileStored $event) use ($path) {
            $this->assertEquals($path, $event->getPath());
            return true;
        });
    }

    public function testPutFile(): void
    {
        Storage::fake();
        Event::fake([FileStored::class]);
        $file = UploadedFile::fake()->image('test.png');

        $path = Storage::putFile('test', $file);
        Storage::assertExists($path);
        Event::assertDispatched(FileStored::class, function (FileStored $event) use ($path) {
            $this->assertEquals($path, $event->getPath());
            return true;
        });
    }

    public function testPutFileAs(): void
    {
        Storage::fake();
        Event::fake([FileStored::class]);
        $file = UploadedFile::fake()->image('test.png');

        $path = Storage::putFileAs('test', $file, 'test123.png');
        Storage::assertExists($path);
        Event::assertDispatched(FileStored::class, function (FileStored $event) use ($path) {
            $this->assertEquals($path, $event->getPath());
            return true;
        });
    }
}
