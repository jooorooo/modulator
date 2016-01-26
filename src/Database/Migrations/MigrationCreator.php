<?php

namespace Simexis\Modulator\Database\Migrations;

use Simexis\Modulator\Modules\Module;
use Illuminate\Database\Migrations\MigrationCreator AS BaseMigrationCreator;

class MigrationCreator extends BaseMigrationCreator
{
	
    /**
     * @var \Simexis\Modulator\Modules\Module|null
     */
    protected $module;

	/**
     * Set module.
     *
     * @param  \Simexis\Modulator\Modules\Module  $module
     * @return \Simexis\Modulator\Database\Migrations\MigrationCreator
     */
	public function setModule(Module $module) {
		$this->module = $module;
		return $this;
	}

    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->makeDirectory($path);

        return parent::create($name, $path, $table, $create);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }
    }

    /**
     * Get the migration stub file.
     *
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if (is_null($table)) {
			if($this->module)
				return $this->files->get(__DIR__.'/stubs/blank.stub');
			return parent::getStub($table, $create);
        }

        // We also have stubs for creating new tables and modifying existing tables
        // to save the developer some typing when they are creating a new tables
        // or modifying existing tables. We'll grab the appropriate stub here.
        else {
            $stub = $create ? 'create.stub' : 'update.stub';

			if($this->module)
				return $this->files->get(__DIR__."/stubs/{$stub}");
			return parent::getStub($table, $create);
        }
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  string  $table
     * @return string
     */
    protected function populateStub($name, $stub, $table)
    {
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);
        $stub = str_replace('DummyNamespace', $this->getNamespace($name), $stub);

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        if (! is_null($table)) {
            $stub = str_replace('DummyTable', $table, $stub);
        }

        return $stub;
    }

    /**
     * Get the full namespace name for a given class.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
		if($this->module)
			return $this->module->getNamespace() . '\Database\Migrations';
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }
}
