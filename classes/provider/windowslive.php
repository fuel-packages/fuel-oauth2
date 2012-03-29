<?php

namespace OAuth2;

class Provider_Windowslive extends Provider
{
	public function __construct(array $options = array())
	{
		// Now make sure we have the default scope to get user data
		$options['scope'] = \Arr::merge(
			
			// We need this default feed to get the authenticated users basic information
			array('wl.basic', 'wl.emails'),
			
			// And take either a string and array it, or empty array to merge into
			(array) \Arr::get($options, 'scope', array())
		);
		
		parent::__construct($options);
	}
	
	// authorise url
	public function url_authorize()
	{
		// return the authorise URL
		return 'https://oauth.live.com/authorize';
	}
	
	// access token url
	public function url_access_token()
	{
		// return the access token URL
		return 'https://oauth.live.com/token';
	}
	
	// get basic user information
	/********************************
	** this can be extended through the 
	** use of scopes, check out the document at
	** http://msdn.microsoft.com/en-gb/library/hh243648.aspx#user
	*********************************/
	public function get_user_info(Token_Access $token)
	{
		// define the get user information token
		$url = 'https://apis.live.net/v5.0/me?'.http_build_query(array(
			'access_token' => $token->access_token,
		));
		
		// perform network request
		$user = json_decode(file_get_contents($url));

		// create a response from the request and return it
		return array(
			'uid' 			=> $user->id,
			'name' 			=> $user->name,
			'emial'			=> isset($user->emails->preferred) ? $user->emails->preferred : null,
			'nickname' 		=> \Inflector::friendly_title($user->name, '-', true),
			// 'location' 	=> $user->location,
			// 	requires scope wl.postal_addresses and docs here: http://msdn.microsoft.com/en-us/library/hh243648.aspx#user
			'locale' 		=> $user->locale,
			'urls' 			=> array(
				'Windows Live' => $user->link
			),
		);
	}
}