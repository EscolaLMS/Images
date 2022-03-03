<?php

namespace EscolaLms\Images\Console;

use EscolaLms\Images\Enum\ConstantEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearImagesCacheCommand extends Command
{
    protected $signature = 'images:cache-clear';
    protected $description = 'Clear images cache.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Storage::deleteDirectory(ConstantEnum::CACHE_DIRECTORY);
        $this->info('Images cache cleared!');
    }
}
