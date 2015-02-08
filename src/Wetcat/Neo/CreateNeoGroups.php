<?php namespace Wetcat\Neo;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Config;
use Neoxygen\NeoClient\ClientBuilder;

class CreateNeoGroups extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'neo:groups';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create standard neo auth groups.';

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

    // Get the name arguments and the age option from the input instance.
    $name = $this->argument('name');
		$level = $this->option('level');
 
 		$this->line('Generating a neo auth group.');
    $this->line("{$name} has level {$level}.");

    try {
    	$query = "CREATE (g:Group {name: '".$name."', level: ".$level."})";
			$result = $client->sendCypherQuery($query)->getResult();
    } catch ( Neoxygen\NeoClient\Exception\Neo4jException $e ) {
    	$this->error($e->getMessage());
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
			array('name', InputArgument::REQUIRED, 'Group name.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('level', null, InputOption::VALUE_REQUIRED, 'Group authorization level.', null),
		);
	}

}
