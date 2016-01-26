<?php

namespace Simexis\Modulator\Database\Console\Migrations;

use Simexis\Modulator\Facades\Modulator;
use Illuminate\Foundation\Composer;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand AS BaseMigrateMakeCommand;

class MigrateMakeCommand extends BaseMigrateMakeCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;
	
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $name = 'make:migration';
	
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
		
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = $this->input->getArgument('name');

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create');

        if (! $table && is_string($create)) {
            $table = $create;
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $table, $create);

        $this->composer->dumpAutoloads();
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
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function writeMigration($name, $table, $create)
    {
        $path = $this->getMigrationPath();
		
		if($this->module)
			$this->creator->setModule($this->module);
		
        $file = pathinfo($this->creator->create($name, $path, $table, $create), PATHINFO_FILENAME);

        $this->line("<info>Created Migration:</info> $file");
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return $this->laravel->basePath().'/'.$targetPath;
        }
		
		if($this->module)
			return $this->module->getExtraPath('Database/Migrations');

        return parent::getMigrationPath();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
		return array_merge(parent::getArguments(), [
			['name', InputArgument::REQUIRED, 'The name of the migration.'],
			['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
		]);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
		return [
			['create', null, InputOption::VALUE_OPTIONAL, 'The table to be created.'],
			['table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate.'],
			['path', null, InputOption::VALUE_OPTIONAL, 'The location where the migration file should be created.'],
		];
    }
}
