<?php

namespace OAuth2;

class Provider_Google extends Provider {  
	
	public $name = 'google';

	public $uid_key = 'uid';
	
	public $method = 'POST';

	public $scope_seperator = ' ';
	
	public $scope = 'https://www.googleapis.com/auth/plus.me';

	public function url_authorize()
	{
		return 'https://accounts.google.com/o/oauth2/auth';
	}

	public function url_access_token()
	{
		return 'https://accounts.google.com/o/oauth2/token';
	}

	public function authorize($options = array())
	{
		if (null === $this->scope or empty($this->scope))
		{
			throw new Exception('Required option not provided: scope');
		}
		
		parent::authorize($options);
	}

	public function get_user_info($token)
	{
		$url = 'https://www.googleapis.com/plus/v1/people/me?'.http_build_query(array(
			'access_token' => $token,
		));
		
		$user = json_decode(file_get_contents($url));
		
		// Normalise email
		$primary_email = null;
		
		if (isset($user->emails))
		{
			// Sometimes the G+ api gives us the emails as an array
			foreach ($user->emails as $email)
			{
				if ($email->primary)
				{
					$primary_email = $email->value;
				}
			}
		}
		else
		{
			//if we dont get the email from G+, get it from the google API.
			$email_url = 'https://www.googleapis.com/userinfo/email?alt=json&'.http_build_query(array(
				'access_token' => $token,
			));
			$email_response = json_decode(file_get_contents($email_url));
			
			$primary_email = $email_response->data->email;
		}
		
		// Normalise urls
		$urls = null;
		foreach ($user->urls as $url)
		{
			if (isset($url->type))
			{
				$urls[$url->type] = $url->value;
			}
			else
			{
				$urls[] = $url->value;
			}
		}
		
		// Create a response from the request
		return array(
			'nickname' => $user->displayName,
			'name' => $user->displayName,
			'email' => $primary_email,
			'location' => null,
			'description' => $user->aboutMe,
			'image' => $user->image->url,
			'urls' => $urls,
			'credentials' => array(
				'uid' => $user->id,
				'provider' => $this->name,
				'token' => $token,
			),
		);
	}
}
