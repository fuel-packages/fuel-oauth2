<?php
/**
 * OAuth 2.0 controller example
 *
 * @author              Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com
 * @copyright   		Copyright (c) 2011, Alex Bilbie.
 * @license             http://www.opensource.org/licenses/mit-license.php
 */

class Controller_Oauth extends Controller_Template
{
	public function before()
	{
		parent::before();

		// This will set up your server, and by default use your default DB connection
		$this->oauth = \OAuth2\Server::forge();

		// Use this for a Mongo based connection
		// $this->oauth = \OAuth2\Server::forge(new \OAuth2\Model_Server_Mongo('default'));
	}

	/**
	 * This is the function that users are sent to when they first enter the flow
	 */
	public function action_index()
	{
		// Get query string parameters
		$params = array();
		
		// Client id
		if ($client_id = Input::get('client_id'))
		{
			$params['client_id'] = trim($client_id);
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See client_id.', NULL, array(), 400);
			return;
		}
		
		// Client redirect uri
		if ($redirect_uri = Input::get('redirect_uri'))
		{
			$params['redirect_uri'] = trim($redirect_uri);
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See redirect_uri.', NULL, array(), 400);
			return;
		}
		
		// Validate the response type
		if ($response_type = Input::get('response_type'))
		{
			$response_type = trim($response_type);
			$valid_response_types = array('code'); // array to allow for future expansion
			
			if ( ! in_array($response_type, $valid_response_types))
			{
				$this->_fail('unsupported_response_type', 'The authorization server does not support obtaining the an authorization code using this method. Supported response types are \'' . implode('\' or ', $valid_response_type) . '\'.', $params['redirect_uri'], array(), 400);
				return;
			}
			
			else
			{
				$params['response_type'] = $response_type;
			}
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See response_type.', NULL, array(), 400);
			return;
		}
				
		// Validate client_id and redirect_uri
		$client_details = $this->oauth->validate_client($params['client_id'], NULL, $params['redirect_uri']); // returns object or FALSE
		
		if ($client_details === FALSE)
		{
			$this->_fail('unauthorized_client', 'The client is not authorized to request an authorization code using this method.', NULL, array(), 403);
			return;
		}
		
		else
		{
			// The client is valid, save the details to the session
			Session::set('oauth.client', $client_details);
		}

		
		// Get and validate the scope(s)
		if ($scope_string = Input::get('scope'))
		{
			$scopes = explode(',', $scope_string);
			$params['scope'] = $scopes;
		}
		
		else
		{
			$params['scope'] = array();
		}
		
		// Check scopes are valid
		if (count($params['scope']) > 0)
		{
			foreach($params['scope'] as $s)
			{
				$exists = $this->oauth->scope_exists($s);
				if ( ! $exists)
				{
					$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See scope \''.$s.'\'.', NULL, array(), 400);
					return;
				}
			}
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See scope.', NULL, array(), 400);
			return;
		}

		// The client is valid, save the details to the session
		Session::set('oauth.client', $client_details);
		Session::delete('oauth.user');
		
		// Get the scope
		$params['state'] = Input::get('state') ? trim(Input::get('state')) : '';

		// Save the params in the session
		Session::set('oauth.params', $params);
		
