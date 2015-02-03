<?php namespace Wetcat\Neo\Users;
/**
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

class Provider implements ProviderInterface {

  protected $attributes = [
    'firstname',
    'lastname',
    'email',
    'password',
    'token'
  ];

  /**
   * Finds a user by the given user ID.
   *
   * @param  mixed  $id
   * @return array
   * @throws \Wetcat\Neo\Users\UserNotFoundException
   */
  public function findById($id)
  {
    $result = Neo::query("START n=node($id) RETURN n");
    $userNode = $result->getSingleNode('User');

    if ( !$userNode )
    {
      throw new UserNotFoundException("A user could not be found with ID [$id].");
    }

    return $userNode->getProperties($attributes);
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
    $result = Neo::query("MATCH (u:User {email: '$email'}) RETURN u");
    $userNode = $result->getSingleNode('User');

    if ( !$userNode )
    {
      throw new UserNotFoundException("A user could not be found with a email value of [$email].");
    }

    return $userNode->getProperties($attributes);
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

    $result = Neo::query("MATCH (u:User {activationcode: '$code'}) RETURN u");
    $nodes = $result->getNodes();


    if (($count = $result->getNodesCount()) > 1)
    {
      throw new \RuntimeException("Found [$count] users with the same activation code.");
    }

    if ( ! $userNode = $result->getSingleNode('User'))
    {
      throw new UserNotFoundException("A user was not found with the given activation code.");
    }

    return $userNode->getProperties($attributes);
  }

  /**
   * Finds a user by the given reset password code.
   *
   * @param  string  $code
   * @return \Cartalyst\Sentry\Users\UserInterface
   * @throws RuntimeException
   * @throws \Cartalyst\Sentry\Users\UserNotFoundException
   */
  public function findByResetPasswordCode($code)
  {
    $result = Neo::query("MATCH (u:User {resetcode: '$code'}) RETURN u");
    $nodes = $result->getNodes();


    if (($count = $result->getNodesCount()) > 1)
    {
      throw new \RuntimeException("Found [$count] users with the same reset password code.");
    }

    if ( ! $userNode = $result->getSingleNode('User'))
    {
      throw new UserNotFoundException("A user was not found with the given reset password code.");
    }

    return $userNode->getProperties($attributes);
  }

  /**
   * Returns an array containing all users.
   *
   * @return array
   */
  public function findAll()
  {
    $result = Neo::query("MATCH (u:User) RETURN u");
    $nodes = $result->getNodes();

    $data = [];

    foreach ($nodes as $node) {
      $data[] = $node->getProperties($attributes);
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
    $result = Neo::query("MATCH (u:User)-[:MEMBER_OF]->(g:Group {name: '$group'}) RETURN u");
    $nodes = $result->getNodes();

    $data = [];

    foreach ($nodes as $node) {
      $data[] = $node->getProperties($attributes);
    }

    return $data;
  }

  /**
   * Creates a user.
   *
   * @param  array  $attrs
   * @return array
   */
  public static function create(array $attrs)
  {
    $query = "CREATE (u:User {";
    foreach ($attrs as $key => $value) {
      $query .= $key.": '".$value."'";
    }
    $query .= "}) RETURN u";

    $result = Neo::query($query);
    $userNode = $result->getSingleNode('User');

    return $userNode->getProperties($attributes);
  }

}