<?php

namespace Simexis\Modulator\Modules;

use Closure;
use Countable;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;
use Illuminate\Foundation\Application;
use Symfony\Component\Finder\SplFileInfo;
use Simexis\Modulator\Modules\Exceptions\ModuleNotFound;

class Modules implements Countable {
	
    /**
     * Application instance.
     *
     * @var Application
     */
    protected $app;
	
    /**
     * @var array
     */
    protected $modules;

    /**
     * The constructor.
     *
     * @param Application $app
     * @param array|null $modules
     */
    public function __construct(Application $app, array $modules = [])
    {
        $this->app = $app;
		if($modules)
			$this->modules = $modules;
    }

    /**
     * Get count from all modules.
     *
     * @return int
     */
    public function count()
    {
        return count(Arr::dot($this->all()));
    }

    /**
     * Register the modules.
     */
    public function register()
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * Boot the modules.
     */
    public function boot()
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * Get a specific config data from a configuration file.
     *
     * @param $key
     * @param $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->app['config']->get('modulator.'.$key, $default);
    }

    /**
     * Get cached modules.
     *
     * @param $key
     * @param $callback
	 *
     * @return array
     */
    public function getCached($key, Closure $callback)
    {
		if(!$this->config('cache.enabled'))
			return $this->app->call($callback);
        return $this->app['cache']->remember($this->config('cache.prefix') . '-' . $key, $this->config('cache.lifetime'), $callback);
    }

    /**
     * Get & scan all modules.
     *
     * @return array
     */
    public function all()
    {
		if(is_array($this->modules))
			return $this->modules;
		
		$this->modules = [];
		
        $paths = $this->getScanPaths();

        foreach ($paths as $key => $path) {
            $manifests = $this->toSplFileInfo($this->getCached($key, function() use($path) {
				return array_map(function($info) { 
					return [
						'path' => $info->getRealPath(),
						'relativePath' => $info->getRelativePath(),
						'relativePathname' => $info->getRelativePathName(),
					]; 
				}, $this->allConfigs($path));
			}));

            foreach ($manifests as $manifest) {
                $namespace = $this->pathToNamespace($manifest->getRelativePath());

                $module = new Module($this->app, $key, $namespace, $manifest);
				
				Arr::set($this->modules, $module->getDotName(),$module);
            }
        }

        return $this->modules;
    }

    /**
     * Get all modules as collection instance.
     *
     * @return Collection
     */
    public function toCollection()
    {
        return new Collection(Arr::dot($this->all()));
    }

    /**
     * Determine whether the given module exist.
     *
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return Arr::has($this->all(), $name);
    }

    /**
     * Find a specific module.
     *
     * @param $name
     */
    public function find($name)
    {
		$module = Arr::get($this->all(), $name);
		if(is_array($module))
			return new static($this->app, $module);
		return $module;
    }

    /**
     * Alternative for "find" method.
     *
     * @param $name
     */
    public function get($name)
    {
        return $this->find($name);
    }

    /**
     * Find a specific module, if there return that, otherwise throw exception.
     *
     * @param $name
     *
     * @return Module
     *
     * @throws \App\Exceptions\ModuleNotFound
     */
    public function findOrFail($name)
    {
        if (!is_null($module = $this->find($name))) {
            return $module;
        }

        throw new ModuleNotFound("Module or group of modules [{$name}] does not exist!");
    }

    /**
     * Get all ordered modules.
     *
     * @param string $direction
     *
     * @return array
     */
    public function getOrdered($direction = 'asc')
    {
        $modules = Arr::dot($this->all());

        uasort($modules, function ($a, $b) use ($direction) {
            if ($a->getOrder() == $b->getOrder()) {
                return 0;
            }

            if ($direction == 'desc') {
                return $a->getOrder() < $b->getOrder() ? 1 : -1;
            }

            return $a->getOrder() > $b->getOrder() ? 1 : -1;
        });

        return $modules;
    }

    /**
     * Get scanned modules paths.
     *
     * @return array
     */
    public function getScanPaths()
    {
        return [
			'core' => $this->app->path() . '/Core/',
			'modules' => $this->app->path() . '/Modules/'
		];
    }

    /**
     * Get all config.php of the files from the given directory (recursive).
     *
     * @param  string  $directory
     * @return array
     */
    private function allConfigs($directory)
    {
		if(!is_dir($directory))
			return [];
        return iterator_to_array(Finder::create()->files()->name('config.php')->in($directory), false);
    }

    private function pathToNamespace($path) {
		return str_replace('/', '\\', $path);
	}
	
	private function toSplFileInfo(array $array) {
		foreach($array AS $key => $value) {
			$array[$key] = new SplFileInfo($value['path'], $value['relativePath'], $value['relativePathname']);
		}
		return $array;
	}
	
}