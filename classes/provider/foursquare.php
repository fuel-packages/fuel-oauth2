<?php

namespace OAuth2;

class Provider_Foursquare extends Provider {  
	
	public $name = 'foursquare';
	
	public $method = 'POST';

	public function url_authorize()
	{
		return 'https://foursquare.com/oauth2/authenticate';
	}

	public function url_access_token()
	{
		return 'https://foursquare.com/oauth2/access_token';
	}

	public function get_user_info(Token $token)
	{
		$url = 'https://api.foursquare.com/v2/users/self?'.http_build_query(array(
			'oauth_token' => $token->access_token,
		));

		$response = json_decode(file_get_contents($url));

		$user = $response->response->user;

		// Create a response from the request
		return array(
			'uid' => $user->id,
			//'nickname' => $user->login,
			'name' => sprintf('%s %s', $user->firstName, $user->lastName),
			'email' => $user->contact->email,
			'image' => $user->photo,
			'location' => $user->homeCity,
		);
	}
}