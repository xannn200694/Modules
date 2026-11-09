<?php

namespace Xannn94\Modules\Console\Generators;

use Xannn94\Modules\Modules;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module
        {slug : The slug of the module}
        {--Q|quick : Skip the make:module wizard and use default values}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Xannn94 module and bootstrap it';

    /**
     * The modules instance.
     *
     * @var Modules
     */
    protected $module;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Array to store the configuration details.
     *
     * @var array
     */
    protected $container;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Modules    $module
     */
    public function __construct(Filesystem $files, Modules $module)
    {
        parent::__construct();

        $this->files  = $files;
        $this->module = $module;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->container['slug']        = Str::slug($this->argument('slug'));
        $this->container['name']        = Str::studly($this->container['slug']);
        $this->container['version']     = '1.0';
        $this->container['description'] = 'This is the description for the ' . $this->container['name'] . ' module.';

        if ($this->option('quick')) {
            return $this->generate();
        } else {
            $this->displayHeader('make_module_introduction');

            $this->stepOne();

            $this->call('make:module:model', [
                'slug'     => $this->container['slug'],
                'name'     => $this->container['name'],
            ]);

            $table = Str::plural(Str::snake(class_basename($this->container['slug'])));

            $this->call('make:module:migration', [
                'slug'     => $this->container['slug'],
                'name'     => "create_{$table}_table",
                '--create' => $table
            ]);

            $this->call('make:module:seeder', [
                'slug'     => $this->container['slug'],
                'name'     => $this->container['name'] . 'Seeder'

            ]);
        }

        return "error creating module" . $this->container['slug'];
    }

    /**
     * Step 1: Configure module manifest.
     *
     * @return mixed
     */
    private function stepOne()
    {
        $this->displayHeader('make_module_step_1');

        $this->container['name']        = $this->ask('Please enter the name of the module:', $this->container['name']);
        $this->container['slug']        = $this->ask('Please enter the slug for the module:', $this->container['slug']);
        $this->container['version']     = $this->ask('Please enter the module version:', $this->container['version']);
        $this->container['description'] = $this->ask(
            'Please enter the description of the module:',
            $this->container['description']
        );
        $this->container['basename']    = Str::studly($this->container['slug']);
        $this->container['namespace']   = config('modules.namespace') . $this->container['basename'];

        $this->comment('You have provided the following manifest information:');
        $this->comment('Name:                       ' . $this->container['name']);
        $this->comment('Slug:                       ' . $this->container['slug']);
        $this->comment('Version:                    ' . $this->container['version']);
        $this->comment('Description:                ' . $this->container['description']);
        $this->comment('Basename (auto-generated):  ' . $this->container['basename']);
        $this->comment('Namespace (auto-generated): ' . $this->container['namespace']);

        if ($this->confirm('If the provided information is correct, type "yes" to generate.')) {
            $this->comment('Thanks! That\'s all we need.');
            $this->comment('Now relax while your module is generated.');

            $this->generate();
        } else {
            return $this->stepOne();
        }

        return true;
    }

    /**
     * Generate the module.
     */
    protected function generate()
    {
        $steps = [
            'Generating module...'       => 'generateModule',
            'Optimizing module cache...' => 'optimizeModules',
        ];

        $progress = new ProgressBar($this->output, count($steps));
        $progress->start();

        foreach ($steps as $message => $function) {
            $progress->setMessage($message);

            $this->$function();

            $progress->advance();
        }

        $progress->finish();

        event($this->container['slug'] . '.module.made');

        $this->info("\nModule generated successfully.");
    }

    /**
     * Generate defined module folders.
     */
    protected function generateModule()
    {
        if (!$this->files->isDirectory(module_path())) {
            $this->files->makeDirectory(module_path());
        }

        $pathMap   = config('modules.pathMap');
        $directory = module_path(null, $this->container['basename']);
        $source    = __DIR__ . '/../../../resources/stubs/module';

        $this->files->makeDirectory($directory);

        $sourceFiles = $this->files->allFiles($source, true);

        if (!empty($pathMap)) {
            $search = array_keys($pathMap);
            $replace = array_values($pathMap);
        }

        foreach ($sourceFiles as $file) {
            $contents = $this->replacePlaceholders($file->getContents());
            $subPath = $file->getRelativePathname();


            if (!empty($pathMap)) {
                $subPath = str_replace($search, $replace, $subPath);
            }

            /*if (strnatcmp($subPath,'Models\Model.php') === 0) {
                $subPath = str_replace('Model.php',$this->container['name'],$subPath);
                echo 'привет ';
                echo $subPath;

            }*/



            $filePath = $directory . '/' . $subPath;
            $dir = dirname($filePath);

            if (!$this->files->isDirectory($dir)) {
                $this->files->makeDirectory($dir, 0755, true);
            }

            $this->files->put($filePath, $contents);
        }


        /*
        $res = shell_exec(
            'php artisan make:module:model ' . $this->container['slug'] . ' ' . $this->container['name']
        );
        echo $res;
        */
    }

    /**
     * Reset module cache of enabled and disabled modules.
     */
    protected function optimizeModules()
    {
        return $this->callSilent('module:optimize');
    }

    /**
     * Pull the given stub file contents and display them on screen.
     *
     * @param string $file
     * @param string $level
     *
     * @return mixed
     */
    protected function displayHeader($file = '', $level = 'info')
    {
        $stub = $this->files->get(__DIR__ . '/../../../resources/stubs/console/' . $file . '.stub');

        return $this->$level($stub);
    }

    protected function replacePlaceholders($contents)
    {
        $find = [
            'DummyBasename',
            'DummyNamespace',
            'DummyName',
            'DummySlug',
            'DummyVersion',
            'DummyDescription'
        ];

        $replace = [
            $this->container['basename'],
            $this->container['namespace'],
            $this->container['name'],
            $this->container['slug'],
            $this->container['version'],
            $this->container['description']
        ];

        return str_replace($find, $replace, $contents);
    }
}
