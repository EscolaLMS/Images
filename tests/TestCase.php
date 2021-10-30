<?php

namespace EscolaLms\Images\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use EscolaLms\Images\EscolaLmsImagesServiceProvider;
use Illuminate\Support\Facades\Storage;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [EscolaLmsImagesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {       
       
    }
}