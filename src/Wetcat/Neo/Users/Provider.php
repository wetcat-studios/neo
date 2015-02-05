<?php namespace Wetcat\Neo\Users;

/**
 * "Stolen" fron Cartalyst/Sentry. =)
 *
 * Part of the Sentry package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Sentry
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Wetcat\Neo\Users\ProviderInterface;
use Wetcat\Neo\Users\UserNotActivatedException;
use Wetcat\Neo\Users\UserNotFoundException;
use Wetcat\Neo\Users\WrongPasswordException;
use Wetcat\Neo\Users\InvalidTokenException;

use Config;
use Hash;
use Str;

use Neoxygen\NeoClient\ClientBuilder;
use Carbon\Carbon;

class Provider implements ProviderInterface {

  // Neo4j client
  protected $client;

  protected $attributes = [
    'firstname',
    'lastname',
    'email',
    'password',
    'token'
  ];

  /**
   * Create a new Neo User provider.
   *
   * @param  \Neoxygen\NeoClient\ClientBuilder  $client
   * @return void
   */
  public function __construct()
  {
    $this->alias   = Config::get('database.neo.default.alias', Config::get('neo::default.alias'));
    $this->scheme  = Config::get('database.neo.default.scheme', Config::get('neo::default.scheme'));
    $this->host    = Config::get('database.neo.default.host', Config::get('neo::default.host'));
    $this->port    = Config::get('database.neo.default.port', Config::get('neo::default.port'));
    $this->auth    = Config::get('database.neo.default.auth', Config::get('neo::default.auth'));
    $this->user    = Config::get('database.neo.default.user', Config::get('neo::default.user'));
    $this->pass    = Config::get('database.neo.default.pass', Config::get('neo::default.pass'));
    $this->timeout = Config::get('database.neo.default.timeout', Config::get('neo::default.timeout'));

    $this->client = ClientBuilder::create()
      ->addConnection($this->alias, $this->scheme, $this->host, $this->port, $this->auth, $this->user, $this->pass)
      ->setAutoFormatResponse(true)
      ->setDefaultTimeout($this->timeout)
      ->build();
  }

  /**
   * Finds a user by the given user ID.
   *
   * @param  mixed  $id
   * @return array
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function findById($id)
  {
    $query = "START n=node($id) RETURN n";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $userNode = $result->getSingleNode('User');

    if ( !$userNode )
    {
      throw new UserNotFoundException("A user could not be found with ID [$id].");
    }

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Finds a user by the email value.
   *
   * @param  string  $email
   * @return array
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function findByEmail($email)
  {
    $query = "MATCH (u:User {email: '$email'}) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $userNode = $result->getSingleNode('User');

    if ( !$userNode )
    {
      throw new UserNotFoundException("A user could not be found with a email value of [$email].");
    }

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Finds a user by the token value.
   *
   * @param  string  $token
   * @return array
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function findByToken($token)
  {
    $query = "MATCH (u:User {token: '$token'}) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $userNode = $result->getSingleNode('User');

    if ( !$userNode )
    {
      throw new InvalidTokenException("The token [$token] is invalid.");
    }

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Finds a user by credentials.
   *
   * @param  string  $token
   * @return array
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function findByCredentials(array $credentials)
  {
    if (empty($credentials['email'])) {
      throw new LoginRequiredException("The [email] attribute is required.");
    }

    if (empty($credentials['password'])) {
      throw new PasswordRequiredException('The [password] attribute is required.');
    }

    $email = $credentials['email'];

    $query = "MATCH (u:User {email: '$email'})-[r:MEMBER_OF]->(g:Group) RETURN u, g, r";
    $result = $this->client->sendCypherQuery($query)->getResult();
    
    $user = $result->getSingleNode('User');
    
    if( ! $user ) {
      throw new UserNotFoundException("User with [$email] could not be found.");
    }

    $relationships = $user->getRelationships('MEMBER_OF', 'OUT');

    $groups = [];

    foreach ($relationships as $rel) {
      $groups[] = [
        'name'  => $rel->getEndNode()->getProperty('name'),
        'since' => $rel->getProperty('since'),
      ];
    }

    if ( Hash::check($credentials['password'], $user->getProperty('password')) ) {
      $data = $user->getProperties($this->attributes);
      $data['groups'] = $groups;
      return $data;
    } else {
      throw new UserNotFoundException("A user could not be found with the given credentials.");
    }
  }

  /**
   * Finds a user by the given activation code.
   *
   * @param  string  $code
   * @return array
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  public function findByActivationCode($code)
  {
    if ( ! $code)
    {
      throw new \InvalidArgumentException("No activation code passed.");
    }

    $query = "MATCH (u:User {activationcode: '$code'}) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $nodes = $result->getNodes();


    if (($count = $result->getNodesCount()) > 1)
    {
      throw new \RuntimeException("Found [$count] users with the same activation code.");
    }

    if ( ! $userNode = $result->getSingleNode('User'))
    {
      throw new UserNotFoundException("A user was not found with the given activation code.");
    }

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Finds a user by the given reset password code.
   *
   * @param  string  $code
   * @return \Wetcat\Neo\Users\UserInterface
   * @throws RuntimeException
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function findByResetPasswordCode($code)
  {
    $query = "MATCH (u:User {resetcode: '$code'}) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $nodes = $result->getNodes();


    if (($count = $result->getNodesCount()) > 1)
    {
      throw new \RuntimeException("Found [$count] users with the same reset password code.");
    }

    if ( ! $userNode = $result->getSingleNode('User'))
    {
      throw new UserNotFoundException("A user was not found with the given reset password code.");
    }

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Returns an array containing all users.
   *
   * @return array
   */
  public function findAll()
  {
    $query = "MATCH (u:User) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $nodes = $result->getNodes();

    $data = [];

    foreach ($nodes as $node) {
      $data[] = $node->getProperties($this->attributes);
    }

    return $data;
  }

  /**
   * Returns all users who belong to
   * a group.
   *
   * @param  string  $group
   * @return array
   */
  public function findAllInGroup($group)
  {
    $query = "MATCH (u:User)-[:MEMBER_OF]->(g:Group {name: '$group'}) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $nodes = $result->getNodes();

    $data = [];

    foreach ($nodes as $node) {
      $data[] = $node->getProperties($this->attributes);
    }

    return $data;
  }

  /**
   * Creates a user.
   *
   * @param  array  $attrs
   * @return array
   */
  public function create(array $attrs)
  {
    if ( !array_key_exists("email", $attrs) ) {
      throw new LoginRequiredException("[email] is required");
    }

    if ( !array_key_exists("password", $attrs) ) {
      throw new PasswordRequiredException("[password] is required");
    }

    // Hash password
    $attrs['password'] = Hash::make($attrs['password']);
  
    // Create initial token
    $attrs['token'] = hash('sha256', Str::random(10), false);

    $query = "CREATE (u:User {";
    $len = count($attrs);
    $i = 0;
    foreach ($attrs as $key => $value) {
      $query .= $key.": '".$value."'";
      $i++;
      if( $i < $len ){
        $query .= ", ";
      }
    }
    $query .= "}) RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $userNode = $result->getSingleNode('User');

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Creates a user.
   *
   * @param  array  $attrs
   * @return array
   */
  public function update(array $attrs)
  {
    // TODO: This needs a credentials verification too!

    if ( !array_key_exists("email", $attrs) ) {
      throw new LoginRequiredException("[email] is required");
    }

    $email = $attrs['emails'];

    $query = "MATCH (u:User {email: '$email'})";

    // Loop through all the attributes and add them the node
    $len = count($attrs);
    foreach ($attrs as $key => $value) {
      $query .= "SET u.".$key."='".$value."' ";
    }
    $query .= "RETURN u";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $userNode = $result->getSingleNode('User');

    return $userNode->getProperties($this->attributes);
  }

  /**
   * Add a user (email) to a group (name)
   *
   * @param  string  $email
   * @param  string  $group
   */
  public function addToGroup($email, $group) {
    // Get the current timestamp
    $mytime = Carbon::now();
    $timestamp = $mytime->toDateTimeString();

    $query = "MATCH (u:User {email: '$email'})
              MATCH (g:Group {name: '$group'})
              CREATE UNIQUE (u)-[r:MEMBER_OF {since: '$timestamp'}]->(g)
              RETURN u, r, g";
    $result = $this->client->sendCypherQuery($query)->getResult();

    $nodes = [];
    $edges = [];
    
    $nodesPositions = [];
    
    $i = 0;
    foreach ($result->getNodes() as $node){
      if ( $node->getLabel() === 'User' ){
        $nodes[] = [
          'id'        => $node->getId(),
          'label'     => $node->getLabel(),
          'firstname' => $node->getProperty('firstname'),
          'lastname'  => $node->getProperty('lastname'),
          'email'     => $node->getProperty('email'),
        ];
      }else if ( $node->getLabel() === "Group"){
        $nodes[] = [
          'id'        => $node->getId(),
          'label'     => $node->getLabel(),
          'name'      => $node->getProperty('name'),
        ];
      }

      $nodesPositions[$node->getId()] = $i;
      $i++;
    }

    foreach ($result->getRelationships() as $rel){
      $edges[] = [
        'source' => $nodesPositions[$rel->getStartNode()->getId()],
        'target' => $nodesPositions[$rel->getEndNode()->getId()],
      ];
    }

    $data = [
        'nodes' => $nodes,
        'edges' => $edges
    ];

    return $data;
  }

  /**
   * Validate if a user is member of group
   */
  public function isMemberOf($email, $group)
  {
    $query = "MATCH (u:User {email: '$email'})-[r:MEMBER_OF]->(g:Group {name: '$group'}) RETURN count(g) as member";
    $result = $this->client->sendCypherQuery($query)->getRows();

    $member = $result['member'][0];

    return $member;
  }

  /*
   * Generate a new token for user
   */
  public function generateToken($email)
  {
    // TODO: This needs a credentials verification too!

    if ( ! $email ) {
      throw new LoginRequiredException("[email] is required");
    }

    // Generate new token
    $token = hash('sha256', Str::random(10), false);

    $query = "MATCH (u:User {email: '$email'}) SET u.token='$token'";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $userNode = $result->getSingleNode('User');

    return $token;
  }

  public function unsetToken($token) {
    $query = "MATCH (u:User {token: '$token'}) REMOVE u.token RETURN u";
    $result = $this->client->sendCypherQuery($query)->getResult();
    $user = $result->getSingleNode('User');

    if( ! $user ){
      throw new InvalidTokenException("Token [$token] is invalid.");
    }

    return $user->getProperties($this->attributes);
  }

}