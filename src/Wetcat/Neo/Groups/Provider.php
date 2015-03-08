<?php namespace Wetcat\Neo\Groups;

/**
 * "Stolen" from Cartalyst/Sentry. =)
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

use Wetcat\Neo\Groups\GroupInterface;
use Wetcat\Neo\Groups\GroupNotFoundException;
use Wetcat\Neo\Groups\ProviderInterface;

use Config;

use Neoxygen\NeoClient\ClientBuilder;

use Webpatser\Uuid;

class Provider implements ProviderInterface {

  // Neo4j client
  protected $client;

  protected $attributes = [
    'name',
    'level',

    'created_at',
    'updated_at',
    'deleted_at'
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
   * Find the group by ID.
   *
   * @param  int  $id
   * @return \Cartalyst\Sentry\Groups\GroupInterface  $group
   * @throws \Cartalyst\Sentry\Groups\GroupNotFoundException
   */
  public function findById($id)
  {
    $query = "START n=node($id) RETURN n";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $groupNode = $result->getSingleNode('Group');

    if ( !$groupNode )
    {
      throw new GroupNotFoundException("A group could not be found with ID [$id].");
    }

    return $groupNode->getProperties($this->attributes);
  }

  /**
   * Find the group by name.
   *
   * @param  string  $name
   * @return array  $group
   * @throws \Wetcat\Neo\Groups\GroupNotFoundException
   */
  public function findByName($name)
  {
    $query = "MATCH (g:Group {name: '$name'}) RETURN g";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $groupNode = $result->getSingleNode('Group');

    if ( !$groupNode )
    {
      throw new GroupNotFoundException("A group could not be found with the name [$name].");
    }

    return $groupNode->getProperties($this->attributes);
  }

  /**
   * Returns all groups.
   *
   * @return array  $groups
   */
  public function findAll()
  {
    $query = "MATCH (g:Group) RETURN g";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $nodes = $result->getNodes();

    $data = [];

    foreach ($nodes as $node) {
      $data[] = $node->getProperties($this->attributes);
    }

    return $data;
  }

  /**
   * Find all users of a group
   */
  public function findAllInGroup($group)
  {
    $query = "MATCH (g:Group {name: '$group'})<-[r:MEMBER_OF]-(u:User) RETURN u";
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
      }

      $nodesPositions[$node->getId()] = $i;
      $i++;
    }
    
    return $nodes;
  }

  /**
   * Creates a group.
   *
   * @param  array  $attributes
   * @return \Cartalyst\Sentry\Groups\GroupInterface
   */
  public function create(array $attrs)
  {
    // Create a unique ID
    $uuid = Uuid::generate(4);
    $attrs['uuid'] = $uuid->string;

    $query = "CREATE (g:Group {";
    $len = count($attrs);
    $i = 0;
    foreach ($attrs as $key => $value) {
      $query .= $key.": '".$value."'";
      $i++;
      if( $i < $len ){
        $query .= ", ";
      }
    }
    $query .= "}) RETURN g";

    $result = $this->client->sendCypherQuery($query)->getResult();
    $groupNode = $result->getSingleNode('Group');

    return $groupNode->getProperties($this->attributes);
  }

}