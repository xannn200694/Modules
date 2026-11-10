<?php

namespace Xannn94\Modules\Tests;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Xannn94\Modules\ModulesServiceProvider;

class ModuleScaffoldTest extends TestCase
{
    private string $modulesPath = '';

    protected function tearDown(): void
    {
        $this->deleteTree($this->modulesPath);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->modulesPath = $app->basePath('testing-modules-'.bin2hex(random_bytes(3)));
        mkdir($this->modulesPath, 0777, true);

        $app['config']->set('modules.path', $this->modulesPath);
        $app['config']->set('modules.namespace', 'App\\Modules\\');
        $app['config']->set('modules.driver', 'local');
        $app['config']->set('modules.enabled', true);
        $app['config']->set('modules.pathMap', []);

        $cache = storage_path('app/modules.json');
        if (is_file($cache)) {
            unlink($cache);
        }
    }

    public function test_quick_module_builds_the_clean_architecture_tree(): void
    {
        $this->app->register(ModulesServiceProvider::class);

        $this->artisan('make:module', ['slug' => 'user', '--quick' => true])->assertSuccessful();
        $this->artisan('make:module', ['slug' => 'order', '--quick' => true])->assertSuccessful();

        $user = $this->modulesPath.'/User';
        $order = $this->modulesPath.'/Order';

        $this->assertModuleTree($user, 'User', 'ListUsers', 'users');
        $this->assertModuleTree($order, 'Order', 'ListOrders', 'orders');
        $this->assertUseCaseBehavior();
    }

    public function test_wizard_matches_the_quick_tree(): void
    {
        $this->app->register(ModulesServiceProvider::class);

        $this->artisan('make:module', ['slug' => 'user'])
            ->expectsQuestion('Please enter the name of the module:', 'User')
            ->expectsQuestion('Please enter the slug for the module:', 'user')
            ->expectsQuestion('Please enter the module version:', '1.0')
            ->expectsQuestion('Please enter the description of the module:', 'Users module')
            ->expectsConfirmation('If the provided information is correct, type "yes" to generate.', 'yes')
            ->assertSuccessful();

        $user = $this->modulesPath.'/User';
        $this->assertModuleTree($user, 'User', 'ListUsers', 'users');
        $this->assertDirectoryDoesNotExist($user.'/Models');
    }

    public function test_generators_write_into_the_new_layers(): void
    {
        $this->app->register(ModulesServiceProvider::class);
        $this->artisan('make:module', ['slug' => 'user', '--quick' => true])->assertSuccessful();

        $this->artisan('make:module:controller', [
            'slug' => 'user',
            'name' => 'ShowController',
        ])->assertSuccessful();
        $this->artisan('make:module:model', [
            'slug' => 'user',
            'name' => 'Profile',
        ])->assertSuccessful();
        $this->artisan('make:module:migration', [
            'slug' => 'user',
            'name' => 'add_bio_to_users',
        ])->assertSuccessful();

        $root = $this->modulesPath.'/User';
        $controller = $root.'/Infrastructure/Http/Controllers/ShowController.php';
        $model = $root.'/Infrastructure/Persistence/Eloquent/Profile.php';

        $this->assertFileExists($controller);
        $this->assertStringContainsString('Infrastructure\\Http\\Controllers', (string) file_get_contents($controller));
        $this->assertFileExists($model);
        $this->assertStringContainsString(
            'Infrastructure\\Persistence\\Eloquent',
            (string) file_get_contents($model)
        );
        $this->assertNotEmpty(glob($root.'/Infrastructure/Persistence/Migrations/*add_bio_to_users.php'));
    }

