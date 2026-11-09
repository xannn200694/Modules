<?php

namespace Xannn94\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $generators = [
            'command.make.module'            => \Xannn94\Modules\Console\Generators\MakeModuleCommand::class,
            'command.make.module.controller' => \Xannn94\Modules\Console\Generators\MakeControllerCommand::class,
            'command.make.module.middleware' => \Xannn94\Modules\Console\Generators\MakeMiddlewareCommand::class,
            'command.make.module.migration'  => \Xannn94\Modules\Console\Generators\MakeMigrationCommand::class,
            'command.make.module.model'      => \Xannn94\Modules\Console\Generators\MakeModelCommand::class,
            'command.make.module.policy'     => \Xannn94\Modules\Console\Generators\MakePolicyCommand::class,
            'command.make.module.provider'   => \Xannn94\Modules\Console\Generators\MakeProviderCommand::class,
            'command.make.module.request'    => \Xannn94\Modules\Console\Generators\MakeRequestCommand::class,
            'command.make.module.seeder'     => \Xannn94\Modules\Console\Generators\MakeSeederCommand::class,
        ];

        foreach ($generators as $slug => $class) {
            $this->app->singleton($slug, function ($app) use ($class) {
                return $app[$class];
            });

            $this->commands($slug);
        }
    }
}
