<?php

namespace Simexis\Modulator\Modules;

use SplFileInfo;
use Illuminate\Support\Str;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class Module extends ServiceProvider {
	
    /**
     * The laravel application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The module namespace.
     *
     * @var
     */
    protected $namespace;

    /**
     * The module manifest,.
     *
     * @var string
     */
    protected $manifest;

    /**
     * The module path,.
     *
     * @var string
     */
    protected $path;

    /**
     * The module group,.
     *
     * @var string
     */
    protected $group;

    /**
     * The constructor.
     *
     * @param Application $app
     * @param $namespace
     * @param $manifest
     */
    public function __construct(Application $app, $group, $namespace, SplFileInfo $manifest)
    { 
        $this->app = $app;
        $this->namespace = $namespace;
        $this->group = $group;
        $this->manifest = $manifest;
		$this->path = realpath($manifest->getPath());
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    { 
        $this->registerTranslation();
		
		$this->registerViews();

        $this->fireEvent('boot');
    }

    /**
     * Register the module.
     */
    public function register()
    { 
        $this->registerConfig();
		
        $this->registerAliases();

        $this->registerProviders();

        $this->fireEvent('register');
    }

    /**
     * Get a specific data from json file by given the key.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->app['config']->get($this->getSlugName() . '.' . $key, $default);
    }

	/**
     * Return module order.
     *
     * @param integer
     */
	public function getOrder() {
		if($this->getLowerGroup() == 'core')
			return (int)('-1' . str_pad($this->get('order', 9999999), 9, '0', STR_PAD_LEFT));
		if($this->getLowerGroup() == 'modules')
			return (int)('1' . str_pad($this->get('order', 9999999), 9, '0', STR_PAD_LEFT));
		return (int)('9' . str_pad($this->get('order', 9999999), 9, '0', STR_PAD_LEFT));
	}

    /**
     * Convert namespace to slug.
     *
     * @return string
     */
    public function getSlugName()
    {
        return Str::slug(str_replace('\\','-',$this->namespace), '_');
    }

    /**
     * Convert namespace to dot.
     *
     * @return string
     */
    public function getDotName()
    {
        return str_replace('_', '.', $this->getSlugName());
    }

    /**
     * Return path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get extra path.
     *
     * @param $path
     *
     * @return string
     */
    public function getExtraPath($path)
    {
        return $this->getPath().'/'.$path;
    }

    /**
     * Convert namespace to path.
     *
     * @return string
     */
    public function getPathName()
    {
        return strtolower(str_replace(['\\','/'], DIRECTORY_SEPARATOR, $this->namespace));
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->namespace;
    }

    /**
     * Get name in lower case.
     *
     * @return string
     */
    public function getLowerName()
    {
        return strtolower($this->namespace);
    }

    /**
     * Get alias name.
     *
     * @return string
     */
    public function getAliasName()
    {
        return $this->getStudlyGroup() . '\\' . $this->getName();
    }

    /**
     * Get namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return 'App\\' . $this->getStudlyGroup() . '\\' . $this->getName();
    }

    /**
     * Get group in lower case.
     *
     * @return string
     */
    public function getLowerGroup()
    {
        return strtolower($this->group);
    }

    /**
     * Get group in studly case.
     *
     * @return string
     */
    public function getStudlyGroup()
    {
        return Str::studly($this->group);
    }
	
    /**
     * Register the module event.
     *
     * @param string $event
     */
    protected function fireEvent($event)
    {
        $this->app['events']->fire(sprintf('modules.%s.'.$event, $this->getLowerName()), [$this]);
    }

    /**
     * Register the aliases from this module.
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            $this->manifest->getRealPath(), $this->getSlugName()
        );
    }

    /**
     * Register the aliases from this module.
     */
    protected function registerTranslation()
    {
		$langPath = $this->app->langPath() . '/' . $this->getPathName();
		$this->loadTranslationsFrom(
			is_dir($langPath) ? $langPath : $this->manifest->getPath() . '/Resources/lang', $this->getSlugName()
		);
    }

	/**
	 * Register views.
	 * 
	 * @return void
	 */
	protected function registerViews()
	{ 
		$viewPaths = $this->app['view']->getFinder()->getPaths();
		foreach($viewPaths AS $r => $path) {
			if(!is_dir($path . '/' . $this->getPathName()))
				unset($viewPaths[$r]);
			$viewPaths[$r] = $path . '/' . $this->getPathName();
		} 
		
		if(!$viewPaths) {
			$this->loadViewsFrom($viewPaths, $this->getSlugName());
		} else {
			$this->loadViewsFrom([$this->manifest->getPath() . '/Resources/views'], $this->getSlugName());
		}
	}

    /**
     * Register the aliases from this module.
     */
    protected function registerAliases()
    {
        $loader = AliasLoader::getInstance();
        foreach ($this->get('aliases', []) as $aliasName => $aliasClass) {
            $loader->alias($aliasName, $aliasClass);
        }
    }

    /**
     * Register the service providers from this module.
     */
    protected function registerProviders()
    {
        foreach ($this->get('providers', []) as $provider) {
            $this->app->register($provider);
        }
    }
	
	
}