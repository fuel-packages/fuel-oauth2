<?php

namespace OAuth2;

class Provider_Github extends Provider {  
	
	public $name = 'github';
	
	// https://api.github.com

	public function url_authorize()
	{
		return 'https://github.com/login/oauth/authorize';
	}

	public function url_access_token()
	{
		return 'https://github.com/login/oauth/access_token';
	}

	public function get_user_info($token)
	{
		$url = 'https://api.github.com/user?'.http_build_query(array(
			'access_token' => $token,
		));

		$user = json_decode(file_get_contents($url));

		// Create a response from the request
		return array(
			'nickname' => $user->login,
			'name' => $user->name,
			'email' => $user->email,
			'urls' => array(
			  'GitHub' => 'http://github.com/'.$user->login,
			  'Blog' => $user->blog,
			),
			'credentials' => array(
				'uid' => $user->id,
				'provider' => $this->name,
				'token' => $token,
			),
		);
	}
}