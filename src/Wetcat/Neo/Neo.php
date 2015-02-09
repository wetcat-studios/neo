<?php namespace Wetcat\Neo;

use Wetcat\Neo\Users\ProviderInterface as UserProviderInterface;
use Wetcat\Neo\Groups\ProviderInterface as GroupProviderInterface;

use Wetcat\Neo\NotAuthenticatedException;
use Wetcat\Neo\NotAuthorizedException;

use Wetcat\Neo\Users\InvalidTokenException;
use Wetcat\Neo\Users\PasswordRequiredException;
use Wetcat\Neo\Users\LoginRequiredException;
use Wetcat\Neo\Users\UserNotFoundException;

use Neoxygen\NeoClient\ClientBuilder;

class Neo {
 
  // Neo4j client
  protected $client;

  protected $groupProvider;

  protected $userProvider;

  /**
   * Create a new Neo object.
   */
  public function __construct(
    UserProviderInterface $userProvider = null,
    GroupProviderInterface $groupProvider = null,
    $alias = 'default',
    $scheme = 'http',
    $host = 'localhost',
    $port = 7474,
    $auth = false,
    $user = null,
    $pass = null,
    $timeout = 25
  )
  {
    $this->client = ClientBuilder::create()
      ->addConnection($alias, $scheme, $host, $port, $auth, $user, $pass)
      ->setAutoFormatResponse(true)
      ->setDefaultTimeout($timeout)
      ->build();

    $this->userProvider     = $userProvider ?: new UserProvider($this->client);
    $this->groupProvider    = $groupProvider ?: new GroupProvider($this->client);
  }

  public function unique($label, $prop) {
    return $this->client->createUniqueConstraint($label, $prop);
  }

  public function dropUnique($label, $prop) {
    return $this->client->dropUniqueConstraint($label, $prop);
  }

  public function index($label, $prop) {
    return $this->client->createIndex($label, $prop);
  }

  public function dropIndex($label, $prop) {
    return $this->client->dropIndex($label, $prop);
  }

  public function root() {
    return $this->client->getRoot();
  }

  public function allNodes() {
    $query = "MATCH (n) RETURN n";
    $result = $this->client->sendCypherQuery($query)->getResult();
    return $result->getNodes();
  }

  public function getGraph() {
    $query = 'MATCH (a)<-[r]-(b) RETURN a, r, b';

    $result = $this->client->sendCypherQuery($query)->getResult();
    
    $nodes = [];
    $edges = [];
    
    $nodesPositions = [];
    
    $i = 0;
    foreach ($result->getNodes() as $node){
        $nodes[] = [
          'id' => $node->getId(),
          'label' => $node->getLabel()
        ];
        $nodesPositions[$node->getId()] = $i;
        $i++;
    }

    foreach ($result->getRelationships() as $rel){
        $edges[] = [
            'source' => $nodesPositions[$rel->getStartNode()->getId()],
            'target' => $nodesPositions[$rel->getEndNode()->getId()]
        ];
    }

    $data = [
        'nodes' => $nodes,
        'edges' => $edges
    ];

    return $data;
  }

  public function query($query) {
    return $this->client->sendCypherQuery($query)->getResult();
  }

  /**
   * Gets the user provider for Neo.
   *
   * @return \Wetcat\Neo\Users\ProviderInterface
   */
  public function getUserProvider()
  {
    return $this->userProvider;
  }

  /**
   * Gets the group provider for Neo.
   *
   * @return \Wetcat\Neo\Groups\ProviderInterface
   */
  public function getGroupProvider()
  {
    return $this->groupProvider;
  }


  // Auth stuff
  /**
   * Attempts to authenticate the given user according to the passed credentials.
   * If it fails it will throw exceptions.
   *
   * @param  array  $credentials
   * @param  string  $token
   * @throws \Wetcat\Neo\Users\LoginRequiredException
   * @throws \Wetcat\Neo\Users\PasswordRequiredException
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function authenticate(array $credentials)
  {
    if (empty($credentials['email'])) {
      throw new LoginRequiredException("The [email] attribute is required.");
      //return false;
    }
    if (empty($credentials['password'])) {
      throw new PasswordRequiredException('The [password] attribute is required.');
      //return false;
    }

    try {
      $user = $this->userProvider->findByCredentials($credentials);
      $token = $this->userProvider->generateToken($user['email']);

      return [
        'userId' => $user['email'],
        'token'  => $token,
        'permissions' => $user['groups']
      ];

    } catch (UserNotFoundException $e) {
      throw $e;
      //return false;
    }
    
//    $user->clearResetPassword();

    //return true;
  }

  /**
   * Gets the group provider for Neo.
   *
   * @return bool
   */
  public function isAuthenticated($token) {
    // Attempting to find the user will automatically throw errors if unsuccessful
    try {
      $user = $this->userProvider->findByToken($token);

      if( ! (array_key_exists('email', $user) && array_key_exists('password', $user)) ){
        //throw new NotAuthenticatedException("The user with token [$token] is not authenticated.");
        return false;
      } else {
        return true;
      }
    } catch (\Wetcat\Neo\Users\InvalidTokenException $e) {
      return false;
    }
  }

  /**
   * Gets the group provider for Neo.
   *
   * @return bool
   */
  public function isAuthorized($token, $group) {
    $hasPermission = false;

    $user = false;
    $requiredGroup = false;

    // Attempting to find the user will automatically throw errors if unsuccessful
    try {
      if( $group !== '*' ){
        // Get the needed group
        $requiredGroup = $this->groupProvider->findByName($group);        
      }

      // Get the user, including the memberships
      $user = $this->userProvider->findByToken($token);

      //$data = $this->userProvider->isMemberOf($user['email'], $group);
    } catch (\Wetcat\Neo\Users\InvalidTokenException $e) {
      $hasPermission = false;
    }
    
    // If the group is '*' it means everyone has permission, but has to be authenticated!
    // That's why we do this check after the token-check
    if ( $group === '*' ) {
      return true;
    }

    if( !$user || !$requiredGroup ) {
      $hasPermission = false;
    } else {
      // Compare user permissions to required
      foreach ($user['groups'] as $group) {
        if( $group['level'] >= $requiredGroup['level'] ){
          $hasPermission = true;
        }
      }
    }

    return $hasPermission;
  }

  /**
   * Attempt to unset token
   */
  public function logout($token) {
    try {
      $this->userProvider->unsetToken($token);
    } catch (\Wetcat\Neo\Users\InvalidTokenException $e) {
      return false;
    } 
  }
 
}