<?php

namespace Xannn94\Modules\Tests;

use Xannn94\Modules\ModulesServiceProvider;

class ModuleBootTest extends TestCase
{
    private string $modulesPath = '';

    /** @var array<string, array<string, mixed>> */
    private array $plan = [];

    private bool $cold = false;

    /** @var array<string, string> */
    private array $pathMap = [];

    protected function setUp(): void
    {
        $this->modulesPath = sys_get_temp_dir().'/xannn-modules-'.bin2hex(random_bytes(4));
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
        $app['config']->set('modules.namespace', 'Modules\\');
        $app['config']->set('modules.driver', 'local');
        $app['config']->set('modules.enabled', true);
        $app['config']->set('modules.pathMap', $this->pathMap);

        $this->writeModules();

        $cache = storage_path('app/modules.json');
        if (! is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0777, true);
        }

        if ($this->cold) {
            if (is_file($cache)) {
                unlink($cache);
            }

            return;
        }

        file_put_contents($cache, json_encode($this->cacheDocument(), JSON_PRETTY_PRINT));
    }

    public function test_cache_wins_when_manifest_disagrees(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module('alpha', ['enabled' => true], ['enabled' => false], true),
        ]);

        $this->assertSame(['provider:Alpha'], $log);
    }

    public function test_enabled_values_follow_loose_true(): void
    {
        $log = $this->boot([
            'Yes' => $this->module('yes', ['enabled' => true, 'order' => 1], [], true),
            'No' => $this->module('no', ['enabled' => false, 'order' => 2], [], true),
            'Missing' => $this->module('missing', ['order' => 3], [], true),
            'One' => $this->module('one', ['enabled' => 1, 'order' => 4], [], true),
            'Word' => $this->module('word', ['enabled' => 'true', 'order' => 5], [], true),
            'Digit' => $this->module('digit', ['enabled' => '1', 'order' => 6], [], true),
        ]);

        $this->assertSame(
            ['provider:Yes', 'provider:One', 'provider:Word', 'provider:Digit'],
            $log
        );
    }

    public function test_lower_order_registers_first(): void
    {
        $log = $this->boot([
            'Late' => $this->module('late', ['order' => 10, 'enabled' => true], [], true),
            'Early' => $this->module('early', ['order' => 1, 'enabled' => true], [], true),
        ]);

        $this->assertSame(['provider:Early', 'provider:Late'], $log);
    }

    public function test_missing_order_registers_before_numeric_order(): void
    {
        $log = $this->boot([
            'Second' => $this->module('second', ['order' => 1, 'enabled' => true], [], true),
            'First' => $this->module('first', ['enabled' => true], [], true),
        ]);

        $this->assertSame(['provider:First', 'provider:Second'], $log);
    }

    public function test_existing_provider_is_registered(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module('alpha', ['enabled' => true], [], true),
        ]);

        $this->assertSame(['provider:Alpha'], $log);
    }

    public function test_infrastructure_provider_is_registered(): void
    {
        $log = $this->boot([
            'Infra' => $this->module('infra', ['enabled' => true], [], 'infrastructure'),
        ]);

        $this->assertSame(['provider:Infra'], $log);
        $this->assertTrue($this->app->providerIsLoaded(
            'Modules\\Infra\\Infrastructure\\Providers\\ModuleServiceProvider'
        ));
    }

    public function test_legacy_provider_is_registered_when_infrastructure_is_absent(): void
    {
        $log = $this->boot([
            'LegacyOnly' => $this->module('legacy-only', ['enabled' => true], [], 'legacy'),
        ]);

        $this->assertSame(['provider:LegacyOnly'], $log);
        $this->assertTrue($this->app->providerIsLoaded('Modules\\LegacyOnly\\Providers\\ModuleServiceProvider'));
        $this->assertFalse($this->app->providerIsLoaded(
            'Modules\\LegacyOnly\\Infrastructure\\Providers\\ModuleServiceProvider'
        ));
    }

    public function test_infrastructure_provider_wins_when_both_exist(): void
    {
        $log = $this->boot([
            'BothLayers' => $this->module('both-layers', ['enabled' => true], [], 'both'),
        ]);

        $this->assertSame(['provider:BothLayers'], $log);
        $this->assertTrue($this->app->providerIsLoaded(
            'Modules\\BothLayers\\Infrastructure\\Providers\\ModuleServiceProvider'
        ));
        $this->assertFalse($this->app->providerIsLoaded('Modules\\BothLayers\\Providers\\ModuleServiceProvider'));
    }

    public function test_missing_provider_is_skipped_and_boot_continues(): void
    {
        $log = $this->boot([
            'Ghost' => $this->module('ghost', ['enabled' => true, 'order' => 1], [], false),
            'Alpha' => $this->module('alpha', ['enabled' => true, 'order' => 2], [], true),
        ]);

        $this->assertSame(['provider:Alpha'], $log);
    }

    public function test_existing_autoload_file_is_included(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module('alpha', ['enabled' => true, 'autoload' => ['start.php']], [], true, ['start.php' => true]),
        ]);

        $this->assertSame(['provider:Alpha', 'file:Alpha-start.php'], $log);
    }

    public function test_missing_autoload_file_is_skipped(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module(
                'alpha',
                ['enabled' => true, 'order' => 1, 'autoload' => ['missing.php']],
                [],
                true
            ),
            'Beta' => $this->module('beta', ['enabled' => true, 'order' => 2], [], true),
        ]);

        $this->assertSame(['provider:Alpha', 'provider:Beta'], $log);
    }

    public function test_module_without_autoload_includes_no_files(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module('alpha', ['enabled' => true], [], true),
        ]);

        $this->assertSame(['provider:Alpha'], $log);
    }

    public function test_path_map_rewrites_autoload_prefix(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module(
                'alpha',
                ['enabled' => true, 'autoload' => ['Starts/boot.php']],
                [],
                true,
                ['Real/boot.php' => 'mapped']
            ),
        ], false, ['Starts/' => 'Real/']);

        $this->assertSame(['provider:Alpha', 'file:Alpha-mapped'], $log);
    }

    public function test_disabled_module_registers_neither_provider_nor_files(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module(
                'alpha',
                ['enabled' => false, 'autoload' => ['start.php']],
                [],
                true,
                ['start.php' => true]
            ),
        ]);

        $this->assertSame([], $log);
    }

    public function test_descriptive_fields_do_not_change_selection(): void
    {
        $module = $this->module('alpha', [
            'enabled' => true,
            'name' => 'Alpha',
            'version' => '1.0',
            'description' => 'one',
            'id' => 1,
        ], [], true);

        $first = $this->boot(['Alpha' => $module]);
        $provider = 'Modules\\Alpha\\Providers\\ModuleServiceProvider';

        $this->assertSame(['provider:Alpha'], $first);
        $this->assertTrue($this->app->providerIsLoaded($provider));

        $module['cache']['name'] = 'Renamed';
        $module['cache']['version'] = '9.9';
        $module['cache']['description'] = 'two';
        $module['cache']['id'] = 2;

        $second = $this->boot(['Alpha' => $module]);

        $this->assertSame($first, $second);
        $this->assertTrue($this->app->providerIsLoaded($provider));
    }

    public function test_cache_edit_after_boot_waits_for_the_next_boot(): void
    {
        $this->boot([
            'Alpha' => $this->module('alpha', ['enabled' => false], [], true),
        ]);

        $this->plan['Alpha']['cache']['enabled'] = true;
        file_put_contents(storage_path('app/modules.json'), json_encode($this->cacheDocument(), JSON_PRETTY_PRINT));

        $this->assertSame([], $GLOBALS['module_boot_log']);

        $this->app['modules']->register();

        $this->assertSame(['provider:Alpha'], $GLOBALS['module_boot_log']);
    }

    public function test_cold_start_keeps_manifest_disabled(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module('alpha', [], ['enabled' => false], true),
        ], true);

        $this->assertSame([], $log);
    }

    public function test_cold_start_defaults_enabled_to_true(): void
    {
        $log = $this->boot([
            'Alpha' => $this->module('alpha', [], ['name' => 'Alpha', 'slug' => 'alpha'], true),
        ], true);

        $this->assertSame(['provider:Alpha'], $log);
    }

    /**
     * @param  array<string, mixed>  $cache
     * @param  array<string, mixed>  $manifest
     * @param  array<string, bool|string>  $files
     * @return array<string, mixed>
     */
    private function module(
        string $slug,
        array $cache,
        array $manifest,
        bool|string $provider,
        array $files = []
    ): array
    {
        return [
            'slug' => $slug,
            'cache' => $cache,
            'manifest' => $manifest,
            'provider' => $provider,
            'files' => $files,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $plan
     * @param  array<string, string>  $pathMap
     * @return array<int, string>
     */
    private function boot(array $plan, bool $cold = false, array $pathMap = []): array
    {
        $this->plan = $plan;
        $this->cold = $cold;
        $this->pathMap = $pathMap;
        $GLOBALS['module_boot_log'] = [];
        $this->refreshApplication();
        $this->app->register(ModulesServiceProvider::class);

        return $GLOBALS['module_boot_log'];
    }

    private function writeModules(): void
    {
        foreach ($this->plan as $basename => $module) {
            $root = $this->modulesPath.'/'.$basename;
            if (! is_dir($root)) {
                mkdir($root, 0777, true);
            }

            $manifest = array_merge([
                'name' => $basename,
                'slug' => $module['slug'],
                'version' => '1.0',
                'description' => $basename,
            ], $module['manifest']);
            file_put_contents($root.'/module.json', json_encode($manifest, JSON_PRETTY_PRINT));

            foreach ($this->providerLocations($module['provider']) as $relative) {
                $namespace = 'Modules\\'.$basename.'\\'.str_replace('/', '\\', $relative);
                $dir = $root.'/'.$relative;
                if (! is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $class = $namespace.'\\ModuleServiceProvider';
                if (! class_exists($class, false)) {
                    $source = $dir.'/ModuleServiceProvider.php';
                    file_put_contents($source, $this->providerSource($namespace, $basename));
                    require $source;
                }
            }

            foreach ($module['files'] as $relative => $token) {
                $path = $root.'/'.$relative;
                if (! is_dir(dirname($path))) {
                    mkdir(dirname($path), 0777, true);
                }
                $marker = $token === true ? $basename.'-'.basename($relative) : $basename.'-'.$token;
                file_put_contents($path, "<?php\n\$GLOBALS['module_boot_log'][] = 'file:{$marker}';\n");
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cacheDocument(): array
    {
        $document = [];

        foreach ($this->plan as $basename => $module) {
            $entry = array_merge([
                'basename' => $basename,
                'slug' => $module['slug'],
                'name' => $basename,
                'version' => '1.0',
                'description' => $basename,
            ], $module['cache']);
            $document[$basename] = $entry;
        }

        return $document;
    }

    /**
     * @return list<string>
     */
    private function providerLocations(bool|string $provider): array
    {
        return match ($provider) {
            true, 'legacy' => ['Providers'],
            'infrastructure' => ['Infrastructure/Providers'],
            'both' => ['Infrastructure/Providers', 'Providers'],
            default => [],
        };
    }

    private function providerSource(string $namespace, string $basename): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$GLOBALS['module_boot_log'][] = 'provider:{$basename}';
    }
}

PHP;
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
