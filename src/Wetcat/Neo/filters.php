<?php namespace Wetcat\Neo;

use Neo;

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

/*
 * Filter that checks that the authentication token is valid, if the token is not 
 * valid the filter aborts and resturns a JSON error.
 *
 * {
 *   "error": true,
 *   "data": {
 *     ... error message/s ...
 *   },
 *   "code": 4xx
 * }
 */
Route::filter('neoAuth', function ($route, $request){

  $token = $request->header('X-Auth-Token');

  // If the token doesn't exist the user isn't authenticated
  if ( $token === null ) {
    $data = ['Not authenticated'];
    $code = 401;

    $response = Response::json([
      'error' => true,
      'data' => $data,
      'code' => $code
      ], $code
    );

    return $response;
  }

  // If the token is invalid the user isn't authenticated
  else if ( ! Neo::isAuthenticated($token) ) {
    $data = ['Not authenticated'];
    $code = 401;

    $response = Response::json([
      'error' => true,
      'data' => $data,
      'code' => $code
      ], $code
    );

    return $response;
  }

});

/*
 * Filter that checks that the authentication token is valid and that the use connect to
 * this token is member of the "Admin" group. If one of these checks doesn't pass the
 * filter aborts and resturns a JSON error.
 *
 * {
 *   "error": true,
 *   "data": {
 *     ... error message/s ...
 *   },
 *   "code": 4xx
 * }
 */
Route::filter('neoAdmin', function ($route, $request){

  $token = $request->header('X-Auth-Token');

  // If the token doesn't exist the user isn't authenticated
  if ($token === null) {
    $data = ['Not authenticated'];
    $code = 401;

    $response = Response::json([
      'error' => true,
      'data' => $data,
      'code' => $code
      ], $code
    );

    return $response;
  }

  // If the token is invalid the user isn't authenticated
  else if ( ! Neo::isAuthenticated($token) ) {
    $data = ['Not authenticated'];
    $code = 401;

    $response = Response::json([
      'error' => true,
      'data' => $data,
      'code' => $code
      ], $code
    );

    return $response;
  }

  // If the user is not a member of group Admin s/he doesn't have access
  if( ! Neo::isAuthorized($token, 'Admin') ){
    $data = ['Not authorized'];
    $code = 401;

    $response = Response::json([
      'error' => true,
      'data' => $data,
      'code' => $code
      ], $code
    );

    return $response;
  }

});