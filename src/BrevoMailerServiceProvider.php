<?php

namespace Kreatif\BrevoMailer;

use Illuminate\Support\Facades\Mail;
use Kreatif\BrevoMailer\Brevo\BrevoApiClient;
use Kreatif\BrevoMailer\Brevo\BrevoTransport;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BrevoMailerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('brevo-mailer')
            ->hasConfigFile('brevo-mailer');
    }

    public function packageBooted(): void
    {
        Mail::extend('brevo', function (array $config) {
            $client = new BrevoApiClient(
                apiKey: $config['api_key'] ?? config('brevo-mailer.api_key'),
                baseUri: $config['base_uri'] ?? config('brevo-mailer.base_uri'),
                timeout: $config['timeout'] ?? config('brevo-mailer.timeout'),
            );

            return new BrevoTransport($client);
        });
    }
}
