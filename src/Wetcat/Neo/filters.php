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




try {
  $data = [
    'error' => false,
    'data'  => Neo::getUserProvider()->findAll(),
    'code'  => 200
  ];
  $code = 200;
  
} catch ( Neoxygen\NeoClient\Exception\Neo4jException $e) {
  $data = [
    'error' => true,
    'data'  => [$e->getMessage()],
    'code'  => 404
    ];
  $code = 404;
}

return Response::json($data, $code);


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