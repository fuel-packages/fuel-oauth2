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

class Token_Refresh extends Token
{
	/**
	 * @var  string  code
	 */
	protected $code;

	/**
	 * Sets the token, expiry, etc values.
	 *
	 * @param   array   token options
	 * @return  void
	 */
	public function __construct(array $options)
	{
		if ( ! isset($options['code']))
		{
			throw new Exception(array('message' => 'Required option not passed: code'));
		}
		
		$this->code = $options['code'];
	}

	/**
	 * Returns the token key.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->code;
	}

} // End Token_Access
