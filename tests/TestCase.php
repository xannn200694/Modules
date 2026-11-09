<?php

namespace Xannn94\Modules\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Xannn94\Modules\Facades\Module;
use Xannn94\Modules\ModulesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Module' => Module::class,
        ];
    }
}
