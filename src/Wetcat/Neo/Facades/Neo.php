<?php namespace Wetcat\Neo\Facades;
 
use Illuminate\Support\Facades\Facade;
 
class Neo extends Facade {
 
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor() { return 'neo'; }
 
}