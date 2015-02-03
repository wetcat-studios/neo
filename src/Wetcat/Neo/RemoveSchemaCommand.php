<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RemoveSchemaCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'neo:remove';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Remove the schema for Users in the Neo4j database.';

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
		Neo::dropUnique('User', 'email');

		Neo::dropIndex('User', 'firstname');
		Neo::dropIndex('User', 'lastname');
		Neo::dropIndex('User', 'roles');
	}

}
