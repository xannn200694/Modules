<?php

namespace Xannn94\Modules\Repositories;

use Xannn94\Modules\Contracts\Repository as RepositoryContract;
use Illuminate\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class Repository implements RepositoryContract
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var string Path to the defined modules directory
     */
    protected $path;

    /**
     * Constructor method.
     *
     * @param \Illuminate\Config\Repository     $config
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Config $config, Filesystem $files)
    {
        $this->config = $config;
        $this->files  = $files;
    }

    /**
     * Get all module basenames.
     *
     * @return Collection
     */
    protected function getAllBasenames()
    {
        $path = $this->getPath();

        try {
            $collection = collect($this->files->directories($path));

            $basenames = $collection->map(function ($item, $key) {
                return basename($item);
            });

            return $basenames;
        } catch (\InvalidArgumentException $e) {
            return collect(array());
        }
    }

    /**
     * Get a module's manifest contents.
     *
     * @param string|null $slug
     *
     * @return Collection|null
     */
    public function getManifest($slug)
    {
        if (! is_null($slug)) {
            $path       = $this->getManifestPath($slug);
            $contents   = $this->files->get($path);
            $collection = collect(json_decode($contents, true));

            return $collection;
        }

        return null;
    }

    /**
     * Get modules path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path ?: $this->config->get('modules.path');
    }

    /**
     * Set modules path in "RunTime" mode.
     *
     * @param string $path
     *
     * @return object $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path for the specified module.
     *
     * @param string $slug
     *
     * @return string
     */
    public function getModulePath($slug)
    {
        $module = Str::studly(Str::slug($slug));

        if (File::exists($this->getPath() . "/{$module}/")) {
            return $this->getPath() . "/{$module}/";
        }

        return $this->getPath() . "/{$slug}/";
    }

    /**
     * Get path of module manifest file.
     *
     * @param $slug
     *
     * @return string
     */
    protected function getManifestPath($slug)
    {
        return $this->getModulePath($slug) . 'module.json';
    }

    /**
     * Get modules namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return rtrim($this->config->get('modules.namespace'), '/\\');
    }
}