    private function assertModuleTree(string $root, string $basename, string $list, string $table): void
    {
        $this->assertFileExists($root.'/module.json');
        $this->assertFileExists($root.'/Domain/Model/'.$basename.'.php');
        $this->assertFileExists($root.'/Domain/Contracts/'.$basename.'Repository.php');
        $this->assertFileExists($root.'/Application/UseCase/'.$list.'/'.$list.'UseCase.php');
        $this->assertFileExists($root.'/Application/UseCase/'.$list.'/'.$list.'Request.php');
        $this->assertFileExists($root.'/Application/UseCase/'.$list.'/'.$list.'Result.php');
        $this->assertFileExists($root.'/Infrastructure/Persistence/Eloquent/'.$basename.'Model.php');
        $this->assertFileExists($root.'/Infrastructure/Persistence/Eloquent'.$basename.'Repository.php');
        $this->assertFileExists($root.'/Infrastructure/Providers/ModuleServiceProvider.php');
        $this->assertFileExists($root.'/Infrastructure/Http/Routes/web.php');
        $this->assertFileExists($root.'/Infrastructure/Http/Controllers/Admin/IndexController.php');
        $this->assertFileExists($root.'/Infrastructure/Config/menu.php');
        $this->assertFileExists($root.'/Infrastructure/Config/settings.php');
        $this->assertFileExists($root.'/Infrastructure/View/Views/index.blade.php');
        $this->assertDirectoryExists($root.'/Infrastructure/Persistence/Factories');
        $this->assertDirectoryExists($root.'/Infrastructure/Persistence/Seeders');

        $migration = (string) file_get_contents(
            $root.'/Infrastructure/Persistence/Migrations/2026_01_01_000000_create_'.$table.'_table.php'
        );
        $this->assertStringContainsString("Schema::create('".$table."'", $migration);
        $this->assertStringContainsString("\$table->string('name')", $migration);
        $this->assertStringContainsString("\$table->string('email')", $migration);

        $provider = (string) file_get_contents($root.'/Infrastructure/Providers/ModuleServiceProvider.php');
        $this->assertStringContainsString(
            'namespace App\\Modules\\'.$basename.'\\Infrastructure\\Providers;',
            $provider
        );

        $controller = (string) file_get_contents($root.'/Infrastructure/Http/Controllers/IndexController.php');
        $this->assertStringContainsString($list.'UseCase', $controller);
        $this->assertStringNotContainsString($basename.'Model', $controller);
        $this->assertStringNotContainsString('::query(', $controller);

        $web = (string) file_get_contents($root.'/Infrastructure/Http/Routes/web.php');
        $this->assertStringContainsString('Route::localizedGroup', $web);

        foreach (['Http', 'Models', 'Database', 'Providers', 'Routes', 'Resources'] as $legacy) {
            $this->assertDirectoryDoesNotExist($root.'/'.$legacy);
        }
    }

    private function assertUseCaseBehavior(): void
    {
        $root = $this->modulesPath.'/User';
        $this->requireOnce($root.'/Domain/Model/User.php');
        $this->requireOnce($root.'/Domain/Contracts/UserRepository.php');
        $this->requireOnce($root.'/Application/UseCase/ListUsers/ListUsersRequest.php');
        $this->requireOnce($root.'/Application/UseCase/ListUsers/ListUsersResult.php');
        $this->requireOnce($root.'/Application/UseCase/ListUsers/ListUsersUseCase.php');
        $this->requireOnce($root.'/Infrastructure/Persistence/Eloquent/UserModel.php');

        $requestClass = \App\Modules\User\Application\UseCase\ListUsers\ListUsersRequest::class;
        $defaults = new $requestClass();
        $this->assertSame(1, $defaults->page);
        $this->assertSame(15, $defaults->perPage);

        $userClass = \App\Modules\User\Domain\Model\User::class;
        $this->assertFalse((new ReflectionClass($userClass))->isSubclassOf(Model::class));
        $this->assertTrue((new ReflectionClass(\App\Modules\User\Infrastructure\Persistence\Eloquent\UserModel::class))
            ->isSubclassOf(Model::class));

        $seen = new \stdClass();
        $repository = new class($seen) implements \App\Modules\User\Domain\Contracts\UserRepository {
            public function __construct(private \stdClass $seen)
            {
            }

            public function list(int $page, int $perPage): array
            {
                $this->seen->page = $page;
                $this->seen->perPage = $perPage;

                return [
                    'items' => [new \App\Modules\User\Domain\Model\User('Ada', 'ada@example.com')],
                    'total' => 2,
                ];
            }
        };

        $useCaseClass = \App\Modules\User\Application\UseCase\ListUsers\ListUsersUseCase::class;
        $result = (new $useCaseClass($repository))->handle(new $requestClass(2, 5));

        $this->assertSame(2, $seen->page);
        $this->assertSame(5, $seen->perPage);
        $this->assertSame(2, $result->total);
        $this->assertSame('Ada', $result->items[0]['name']);
        $this->assertSame('ada@example.com', $result->items[0]['email']);
    }

    private function requireOnce(string $path): void
    {
        require_once $path;
    }

    private function deleteTree(string $path): void
    {
        if ($path === '' || ! is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path.'/'.$item;
            if (is_dir($full)) {
                $this->deleteTree($full);
            } else {
                unlink($full);
            }
        }

        rmdir($path);
    }
}
