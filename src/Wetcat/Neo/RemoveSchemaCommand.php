<?php namespace Wetcat\Neo;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Config;
use Neoxygen\NeoClient\ClientBuilder;

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
		$alias   = Config::get('database.neo.default.alias', Config::get('neo::default.alias'));
    $scheme  = Config::get('database.neo.default.scheme', Config::get('neo::default.scheme'));
    $host    = Config::get('database.neo.default.host', Config::get('neo::default.host'));
    $port    = Config::get('database.neo.default.port', Config::get('neo::default.port'));
    $auth    = Config::get('database.neo.default.auth', Config::get('neo::default.auth'));
    $user    = Config::get('database.neo.default.user', Config::get('neo::default.user'));
    $pass    = Config::get('database.neo.default.pass', Config::get('neo::default.pass'));
    $timeout = Config::get('database.neo.default.timeout', Config::get('neo::default.timeout'));

		$client = ClientBuilder::create()
      ->addConnection($alias, $scheme, $host, $port, $auth, $user, $pass)
      ->setAutoFormatResponse(true)
      ->setDefaultTimeout($timeout)
      ->build();

    // Unset User label
    $client->dropUniqueConstraint('User', 'email');
    $client->dropIndex('User', 'firstname');
    $client->dropIndex('User', 'lastname');

    // Unset Group label
    $client->dropUniqueConstraint('Group', 'name');
	}

}
