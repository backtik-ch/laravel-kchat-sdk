<?php

namespace Backtik\KChat;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class KChatServiceProvider extends PackageServiceProvider
{
    public function packageRegistered(): void
    {
        $this->app->singleton(KChatClient::class, fn (): KChatClient => new KChatClient(config('kchat-sdk', [])));
        $this->app->singleton(KChat::class, fn (): KChat => new KChat($this->app->make(KChatClient::class)));
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-kchat-sdk')
            ->hasConfigFile();
    }
}
