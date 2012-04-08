<?php

/**
 * OAuth Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Server
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace OAuth2;

class Server {
	
	public static function forge(Model_Server $model = null)
	{
		return new static($model);
	}

	public function __construct(Model_Server $model = null)
	{
		$this->model = $model === null ? new Model_Server_Db : $model;
	}

	/**************************************************************
	//! Client stuff
	**************************************************************/
	
	/**
	 * Validates a client's credentials
	 * 
	 * @param string $client_id
	 * @param mixed $client_secret
	 * @param mixed $redirect_uri
	 * @return bool|object
	 */
	public function validate_client($client_id = "", $client_secret = NULL, $redirect_uri = NULL)
	{
		$params = array(
			'client_id' => $client_id,
		);
		
		if ($client_secret !== NULL)
		{
			$params['client_secret'] = $client_secret;
		}
		
		if ($redirect_uri !== NULL)
		{
			$params['redirect_uri'] = $redirect_uri;
		}
	
		// find the client using the parameters
		if (($client = $this->model->get_client($params)))
		{
			return $client;
		}

		return false;
	}
	
	/**************************************************************
	//! Auth code stuff
	**************************************************************/
	
	/**
	 * Generates a new authorise code once a user has approved an application
	 * 
	 * @param mixed $client_id
	 * @param mixed $user_id
	 * @param mixed $redirect_uri
	 * @param array $scopes
	 * @return string
	 */
	public function new_auth_code($client_id = '', $user_id = '', $redirect_uri = '', $scopes = array(), $access_token = NULL)
	{
		// Update an existing session with the new code
		if ($access_token)
		{
			$code = md5(time().uniqid());
			
			$this->model->update_session(array(
				'type_id'		=> $user_id,
				'type'			=> 'user',
				'client_id'		=> $client_id,
				'access_token'	=> $access_token
			), array(
				'code'			=> $code,
				'stage'			=> 'request',
				'redirect_uri'	=> $redirect_uri, // The applications redirect URI may have been updated
				'last_updated'	=> time(),
			));
			
			return $code;
		}
		
		// Create a new oauth session
		else
		{
			// Delete any existing sessions just to be sure
			$this->model->delete_session(array(
				'client_id'		=> $client_id,
				'type_id'		=> $user_id,
				'type'			=> 'user'
			));
		
			$code = md5(time().uniqid());
			
			$this->model->new_session(array(
				'client_id'			=> $client_id,
				'redirect_uri'		=> $redirect_uri,
				'type_id'			=> $user_id,
				'type'				=> 'user',
				'code'				=> $code,
				'first_requested'	=> time(),
				'last_updated'		=> time(),
				'access_token'		=> NULL,
			), $scopes);
		}
		
		return $code;
	}
	
	
	/**
	 * validate_auth_code function.
	 * 
	 * @param string $code
	 * @param string $client_id
	 * @param string $redirect_uri
	 * @return bool|int
	 */
	public function validate_auth_code($code = '', $client_id = '', $redirect_uri = '')
	{
		$session = $this->model->get_session(array(
			'client_id'		=> $client_id,
			'redirect_uri'	=> $redirect_uri, 
			'code'			=> $code
		));

		return $session ?: false;
	}
	
	/**************************************************************
	//! Access token stuff
	**************************************************************/	
	/**
	 * Generates a new access token (or returns an existing one)
	 * 
	 * @param string $session_id. (default: '')
	 * @return string
	 */
	public function get_access_token($session_id = '')
	{
		// Check if an access token exists already
		$token = $this->model->get_token_from_session($session_id);
		
		// If an access token already exists, return it and remove the authorization code
		if ($token)
		{
			// Remove the authorization code
			$this->model->update_session(array(
				'id' => $session_id
			), array(
				'code'	=>	NULL,
				'stage'	=>	'granted'
			));
			
			// Return the access token
			return $token->access_token;
		}
		
		// An access token doesn't exist yet so create one and remove the authorization code
		else
		{
			return $this->model->create_access_token($session_id);
		}
	}
		
	/**
	 * Validates an access token
	 * 
	 * @param string $access_token. (default: "")
	 * @param array $scope. (default: array())
	 * @return void
	 */
	public function validate_access_token($access_token = '', $scopes = array())
	{
		// Validate the token exists
		$token = $this->model->get_session(array(
			'access_token'	=>	$access_token
		));
		
		// The access token doesn't exists
		if ( ! $token)
		{
			return FALSE;
		}

		// The access token does exist, validate each scope
		if (count($scopes) > 0)
		{
			foreach ($scopes as $scope)
			{
				if ( ! $this->model->has_scope($access_token, $scope))
				{
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}	
	
	/**
	 * Tests if a user has already authorized an application and an access token has been granted
	 * 
	 * @param string $user_id
	 * @param string $client_id
	 * @return bool
	 */
	public function access_token_exists($user_id = '', $client_id = '')
	{
		return $this->model->has_user_authenicated_client($user_id, $client_id);
	}
	
	/**************************************************************
	//! Miscellaneous stuff
	**************************************************************/

	public function scope_exists($scope)
	{
		return (bool) $this->model->get_scope($scope);
	}
	
	public function get_scope($scope)
	{
		return $this->model->get_scope($scope);	
	}
	
	/**************************************************************
	//! Miscellaneous stuff
	**************************************************************/
	
	/**
	 * Generates the redirect uri with appended params
	 * 
	 * @param string $redirect_uri. (default: "")
	 * @param array $params. (default: array())
	 * @return string
	 */
	public function redirect_uri($redirect_uri = '', $params = array(), $query_delimeter = '?')
	{
		if (strstr($redirect_uri, $query_delimeter))
		{
			return $redirect_uri . http_build_query($params);
		}
		else
		{
			return $redirect_uri . $query_delimeter . http_build_query($params);
		}
	}
		
}