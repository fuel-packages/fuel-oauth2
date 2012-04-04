<?php
/**
 * OAuth2 Server Model
 * 
 * @package    FuelPHP/OAuth2
 * @category   Server Model
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 */

namespace OAuth2;

/*
db.oauth_clients.insert({name : "Testing", client_id : 123456789, client_secret : "secret", redirect_uri : "http://local.pyrocms/index.php/social/callback/blooie", status : "approved"});
db.oauth_scopes.insert({'scope' : 'user.profile', name : 'Profile', 'Description' : 'Show profile...sdf jskfbjksdf'});
db.oauth_scopes.insert({'scope' : 'user.picture', name : 'Picture', 'Description' : 'Picture of a user'});
db.oauth_scopes.insert({'scope' : 'chat.history', name : 'Chat History', 'Description' : 'Chat history and stuff'});
db.oauth_scopes.insert({'scope' : 'statistics', name : 'Statistics', 'Description' : 'Various statistics'});
*/

class Model_Server_Mongo extends Model_Server
{
	const COLLECTION_CLIENT = 'oauth_clients';
	const COLLECTION_SESSIONS = 'oauth_sessions';
	const COLLECTION_SCOPES = 'oauth_scopes';

	public function __construct($instance = 'default')
	{
		$this->mongo = \Mongo_Db::instance($instance);
	}

	public function get_client(array $where)
	{	
		$client = $this->mongo
			->select(array('name', 'client_id', 'auto_approve'))
			->where($where)
			->get_one(static::COLLECTION_CLIENT);

		return $client ? (object) $client : false;
	}

	public function get_session(array $where)
	{					
		$session = $this->mongo
			->select(array('id', 'type_id'))
			->where($where)
			->get_one(static::COLLECTION_SESSIONS);

		return $session ? (object) $session : false;
	}

	public function get_token_from_session($session_id)
	{
		$token = (object) $this->mongo
			->select('access_token')
			->where(array('id' => $session_id))
			->where_ne('access_token', null)
			->get_one(static::COLLECTION_SESSIONS);

		return isset($token->access_token) ? $token->access_token : false;
	}

	public function has_user_authenicated_client($client_id, $user_id)
	{
		$token = (object) $this->mongo
			->select(array('access_token'))
			->where(array(
				'client_id' => $client_id,
				'type_id' => $user_id,
				'type' => 'user',
			))
			->where_ne('access_token', null)
			->get_one(static::COLLECTION_SESSIONS);

		return isset($token->access_token) ? $token->access_token : false;
	}

	public function has_scope($access_token, $scope)
	{
		return (bool) $this->mongo
			->select(array('id'))
			->where(array(
				'access_token' => $access_token,
				'scopes' => $scope,
			))
			->get_one(static::COLLECTION_SESSIONS);
	}

	public function new_session(array $values, array $scopes)
	{
		return $this->mongo->insert(static::COLLECTION_SESSIONS, array_merge($values, array(
			'scopes' => $scopes,
		)));
	}

	public function update_session(array $where, array $values)
	{
		return $this->mongo
			->where($where)
			->update(static::COLLECTION_SESSIONS, $values);
	}

	public function create_access_token($session_id)
	{
		$access_token = sha1(time().uniqid());

		// Update the OAuth session
		$this->update_session(array(	
			'id' 			=> $session_id
		), array(
			'code'			=> null,
			'access_token'	=> $access_token,
			'last_updated'	=> time(),
			'stage'			=> 'granted'
		));

		return $access_token;
	}

	public function delete_session(array $where)
	{
		return $this->mongo
			->where($where)
			->delete(static::COLLECTION_SESSIONS);
	}

	// Scopes

	public function get_scope($scope)
	{
		if (is_array($scope))
		{
			return $this->mongo
				->where_in('scope', $scope)
				->get(static::COLLECTION_SCOPES);
		}

		else
		{
			return $this->mongo
				->where(array('scope' => $scope))
				->get(static::COLLECTION_SCOPES);
		}
	}

}