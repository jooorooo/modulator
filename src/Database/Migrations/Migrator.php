<?php

namespace Simexis\Modulator\Database\Migrations;

use Illuminate\Support\Str;
use Simexis\Modulator\Modulator\Module;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migrator AS BaseMigrator;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class Migrator extends BaseMigrator
{
    /**
     * @var \Simexis\Modulator\Modulator\Module|null
     */
    protected $module;

	/**
     * Set module.
     *
     * @param  Simexis\Modulator\Modulator\Module  $module
     * @return \Simexis\Modulator\Database\Migrations\Migrator;
     */
	public function setModule(Module $module) {
		$this->module = $module;
		return $this;
	}
	
    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $file = implode('_', array_slice(explode('_', $file), 4));

        $class = Str::studly($file);
		if($this->module)
			$class = $this->module->getNamespace() . '\Database\Migrations\\' . $class;
		
        return new $class;
    }

}
