<?php

namespace EscolaLms\Images\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use EscolaLms\Images\EscolaLmsImagesServiceProvider;
use Illuminate\Support\Facades\Config;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('images.private.rate_limit_global', 100);
        Config::set('images.private.rate_limit_per_ip', 100);
    }

    protected function getPackageProviders($app)
    {
        return [EscolaLmsImagesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
    }
}
