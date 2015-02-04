<?php namespace Wetcat\Neo;

use Wetcat\Neo\Users\ProviderInterface as UserProviderInterface;
use Wetcat\Neo\Groups\ProviderInterface as GroupProviderInterface;
use Wetcat\Neo\NotAuthenticatedException;
use Wetcat\Neo\NotAuthorizedException;
use Wetcat\Neo\Users\InvalidTokenException;

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
   * Attempts to authenticate the given user
   * according to the passed credentials.
   *
   * @param  array  $credentials
   * @param  bool   $remember
   * @return bool
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
      throw new PasswordRequiredException('The password attribute is required.');
      //return false;
    }

    try {
      $user = $this->userProvider->findByCredentials($credentials);
    } catch (UserNotFoundException $e) {
      throw $e;
      //return false;
    }
    
//    $user->clearResetPassword();

    return true;
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
    // Attempting to find the user will automatically throw errors if unsuccessful
    try {
      $user = $this->userProvider->findByToken($token);
      $data = $this->userProvider->isMemberOf($user['email'], $group);
    } catch (\Wetcat\Neo\Users\InvalidTokenException $e) {
      return false;
    }
    

    if( $data === 0 ) {
      //throw new NotAuthorizedException("The user with token [$token] is not authorized [$group]");
      return false;
    } else if( $data === 1 ) {
      return true;
    }
  }
 
}