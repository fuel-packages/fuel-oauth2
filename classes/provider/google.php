<?php

namespace OAuth2;

class Provider_Google extends Provider {  
	
	public $name = 'google';

	public $uid_key = 'uid';
	
	public $method = 'POST';

	public $scope_seperator = ' ';

	public function url_authorize()
	{
		return 'https://accounts.google.com/o/oauth2/auth';
	}

	public function url_access_token()
	{
		return 'https://accounts.google.com/o/oauth2/token';
	}


	public function __construct(array $options = array())
	{
		// Now make sure we have the default scope to get user data
		$options['scope'] = \Arr::merge(
			
			// We need this default feed to get the authenticated users basic information
			// array('https://www.googleapis.com/auth/plus.me'),
			array('https://www.google.com/m8/feeds'),
			
			// And take either a string and array it, or empty array to merge into
			(array) \Arr::get($options, 'scope', array())
		);
		
		parent::__construct($options);
	}

	/*
	* Get access to the API
	*
	* @param	string	The access code
	* @return	object	Success or failure along with the response details
	*/	
	public function access($code, $options = array())
	{
		if (null === $code)
		{
			throw new Exception('Expected Authorization Code from '.ucfirst($this->name).' is missing');
		}

		return parent::access($code, $options);
	}

	public function get_user_info($token)
	{
		$url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results=1&alt=json&'.http_build_query(array(
			'access_token' => $token,
		));
		
		$response = json_decode(file_get_contents($url), true);
		
		// Fetch data parts
		$email = \Arr::get($response, 'feed.id.$t');
		$name = \Arr::get($response, 'feed.author.0.name.$t');
		$name == '(unknown)' and $name = $email;
		
		return array(
			'nickname' => \Inflector::friendly_title($name),
			'name' => $name,
			'email' => $email,
			'location' => null,
			'image' => null,
			'description' => null,
			'urls' => array(),
			'credentials' => array(
				'uid' => $email,
				'provider' => $this->name,
				'token' => $token,
			),
		);
		
		
		/*
		
		This code was taken from somewhere and in theory perfect as we don't need access to all their contacts
		but in reality the likelyhood that a user has Google Plus is quite low so this just doesn't work.
		If there is a more generic way to get peoples basic data with lower requirements then please send a pull request!
			- Phil
		
		$url = 'https://www.googleapis.com/plus/v1/people/me?'.http_build_query(array(
			'access_token' => $token,
		));
		
		$primary_email = null;
		
		$user = json_decode(@file_get_contents($url));
			
		// See if we got any emails from Google+
		if ( ! empty($user->emails))
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
		*/
	}
}
