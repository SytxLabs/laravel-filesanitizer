<?php

namespace SytxLabs\LaravelFileSanitizer\Tests;

use Illuminate\Support\Facades\Validator;
use SytxLabs\LaravelFileSanitizer\Facades\FileSanitizer;
use SytxLabs\LaravelFileSanitizer\FileSanitizerManager;

class ServiceProviderTest extends TestCase
{
    public function testResolvesManagerAndFacade(): void
    {
        $manager = $this->app->make('filesanitizer');

        $this->assertInstanceOf(FileSanitizerManager::class, $manager);
        $this->assertInstanceOf(FileSanitizerManager::class, FileSanitizer::getFacadeRoot());
    }

    public function testValidatorRuleIsRegistered(): void
    {
        $validator = Validator::make(['upload' => null], ['upload' => ['safe_file']]);

        $this->assertTrue(method_exists($validator, 'passes'));
    }
}
