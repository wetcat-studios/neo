<?php namespace Wetcat\Neo;

use Illuminate\Support\ServiceProvider;
use Wetcat\Neo\Neo;
use Wetcat\Neo\Users\Provider as UserProvider;
use Wetcat\Neo\Groups\Provider as GroupProvider;
use Config;

class NeoServiceProvider extends ServiceProvider {

  /**
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = false;

  /**
   * Bootstrap the application events.
   *
   * @return void
   */
  public function boot()
  {
    $this->package('wetcat/neo');

    include __DIR__.'/filters.php';
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    $this->registerUserProvider();
    $this->registerGroupProvider();
    $this->registerNeo();

    $this->registerCommands();

    $this->app->booting(function()
    {
      $loader = \Illuminate\Foundation\AliasLoader::getInstance();
      $loader->alias('Neo', 'Wetcat\Neo\Facades\Neo');
    });
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {
    return array();
  }

  /**
   * Register the user provider used by Neo.
   *
   * @return void
   */
  protected function registerUserProvider()
  {
    $this->app['neo.user'] = $this->app->share(function ($app)
    {
      return new UserProvider();
    });
  }

  /**
   * Register the group provider used by Neo.
   *
   * @return void
   */
  protected function registerGroupProvider()
  {
    $this->app['neo.group'] = $this->app->share(function ($app)
    {
      return new GroupProvider();
    });
  }

  /**
   * Creates a new Neo object
   *
   * @return void
   */
  protected function registerNeo()
  {
    $this->app['neo'] = $this->app->share(function($app)
    {
      $alias   = Config::get('database.neo.default.alias', Config::get('neo::default.alias'));
      $scheme  = Config::get('database.neo.default.scheme', Config::get('neo::default.scheme'));
      $host    = Config::get('database.neo.default.host', Config::get('neo::default.host'));
      $port    = Config::get('database.neo.default.port', Config::get('neo::default.port'));
      $auth    = Config::get('database.neo.default.auth', Config::get('neo::default.auth'));
      $user    = Config::get('database.neo.default.user', Config::get('neo::default.user'));
      $pass    = Config::get('database.neo.default.pass', Config::get('neo::default.pass'));
      $timeout = Config::get('database.neo.default.timeout', Config::get('neo::default.timeout'));
    
      return new Neo(
        $app['neo.user'],
        $app['neo.group'],
        $alias, 
        $scheme, 
        $host, 
        $port,
        $auth, 
        $user, 
        $pass, 
        $timeout
      );
    });
  }

  protected function registerCommands()
  {
    $this->app->bind('wetcat::neo.create', function($app) {
      return new CreateSchemaCommand();
    });

    $this->app->bind('wetcat::neo.remove', function($app) {
      return new RemoveSchemaCommand();
    });

    $this->app->bind('wetcat::neo.group', function($app) {
      return new CreateNeoGroups();
    });

    $this->commands(array(
        'wetcat::neo.create',
        'wetcat::neo.remove',
        'wetcat::neo.group'
    ));
  }

}
