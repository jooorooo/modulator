<?php

namespace Simexis\Modulator\Database\Console\Seeds;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Console\Seeds\SeedCommand AS BaseSeedCommand;

class SeedCommand extends BaseSeedCommand
{
    /**
     * @var \Simexis\Modulator\Modulator\Modules
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
		
		$this->module = $this->laravel['modules'];

		$arguments = $this->input->getArguments();
        $name = array_key_exists('module', $arguments) && $arguments['module'] ? strtolower($arguments['module']) : null;

		if(!$name) {
			return parent::fire();
		}
		
		//parent::fire();
		
		if ($name) {
            return $this->dbseed($name);
        }

        foreach ($this->module->getOrdered($this->option('direction')) as $module) {
            $this->line('Seed for module: <info>'.$module->getName().'</info>');

            $this->dbseed($module->getDotName());
        }
		
		$fkCheck ? null : DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return $this->info('All modules seeded.');
		
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->resolver->setDefaultConnection($this->getDatabase());

        $this->getSeeder()->run();
    }

    /**
     * Seed the specified module.
     *
     * @parama string  $name
     *
     * @return array
     */
    protected function dbseed($name)
    {
		
		$class = $this->getSeederName($name);
		if(!$class)
			return $this->info('Nothing to seed.');
		
        $params = [
            '--class' => $this->getSeederName($name),
            '--force' => $this->option('force'),
        ];

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        $this->call('db:seed', $params);
    }

    /**
     * Get master database seeder name for the specified module.
     *
     * @param string $name
     *
     * @return string
     */
    public function getSeederName($name)
    {
        $module = $this->module->findOrFail($name);
		
		$path = $module->getExtraPath('Database/Seeders/DatabaseSeeder.php');
		if(!is_file($path))
			return null;
		
        $namespace = $module->getNamespace();

        return $namespace.'\Database\Seeders\DatabaseSeeder';
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
