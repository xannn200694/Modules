<?php

namespace DummyNamespace\Infrastructure\Providers;

use DummyNamespace\Domain\Contracts\DummyBasenameRepository;
use DummyNamespace\Infrastructure\Persistence\EloquentDummyBasenameRepository;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    public $module = 'DummySlug';

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(DummyBasenameRepository::class, EloquentDummyBasenameRepository::class);
    }

    /**
     * Bootstrap the module services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../View/Views', 'DummySlug');
        $this->loadTranslationsFrom(__DIR__ . '/../View/Lang', 'DummySlug');
    }
}
