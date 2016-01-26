<?php

namespace Simexis\Modulator\Database\Console\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Console\Migrations\MigrateCommand AS BaseMigrateCommand;

class MigrateCommand extends BaseMigrateCommand
{
    /**
     * @var \Simexis\Modulator\Modules\Modules
     */
    protected $module;
	
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
		
		$fkCheck = $this->input->getOption('fkCheck');
		
		$fkCheck ? null : DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		
		if (! is_null($path = $this->input->getOption('path'))) {
			return $this->migrate($path);
		}
		
		$this->migrate();
		
		$this->module = $this->laravel['modules'];

        $name = strtolower($this->argument('module') ? : '');
		
		if ($name) { 
            return $this->migrate(null, $name);
        }

        foreach ($this->module->getOrdered($this->option('direction')) as $module) {
            $this->line('Running for module: <info>'.$module->getName().'</info>');

            $this->migrate(null, $module->getDotName());
        }
		
		$fkCheck ? null : DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return $this->info('All modules migrated.');
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
		return $this->module->findOrFail(strtolower($name));
    }

    /**
     * Run the migration from the specified module.
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function migrate($path = null, $name = null)
    {
		if (! $this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();
		
		$path = $module = null;
		if(!$path && $name) {
			$module = $this->getModule($name);
			$path = $module->getExtraPath('Database/Migrations');
		} else if($path && !$name) {
			$path = $this->laravel->basePath().'/'.$path;
		}
		if(!$path)
			$path = $this->getMigrationPath();

        // The pretend option can be used for "simulating" the migration and grabbing
        // the SQL queries that would fire if the migration were to be run against
        // a database for real, which is helpful for double checking migrations.
        $pretend = $this->input->getOption('pretend');
		
		if($module)
			$this->migrator->setModule($module);
		
        $this->migrator->run($path, $pretend);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }

        // Finally, if the "seed" option has been given, we will re-run the database
        // seed task to re-populate the database, which is convenient when adding
        // a migration and a seed at the same time, as it is only this command.
        if ($this->option('seed')) {
            $this->call('db:seed', ['module' => $name, '--force' => true]);
        }

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of module will be used.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
		return array_merge([
			['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
			['fkCheck', null, InputOption::VALUE_OPTIONAL, 'Enable or disable "FOREIGN_KEY_CHECKS".', false],
		], parent::getOptions());
    }
}
