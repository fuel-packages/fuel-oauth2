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

class Model_Server_DB extends Model_Server
{
	const TABLE_CLIENT = 'oauth_clients';
	const TABLE_SESSIONS = 'oauth_sessions';
	const TABLE_SESSION_SCOPES = 'oauth_session_scopes';
	const TABLE_SCOPES = 'oauth_scopes';

	public function get_client(array $where)
	{
		$clients = \DB::select('name', 'client_id', 'auto_approve')
			->from(static::TABLE_CLIENT)
			->where($where)
			->limit(1)
			->as_object()
			->execute();
						
		return isset($clients[0]) ? $clients[0] : false;
	}

	public function get_session(array $where)
	{
		$session = \DB::select('id', 'type_id')
			->from(static::TABLE_SESSIONS)
			->where($where)
			->limit(1)
			->as_object()
			->execute();
						
		return isset($session[0]) ? $session[0] : false;
	}

	public function get_token_from_session($session_id)
	{
		$tokens = \DB::select('access_token')
			->where('id', $session_id)
			->where('access_token', '!=', null)
			->from(static::TABLE_SESSIONS)
			->limit(1)
			->as_object()
			->execute();

		return isset($tokens[0]) ? $tokens[0]->access_token : false;
	}

	public function has_user_authenicated_client($client_id, $user_id)
	{
		$tokens = \DB::select('access_token')
			->where('client_id', $client_id)
			->where('type_id', $user_id)
			->where('type', 'user')
			->where('access_token', '!=', '')
			->where('access_token', '!=', null)
			->from(static::TABLE_SESSIONS)
			->limit(1)
			->as_object()
			->execute();

		return isset($tokens[0]) ? $tokens[0]->access_token : false;
	}

	public function has_scope($access_token, $scope)
	{
		$has_any = \DB::select('id')
			->where('access_token', $access_token)
			->where('scope', $scope)
			->from(static::TABLE_SESSION_SCOPES)
			->as_array()
			->execute();

		return (bool) $has_any;
	}

	public function new_session(array $values, array $scopes)
	{
		// Set the session values
		$result = \DB::insert(static::TABLE_SESSIONS)
			->set($values)
			->execute();

		// Crazh Kohana legacy shit to get the insert_id
		$session_id = $result[0];
			
		// Add the scopes
		foreach ($scopes as $scope)
		{
			if (trim($scope) !== "")
			{
				\DB::insert(static::TABLE_SESSION_SCOPES)
					->set(array(
						'session_id'	=>	$session_id,
						'scope'			=>	$scope
					));
			}
		}
	}

	public function update_session(array $where, array $values)
	{
		return \DB::update(static::TABLE_SESSIONS)
			->set($values)
			->where($where)
			->execute();
	}

	public function create_access_token($session_id)
	{
		$access_token = sha1(time().uniqid());

		// Update the OAuth session
		$this->update_session(array(	
			'id' => $session_id
		), array(
			'code'			=>	NULL,
			'access_token'	=>	$access_token,
			'last_updated'	=>	time(),
			'stage'			=>	'granted'
		));

		\DB::update(static::TABLE_SESSION_SCOPES)
			->where('session_id', $session_id)
			->set(array('access_token' => $access_token))
			->execute();

		return $access_token;
	}

	public function delete_session(array $where)
	{
		return \DB::delete(static::TABLE_SESSIONS)
			->where($where)
			->execute();
	}

	// Scopes

	public function get_scope($scope)
	{
		$query = \DB::select()->from(static::TABLE_SCOPES);

		if (is_array($scope))
		{
			return $query
				->where('scope', 'IN', (array) $scope)
				->execute()
				->as_array();
		}

		else
		{
			$details = $query
				->where('scope', '=', $scope)
				->limit(1)
				->execute()
				->as_array();

			return $details ? current($details) : false;
		}
	}

}