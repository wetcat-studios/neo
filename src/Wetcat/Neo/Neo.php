<?php namespace Wetcat\Neo;
 
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
    $this->userProvider     = $userProvider ?: new UserProvider;
    $this->groupProvider    = $groupProvider ?: new GroupProvider;

    $this->client = ClientBuilder::create()
      ->addConnection($alias, $scheme, $host, $port, $auth, $user, $pass)
      ->setAutoFormatResponse(true)
      ->setDefaultTimeout($timeout)
      ->build();
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
 
}