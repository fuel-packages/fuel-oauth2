<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @OAuthor     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

Autoloader::add_classes(array(
	'OAuth2\\OAuth2'          		=> __DIR__.'/classes/oauth2.php',

	'OAuth2\\Exception'  			=> __DIR__.'/classes/exception.php',

	'OAuth2\\Provider'  			=> __DIR__.'/classes/provider.php',
	'OAuth2\\Provider_Facebook'  	=> __DIR__.'/classes/provider/facebook.php',
	'OAuth2\\Provider_Foursquare'  	=> __DIR__.'/classes/provider/foursquare.php',
	'OAuth2\\Provider_Google'  		=> __DIR__.'/classes/provider/google.php',
	'OAuth2\\Provider_Github'  		=> __DIR__.'/classes/provider/github.php',
	'OAuth2\\Provider_Unmagnify'  	=> __DIR__.'/classes/provider/unmagnify.php',
	'OAuth2\\Provider_Windowslive'  => __DIR__.'/classes/provider/windowslive.php',
	
	'OAuth2\\Request'  				=> __DIR__.'/classes/request.php',
	
	'OAuth2\\Token'  				=> __DIR__.'/classes/token.php',
	'OAuth2\\Token_Access'  		=> __DIR__.'/classes/token/access.php',
	'OAuth2\\Token_Authorize'		=> __DIR__.'/classes/token/authorize.php',
	
));


/* End of file bootstrap.php */