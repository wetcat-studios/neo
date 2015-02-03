<?php namespace Wetcat\Neo;

use Illuminate\Support\ServiceProvider;
use Wetcat\Neo\Neo;

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
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    $this->registerUserProvider();
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
   * Register the user provider used by Sentry.
   *
   * @return void
   */
  protected function registerUserProvider()
  {
    $this->app['neo.user'] = $this->app->share(function ($app)
    {
      $model = $app['config']['cartalyst/sentry::users.model'];

      return new UserProvider($app['sentry.hasher'], $model);
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
      $alias   = $app['config']->get('database.neo.default.alias', $app['config']->get('neo::default.alias'));
      $scheme  = $app['config']->get('database.neo.default.scheme', $app['config']->get('neo::default.scheme'));
      $host    = $app['config']->get('database.neo.default.host', $app['config']->get('neo::default.host'));
      $port    = $app['config']->get('database.neo.default.port', $app['config']->get('neo::default.port'));
      $auth    = $app['config']->get('database.neo.default.auth', $app['config']->get('neo::default.auth'));
      $user    = $app['config']->get('database.neo.default.user', $app['config']->get('neo::default.user'));
      $pass    = $app['config']->get('database.neo.default.pass', $app['config']->get('neo::default.pass'));
      $timeout = $app['config']->get('database.neo.default.timeout', $app['config']->get('neo::default.timeout'));
    
      return new Neo(
        $alias, $scheme, $host, $port, $auth, $user, $pass, $timeout
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

    $this->commands(array(
        'wetcat::neo.create',
        'wetcat::neo.remove'
    ));
  }

}
