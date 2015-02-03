<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateSchemaCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'neo:create';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create the schema for Users in the Neo4j database.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		Neo::unique('User', 'email');

		Neo::index('User', 'firstname');
		Neo::index('User', 'lastname');
		Neo::index('User', 'roles');
	}

}
