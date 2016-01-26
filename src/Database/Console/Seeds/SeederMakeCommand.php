<?php

namespace Simexis\Modulator\Database\Console\Seeds;

use Simexis\Modulator\Facades\Modulator;
use Illuminate\Foundation\Composer;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Console\Seeds\SeederMakeCommand AS BaseSeederMakeCommand;

class SeederMakeCommand extends BaseSeederMakeCommand
{
    /**
     * @var \Simexis\Modulator\Modules\Module|null
     */
    protected $module;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
		
        $this->module = $this->getModule($this->argument('module'));
		
		$this->checkBaseSeeder();
		
        parent::fire();

        $this->composer->dumpAutoloads();
    }

    /**
     * Generate DatabaseSeeder if not exists.
     *
     * @return \Simexis\Modulator\Modules\Module|null
     */
    protected function checkBaseSeeder()
    {
		if(!$this->module)
			return ;
		
		$path = $this->module->getExtraPath('Database/Seeders/DatabaseSeeder.php');
		if(is_file($path))
			return ;
		
		$this->makeDirectory($path);
		
		$stub = $this->files->get(__DIR__.'/stubs/dbseeder.stub');

        $this->replaceNamespace($stub, '');

		return $this->files->put($path, $stub);
		
    }

    /**
     * Get module.
     *
     * @param  string  $name
     * @return \Simexis\Modulator\Modules\Module|null
     */
    protected function getModule($name)
    {
		if(!$name)
			return null;
		return Modulator::findOrFail(strtolower($name));
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
		if($this->module)
			return $this->module->getExtraPath('Database/Seeders/' . $name . '.php');
        return $this->laravel->databasePath().'/seeds/'.$name.'.php';
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
			return $this->module->getNamespace() . '\Database\Seeders';
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
		if($this->module)
			return __DIR__.'/stubs/seeder.stub';
		return parent::getStub();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
		return array_merge(parent::getArguments(), [
			['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
		]);
    }
}
