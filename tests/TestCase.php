<?php

namespace Minishop\Tests;

use Minishop\MinishopServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MinishopServiceProvider::class,
        ];
    }
}