		// Redirect the user to sign in
		Response::redirect('oauth/sign_in');
	}
	
	public function action_sign_in()
	{
		$user = Session::get('oauth.user');
		$client = Session::get('oauth.client');

		// Check if user is signed in, if so redirect them on to /authorise
		if ($user && $client)
		{
			Response::redirect('oauth/authorise');
		}
		
		// Check there is are client parameters are stored
		if ($client === NULL)
		{
			$this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
			return;
		}
		
		// Errors
		$vars = array(
			'error' => FALSE,
			'error_messages' => array(),
			'client_name' => $client->name,
		);
		
		// If the form has been posted
		if (Input::post('validate_user'))
		{
			$u = trim(Input::post('username'));
			$p = trim(Input::post('password'));
			
			// Validate username and password
			if ($u === FALSE || empty($u))
			{
				$vars['error_messages'][] = 'The username field should not be empty';
				$vars['error'] = TRUE;
			}
			
			if ($p === FALSE || empty($p))
			{
				$vars['error_messages'][] = 'The password field should not be empty';
				$vars['error'] = TRUE;
			}
			
			// Check login and get credentials
			if ($vars['error'] === FALSE)
			{
				$user = Sentry::validate_user($u, $p, 'password');
				
				if ($user === FALSE)
				{
					$vars['error_messages'][] = 'Invalid username and/or password';
					$vars['error'] = TRUE;
				}
				
				else
				{
					Session::set('oauth.user', (object) array(
						'id' => $user->id,
						'username' => $user->username,
						'email' => $user->email,
						'non_ad_user' => TRUE
					));
				}
			}

			// If there is no error then the user has successfully signed in
			if ($vars['error'] === FALSE)
			{
				Response::redirect('oauth/authorise');
			}
		}
		
		$this->template->body = View::forge('oauth/sign_in', $vars);
	}
	
	
	/**
	 * Sign the user out of the SSO service
	 */
	public function action_sign_out()
	{
		Session::destroy();
		
		if ($redirect_uri = Input::get('redirect_uri'))
		{
			Response::redirect($redirect_uri);
		}
		
		else
		{
			$this->template->body = View::forge('oauth_server/sign_out');
		}
	}
	
	
	/**
	 * When the user has signed in they will be redirected here to approve the application
	 */
	public function action_authorise()
	{
		$user = Session::get('oauth.user');
		$client = Session::get('oauth.client');
		$params = Session::get('oauth.params');
		
		// Check if the user is signed in
		if ($user === NULL)
		{
			Session::set('sign_in_redirect', 'oauth/authorise');
			Response::redirect('oauth/sign_in');
		}
		
		// Check the client params are stored
		if ($client === NULL)
		{
			$this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
			return;
		}
		
		// Check the request parameters are still stored
		if ($params === NULL)
		{
			$this->_fail('invalid_request', 'No client details have been saved. Have you deleted your cookies?', NULL, array(), 400);
			return;
		}
		
		// Has the user authorised the application?
		if (($doauth = Input::post('doauth')))
		{		
			switch ($doauth)
			{
				// The user has approved the application.
				case "Approve":
					$authorised = FALSE;
					$action = 'newrequest';		
				break;
				
				// The user has denied the application
				case "Deny":
				
					$error_params = array(
						'error' => 'access_denied',
						'error_description' => 'The resource owner or authorization server denied the request.'
					);

					if ($params['state'])
					{
						$error_params['state'] = $params['state'];
					}				
					
					$redirect_uri = $this->oauth->redirect_uri($params['redirect_uri'], implode('&', $error_params));
					Session::delete(array('params','oauth.client', 'sign_in_redirect'));
					Response::redirect($redirect_uri);
					
				break;
				
			}
		}
		
		else
		{
			// Does the user already have an access token?
			$authorised = $this->oauth->access_token_exists($user->id, $client->client_id);
			
			if ($authorised)
			{
				$match = $this->oauth->validate_access_token($authorised->access_token, $params['scope']);
				$action = $match ? 'finish' : 'approve';
			}
			
			else
			{
				// Can the application be auto approved?
				$action = ! empty($client->auto_approve) ? 'newrequest' : 'approve';
			}
		}
		
		switch ($action)
		{
			case 'approve':
			
				$requested_scopes = $params['scope'];
				$scopes = $this->oauth->get_scope($requested_scopes);
			
				$vars = array(
					'client_name' => $client->name,
					'scopes' => $scopes
				);
				
				$this->template->body = View::forge('oauth/authorise', $vars);
			
			break;
			
			case 'newrequest':
				
				$code = $this->oauth->new_auth_code($client->client_id, $user->id, $params['redirect_uri'], $params['scope'], $authorised->access_token);
				
				$this->fast_code_redirect($params['redirect_uri'], $params['state'], $code);
			
			break;
			
			case 'finish':

				$code = $this->oauth->new_auth_code($client->client_id, $user->id, $params['redirect_uri'], $params['scope'], $authorised->access_token);
				
				$this->fast_token_redirect($params['redirect_uri'], $params['state'], $code);
				
			break;
		}
	}
	
	/**
	 * Generate a new access token
	 */
	public function action_access_token()
	{
		// Get post query string parameters
		$params = array();
				
		// Client id
		if ($client_id = Input::post('client_id'))
		{
			$params['client_id'] = trim($client_id);
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See client_id.', NULL, array(), 400, 'json');
			return;
		}
		
		// Client secret
		if ($client_secret = Input::post('client_secret'))
		{
			$params['client_secret'] = trim($client_secret);
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See client_secret.', NULL, array(), 400, 'json');
			return;
		}
				
		// Client redirect uri
		if ($redirect_uri = Input::post('redirect_uri'))
		{
			$params['redirect_uri'] = urldecode(trim($redirect_uri));
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See redirect_uri.', NULL, array(), 400, 'json');
			return;
		}
		
		if ($code = Input::post('code'))
		{
			$params['code'] = trim($code);
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See code.', NULL, array(), 400, 'json');
			return;
		}
		
		// Validate the grant type
		if ($grant_type = Input::post('grant_type'))
		{
			$grant_type = trim($grant_type);
			
			if ( ! in_array($grant_type, array('authorization_code')))
			{
				$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See grant_type.', NULL, array(), 400, 'json');
				return;
			}
			
			else
			{
				$params['grant_type'] = $grant_type;
			}
		}
		
		else
		{
			$this->_fail('invalid_request', 'The request is missing a required parameter, includes an invalid parameter value, or is otherwise malformed. See grant_type.', NULL, array(), 400, 'json');
			return;
		}
				
		// Validate client_id and redirect_uri
		$client_details = $this->oauth->validate_client($params['client_id'], $params['client_secret'], $params['redirect_uri']); // returns object or FALSE

		if ($client_details === FALSE )
		{
			$this->_fail('unauthorized_client', 'The client is not authorized to request an authorization code using this method', NULL, array(), 403, 'json');
			return;
		}
		
		// Respond to the grant type
		switch ($params['grant_type'])
		{
			case "authorization_code":
			
				// Validate the auth code
				$session = $this->oauth->validate_auth_code($params['code'], $params['client_id'], $params['redirect_uri']);

				if ($session === FALSE)
				{
					$this->_fail('invalid_request', 'The authorization code is invalid.', NULL, array(), 403, 'json');
					return;
				}
				
				// Generate a new access_token (and remove the authorise code from the session)
				$access_token = $this->oauth->get_access_token($session);
		
				// Send the response back to the application
				$this->_response(array('access_token' => $access_token));
				return;
			
			break;
		}
	}	
	
	/**
	 * Generates a new auth code and redirects the user
	 * Used in the web-server flow
	 * 
	 * @access private
	 * @param string $redirect_uri
	 * @param string $state
	 * @param string $code
	 * @return void
	 */
	private function fast_code_redirect($redirect_uri = '', $state = '', $code = '')
	{
		$redirect_uri = $this->oauth->redirect_uri($redirect_uri, array('code' => $code, 'state' => $state));
		Session::delete(array('oauth.params', 'oauth.client', 'sign_in_redirect'));
		Response::redirect($redirect_uri);	
	}
	
	/**
	 * Generates a new auth access token and redirects the user
	 * Used in the user-agent flow
	 * 
	 * @access private
	 * @param string $redirect_uri
	 * @param string $state
	 * @param string $code
	 * @return void
	 */
	private function fast_token_redirect($redirect_uri = '', $state = '', $code = '')
	{
		$redirect_uri = $this->oauth->redirect_uri($redirect_uri, array('code' => $code, 'state' => $state), '#');
		Session::delete(array('oauth.params','oauth.client', 'sign_in_redirect'));
		Response::redirect($redirect_uri);	
	}
	
	
	/**
	 * Show an error message
	 * 
	 * @access private
	 * @param mixed $msg
	 * @return string
	 */
	
	private function _fail($error, $description, $url = NULL, $params = array(), $status = 400, $output = 'html')
	{
		if ($url)
		{
			$this->oauth->redirect_uri($url, array_merge($params, array(
				'error=' . $error,
				'error_description=' . urlencode($description)
			)));
		}
		
		else
		{
			switch ($output)
			{
				case 'html':
				default:
					throw new Exception('[OAuth error: ' . $error . '] ' . $description, $status);
				break;
				case 'json':
					
					// Override template
					$this->template = $this->response;

					// This is JSON
					$this->response->set_header('Content-Type', 'application/json');

					// Send the correct error code
					$this->response->status = $status;

					// Send back the error
					$this->response->body(json_encode(array(
						'error'			=>	true,
						'error_message'	=>	'[OAuth error: ' . $error . '] ' . $description,
						'access_token'	=>	null
					)));

				break;
			}
			
		}
	}
	
	
	/**
	 * JSON response
	 * 
	 * @access private
	 * @param mixed $msg
	 * @return string
	 */
	private function _response($msg)
	{
		$msg['error'] = false;
		$msg['error_message'] = '';

		// Override template
		$this->template = $this->response;

		$this->response->set_header('Content-Type', 'application/json');

		// Send the correct error code
		$this->response->status = 200;

		// Send back the error
		$this->response->body(json_encode($msg));

	}

}