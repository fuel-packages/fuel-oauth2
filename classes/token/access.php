<?php
/**
 * OAuth2 Token
 *
 * @package    OAuth2
 * @category   Token
 * @author     Phil Sturgeon
 * @copyright  (c) 2011 HappyNinjas Ltd
 */

namespace OAuth2;

class Token_Access extends Token
{
	/**
	 * @var  string  access_token
	 */
	protected $access_token;

	/**
	 * @var  int  expires
	 */
	protected $expires;

	/**
	 * @var  string  refresh_token
	 */
	protected $refresh_token;

	/**
	 * @var  string  uid
	 */
	protected $uid;

	/**
	 * @var  mixed  user
	 */
	protected $user;

	/**
	 * Sets the token, expiry, etc values.
	 *
	 * @param   array   token options
	 * @return  void
	 */
	public function __construct(array $options)
	{
		if ( ! isset($options['access_token']))
		{
			throw new Exception(array('message' => 'Required option not passed: access_token'.PHP_EOL.print_r($options, true)));
		}
		
		// if ( ! isset($options['expires_in']) and ! isset($options['expires']))
		// {
		// 	throw new Exception('We do not know when this access_token will expire');
		// }

		$this->access_token = $options['access_token'];
		
		// Some providers (not many) give the uid here, so lets take it
		isset($options['uid']) and $this->uid = $options['uid'];

		// Some providers (not many) give the user here, so lets take it
		isset($options['user']) and $this->user = $options['user'];
		
		// We need to know when the token expires, add num. seconds to current time
		isset($options['expires_in']) and $this->expires = time() + ((int) $options['expires_in']);
		
		// Facebook is just being a spec ignoring jerk
		isset($options['expires']) and $this->expires = time() + ((int) $options['expires']);
		
		// Grab a refresh token so we can update access tokens when they expires
		isset($options['refresh_token']) and $this->refresh_token = $options['refresh_token'];
	}

	/**
	 * Returns the token key.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->access_token;
	}

} // End Token_Access