<?php

namespace SytxLabs\LaravelFileSanitizer\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SytxLabs\LaravelFileSanitizer\FileSanitizerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [FileSanitizerServiceProvider::class];
    }
}
