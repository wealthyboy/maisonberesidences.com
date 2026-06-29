<?php

namespace App\Providers;

use App\Mail\ZeptoMailTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class ZeptoMailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(MailManager::class)->extend('zeptomail', function (array $config): ZeptoMailTransport {
            return new ZeptoMailTransport(
                (string) Arr::get($config, 'key', config('mail.mailers.zeptomail.key')),
                (string) Arr::get($config, 'endpoint', config('mail.mailers.zeptomail.endpoint')),
            );
        });
    }
}
