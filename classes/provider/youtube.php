<?php

namespace OAuth2;

class Provider_Youtube extends Provider_Google {  
	
	public $name = 'youtube';

	public function __construct(array $options = array())
	{
		// Now make sure we have the default scope to get user data
		$options['scope'] = \Arr::merge(
			
			// We need this default feed to get the authenticated users basic information
			array('https://www.google.com/m8/feeds', 'http://gdata.youtube.com'),
			
			// And take either a string and array it, or empty array to merge into
			(array) \Arr::get($options, 'scope', array())
		);
		
		parent::__construct($options);
	}
}
