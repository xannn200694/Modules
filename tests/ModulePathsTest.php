<?php

namespace Xannn94\Modules\Tests;

use ReflectionMethod;
use Xannn94\Modules\ModulesServiceProvider;
use Xannn94\Modules\Traits\MigrationTrait;

class ModulePathsTest extends TestCase
{
    private string $modulesPath = '';

    protected function setUp(): void
    {
        $this->modulesPath = sys_get_temp_dir().'/xannn-paths-'.bin2hex(random_bytes(4));
        mkdir($this->modulesPath, 0777, true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->modulesPath);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
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

    public function test_migration_commands_use_the_modern_directory_when_it_exists(): void
    {
        $this->install('Alpha', 'alpha', ['Infrastructure/Persistence/Migrations']);
        $expected = $this->modulesPath.'/Alpha/Infrastructure/Persistence/Migrations';

        $this->assertSame($expected, module_migration_path('alpha'));
        $this->assertSame($expected, $this->invokePath('command.module.migrate', 'alpha'));
        $this->assertSame($expected, $this->invokePath('command.module.migrate.reset', 'alpha'));
        $this->assertSame($expected, $this->invokeTraitPath('alpha'));
        $this->assertSame([$expected], $this->invokeRollbackPaths('alpha'));
    }

    public function test_migration_commands_keep_the_legacy_directory(): void
    {
        $this->install('Alpha', 'alpha', ['Database/Migrations']);
        $expected = $this->modulesPath.'/Alpha/Database/Migrations';

        $this->assertSame($expected, module_migration_path('alpha'));
        $this->assertSame($expected, $this->invokePath('command.module.migrate', 'alpha'));
        $this->assertSame($expected, $this->invokePath('command.module.migrate.reset', 'alpha'));
        $this->assertSame([$expected], $this->invokeRollbackPaths('alpha'));
    }

    public function test_seed_uses_the_modern_class_when_its_directory_exists(): void
    {
        $root = $this->install('Modern', 'modern', ['Infrastructure/Persistence/Seeders']);
        $this->writeSeeder(
            $root.'/Infrastructure/Persistence/Seeders/ModernDatabaseSeeder.php',
            'App\\Modules\\Modern\\Infrastructure\\Persistence\\Seeders',
            'ModernDatabaseSeeder',
            'modern'
        );
        $this->autoload($root, 'Modern');

        $this->artisan('module:seed', ['slug' => 'modern', '--force' => true]);

        $this->assertSame('modern', $GLOBALS['module_seed_marker']);
    }

    public function test_seed_ignores_the_legacy_class_when_the_modern_directory_is_empty(): void
    {
        $root = $this->install('EmptyLayer', 'empty-layer', [
            'Infrastructure/Persistence/Seeders',
            'Database/Seeds',
        ]);
        $this->writeSeeder(
            $root.'/Database/Seeds/EmptyLayerDatabaseSeeder.php',
            'App\\Modules\\EmptyLayer\\Database\\Seeds',
            'EmptyLayerDatabaseSeeder',
            'legacy'
        );
        $this->autoload($root, 'EmptyLayer');
        $GLOBALS['module_seed_marker'] = null;

        $this->artisan('module:seed', ['slug' => 'empty-layer', '--force' => true]);

        $this->assertNull($GLOBALS['module_seed_marker']);
    }

    public function test_seed_uses_the_legacy_class_when_the_modern_directory_is_absent(): void
    {
        $root = $this->install('Legacy', 'legacy', ['Database/Seeds']);
        $this->writeSeeder(
            $root.'/Database/Seeds/LegacyDatabaseSeeder.php',
            'App\\Modules\\Legacy\\Database\\Seeds',
            'LegacyDatabaseSeeder',
            'legacy'
        );
        $this->autoload($root, 'Legacy');

        $this->artisan('module:seed', ['slug' => 'legacy', '--force' => true]);

        $this->assertSame('legacy', $GLOBALS['module_seed_marker']);
    }

    /**
     * @param  list<string>  $directories
     */
    private function install(string $basename, string $slug, array $directories): string
    {
        $root = $this->modulesPath.'/'.$basename;
        mkdir($root, 0777, true);
        file_put_contents($root.'/module.json', json_encode([
            'name' => $basename,
            'slug' => $slug,
            'version' => '1.0',
            'description' => $basename,
            'enabled' => true,
        ]));

        foreach ($directories as $directory) {
            mkdir($root.'/'.$directory, 0777, true);
        }

        $this->app->register(ModulesServiceProvider::class);

        return $root;
    }

    private function writeSeeder(string $path, string $namespace, string $class, string $marker): void
    {
        file_put_contents($path, <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Seeder;

class {$class} extends Seeder
{
    public function run(): void
    {
        \$GLOBALS['module_seed_marker'] = '{$marker}';
    }
}

PHP);
    }

    private function autoload(string $root, string $basename): void
    {
        spl_autoload_register(function (string $class) use ($root, $basename): void {
            $prefix = 'App\\Modules\\'.$basename.'\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = $root.'/'.str_replace('\\', '/', $relative).'.php';
            if (is_file($path)) {
                require $path;
            }
        });
    }

    private function invokePath(string $abstract, string $slug): string
    {
        $command = $this->app->make($abstract);
        $method = new ReflectionMethod($command, 'getMigrationPath');
        $method->setAccessible(true);

        return $method->invoke($command, $slug);
    }

    private function invokeTraitPath(string $slug): string
    {
        $carrier = new class {
            use MigrationTrait;

            public function path(string $slug): string
            {
                return $this->getMigrationPath($slug);
            }
        };

        return $carrier->path($slug);
    }

    /**
     * @return list<string>
     */
    private function invokeRollbackPaths(string $slug): array
    {
        $command = $this->app->make('command.module.migrate.rollback');
        $command->setLaravel($this->app);
        $property = new \ReflectionProperty($command, 'input');
        $input = new \Symfony\Component\Console\Input\ArrayInput(['slug' => $slug]);
        $input->bind($command->getDefinition());
        $property->setAccessible(true);
        $property->setValue($command, $input);

        $method = new ReflectionMethod($command, 'getMigrationPaths');
        $method->setAccessible(true);

        return $method->invoke($command);
    }

    private function deleteTree(string $path): void
    {
        if (! is_dir($path)) {
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
