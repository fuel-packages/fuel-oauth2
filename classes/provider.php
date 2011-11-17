<?php
/**
 * OAuth Provider
 *
 * @package    CodeIgniter/OAuth
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  Phil Sturgeon
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace OAuth2;

abstract class Provider {

	/**
	 * Create a new provider.
	 *
	 *     // Load the Twitter provider
	 *     $provider = OAuth_Provider::factory('twitter');
	 *
	 * @param   string   provider name
	 * @param   array    provider options
	 * @return  OAuth_Provider
	 */
	public static function factory($name, array $options = NULL)
	{	
		$class = 'OAuth2\\Provider_'.\Inflector::classify($name);
		return new $class($options);
	}

	/**
	 * @var  string  provider name
	 */
	public $name;

	/**
	 * @var  string  uid key name
	 */
	public $uid_key = 'uid';
	
	/**
	 * @var  string  scope separator, most use "," but some like Google are spaces
	 */
	public $scope_seperator = ',';

	/**
	 * @var  string  additional request parameters to be used for remote requests
	 */
	public $callback = null;

	/**
	 * @var  array  additional request parameters to be used for remote requests
	 */
	protected $params = array();
	
	/**
	 * @var  string  the method to use when requesting tokens
	 */
	protected $method = 'GET';

	/**
	 * Overloads default class properties from the options.
	 *
	 * Any of the provider options can be set here, such as app_id or secret.
	 *
	 * @param   array   provider options
	 * @return  void
	 */
	public function __construct(array $options = NULL)
	{
		if ( ! $this->name)
		{
			// Attempt to guess the name from the class name
			$this->name = strtolower(substr(get_class($this), strlen('OAuth2\\Provider_')));
		}
		
		if ( ! $this->client_id = \Arr::get($options, 'id'))
		{
			throw new Exception('Required option not provided: id');
		}
		
		if (isset($options['callback']))
		{
			$this->callback = $options['callback'];
		}
		
		$this->client_secret = \Arr::get($options, 'secret');
		$this->scope = \Arr::get($options, 'scope');
		
		$this->redirect_uri = \Uri::current();
	}

	/**
	 * Return the value of any protected class variable.
	 *
	 *     // Get the provider signature
	 *     $signature = $provider->signature;
	 *
	 * @param   string  variable name
	 * @return  mixed
	 */
	public function __get($key)
	{
		return $this->$key;
	}

	/**
	 * Returns the authorization URL for the provider.
	 *
	 *     $url = $provider->url_authorize();
	 *
	 * @return  string
	 */
	abstract public function url_authorize();

	/**
	 * Returns the access token endpoint for the provider.
	 *
	 *     $url = $provider->url_access_token();
	 *
	 * @return  string
	 */
	abstract public function url_access_token();

	/*
	* Get an authorization code from Facebook.  Redirects to Facebook, which this redirects back to the app using the redirect address you've set.
	*/	
	public function authorize($options = array())
	{
		$state = md5(uniqid(rand(), TRUE));
		\Session::set('state', $state);
		
		$url = $this->url_authorize().'?'.http_build_query(array(
			'client_id' 		=> $this->client_id,
			'redirect_uri' 		=> \Arr::get($options, 'redirect_uri', $this->redirect_uri),
			'state' 			=> $state,
			'scope'     		=> is_array($this->scope) ? implode($this->scope_seperator, $this->scope) : $this->scope,
			'response_type' 	=> 'code',
		));
		
		\Response::redirect($url);
	}

	/*
	* Get access to the API
	*
	* @param	string	The access code
	* @return	object	Success or failure along with the response details
	*/	
	public function access($code, $options = array())
	{
		$params = array(
			'client_id' 	=> $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' 	=> \Arr::get($options, 'redirect_uri', $this->redirect_uri),
			'code' 			=> $code,
			'grant_type' 	=> 'authorization_code'
		);
	
		$response = null;	
		$url = $this->url_access_token();
		
		switch($this->method)
		{
			case 'GET':
				$url .= '?'.http_build_query($params);
				$response = file_get_contents($url);
				
				parse_str($response, $params); 
				break;
				
			case 'POST':
				//maybe switch to use curl?
				/*
				$curl = \Rest::forge('oauth2', array(
					'server' => $url,
					'method' => 'curl'
				));
				$response = $curl->post('', $params);*/
				
				$postdata = http_build_query($params);
				$opts = array(
					'http' => array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $postdata
					)
				);
				$context  = stream_context_create($opts);
				$response = file_get_contents($url, false, $context);
				
				$params = get_object_vars(json_decode($response));
				break;
			default:
				throw new \OutOfBoundsException("Method '{$this->method}' must be either GET or POST");
		}
		
		if (isset($params['error']))
		{
			throw new Exception($params);
		}
		
		return $params;
	}

}
