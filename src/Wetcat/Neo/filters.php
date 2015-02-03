<?php

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/


Route::filter('neoAuth', function ($route, $request){

  $token = $request->header('X-Auth-Token');

  if ( $token === null ) {
    $response = Response::json([
      'error' => true,
      'message' => 'Not authenticated',
      'code' => 401
      ], 401
    );

    $response->header('Content-Type', 'application/json');
    return $response;
  }

  else if ( ! Neo::isAuthenticated($token) ) {
    $response = Response::json([
      'error' => true,
      'message' => 'Not authenticated',
      'code' => 401
      ], 401
    );
    return $response;
  }


});

Route::filter('neoAdmin', function ($route, $request){

  $token = $request->header('X-Auth-Token');

  if ($token === null) {
    $response = Response::json([
      'error' => true,
      'message' => 'Not authenticated',
      'code' => 401
      ], 401
    );

    $response->header('Content-Type', 'application/json');
    return $response;
  }

  else if ( ! Neo::isAuthenticated($token) ) {
    $response = Response::json([
      'error' => true,
      'message' => 'Not authenticated',
      'code' => 401
      ], 401
    );
    return $response;
  }

  if( ! Neo::isAuthorized($token, 'admin') ){
    $response = Response::json([
      'error' => true,
      'message' => 'Not authorized',
      'code' => 401
      ], 401
    );
    return $response;
  }

});