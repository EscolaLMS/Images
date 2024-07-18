<?php

namespace EscolaLms\Images\Services;

use EscolaLms\Images\Events\FileDeleted;
use EscolaLms\Images\Events\FileStored;
use Illuminate\Filesystem\FilesystemManager;

class CustomFilesystemManager extends FilesystemManager
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    // @phpstan-ignore-next-line
    public function delete($paths): bool
    {
        $result = parent::delete($paths);

        if ($result) {
            event(new FileDeleted($paths));
        }

        return $result;
    }

    // @phpstan-ignore-next-line
    public function put(string $path, $contents, mixed $options = []): string|bool
    {
        $result = parent::put($path, $contents, $options);

        $this->dispatchEventAfterPut($result);


        return $result;
    }

    // @phpstan-ignore-next-line
    public function putFile(string $path, $file, mixed $options = []): string|false
    {
        $result = parent::putFile($path, $file, $options);

        $this->dispatchEventAfterPut($result);

        return $result;
    }

    // @phpstan-ignore-next-line
    public function putFileAs(string $path, $file, $name, mixed $options = []): string|false
    {
        $result = parent::putFileAs($path, $file, $name, $options);

        $this->dispatchEventAfterPut($result);

        return $result;
    }

    private function dispatchEventAfterPut(string|false $result): void
    {
        if ($result) {
            event(new FileStored($result));
        }
    }
}
