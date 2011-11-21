<?php

namespace OAuth2;

class Provider_Unmagnify extends Provider {  
	
	public $name = 'unmagnify';

	protected $method = 'POST';

	public function url_authorize()
	{
		return \Fuel::$env == \Fuel::DEVELOPMENT ? 'http://localhost:3000/oauth2/authorize' : 'https://www.unmagnify.com/oauth2/authorize';
	}

	public function url_access_token()
	{
		return \Fuel::$env == \Fuel::DEVELOPMENT ? 'http://localhost:3000/oauth2/token' : 'https://www.unmagnify.com/oauth2/token';
	}

	public function get_user_info(Token $token)
	{
		$url = 'http://localhost:3000/api/v1/me?'.http_build_query(array(
		 	'access_token' => $token->access_token,
		));
		
		$user = json_decode(file_get_contents($url), true);

		// Create a response from the request
		return array(
			'uid' => $user['username'],
			'nickname' => $user['username'],
			'name' => $user['name'],
			'description' => $user['bio'],
			'location' => $user['location'],
			'email' => $user['email'],
			'urls' => array(
				'Unmagnify' => 'http://unmagnify.com/'.$user['username'],
				'Website' => $user['website'],
			),
		);
	}
}