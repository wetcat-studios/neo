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

class Provider implements ProviderInterface {

  protected $attributes = [
    'name',
  ];

  /**
   * Find the group by ID.
   *
   * @param  int  $id
   * @return \Cartalyst\Sentry\Groups\GroupInterface  $group
   * @throws \Cartalyst\Sentry\Groups\GroupNotFoundException
   */
  public function findById($id)
  {
    $result = Neo::query("START n=node($id) RETURN n");
    $groupNode = $result->getSingleNode('Group');

    if ( !$groupNode )
    {
      throw new GroupNotFoundException("A group could not be found with ID [$id].");
    }

    return $groupNode->getProperties($attributes);
  }

  /**
   * Find the group by name.
   *
   * @param  string  $name
   * @return \Cartalyst\Sentry\Groups\GroupInterface  $group
   * @throws \Cartalyst\Sentry\Groups\GroupNotFoundException
   */
  public function findByName($name)
  {
    $result = Neo::query("MATCH (g:Group {name: '$name'}) RETURN g");
    $groupNode = $result->getSingleNode('Group');

    if ( !$groupNode )
    {
      throw new GroupNotFoundException("A group could not be found with the name [$name].");
    }

    return $groupNode->getProperties($attributes);
  }

  /**
   * Returns all groups.
   *
   * @return array  $groups
   */
  public function findAll()
  {
    $result = Neo::query("MATCH (g:Group) RETURN g");
    $nodes = $result->getNodes();

    $data = [];

    foreach ($nodes as $node) {
      $data[] = $node->getProperties($attributes);
    }

    return $data;
  }

  /**
   * Creates a group.
   *
   * @param  array  $attributes
   * @return \Cartalyst\Sentry\Groups\GroupInterface
   */
  public function create(array $attributes)
  {
    $query = "CREATE (g:Group {";
    foreach ($attrs as $key => $value) {
      $query .= $key.": '".$value."'";
    }
    $query .= "}) RETURN g";

    $result = Neo::query($query);
    $groupNode = $result->getSingleNode('Group');

    return $groupNode->getProperties($attributes);
  }

}