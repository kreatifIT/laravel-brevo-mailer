<?php

namespace Kreatif\BrevoMailer\Tests;

use Kreatif\BrevoMailer\BrevoMailerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            BrevoMailerServiceProvider::class,
        ];
    }
}
