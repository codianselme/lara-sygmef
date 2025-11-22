<?php

namespace Codianselme\LaraSygmef\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Codianselme\LaraSygmef\Providers\EmecfServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            EmecfServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('emecf.token', 'test-token');
    }
}
