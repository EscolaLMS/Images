<?php

namespace EscolaLms\Images\Tests\Mock;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestImageChanged
{
    use Dispatchable, SerializesModels;

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
