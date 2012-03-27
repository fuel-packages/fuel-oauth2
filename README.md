# Fuel OAuth2

Authorize users with your application in a driver-base fashion meaning one implementation works for multiple OAuth 2 providers.

Note that this Cell ONLY provides the authorization mechanism. You will need to implement the example controller so you can save this information to make API requests on the users behalf.

## Currently Supported

- Facebook
- Foursquare
- GitHub
- Google
- Instagram
- Unmagnify
- Windows Live
- YouTube

## TODO

Requests need to be WAY more bullet-proof, but there is no generic Request class in Fuel, which is something else that needs to be worked on.

## Usage Example

http://example.com/auth/session/facebook

```php
public function action_session($provider)
{	
	$provider = OAuth2\Provider::factory($provider, array(
		'client_id' => 'your-client-id',
		'client_secret' => 'your-client-secret',
	));

	if ( ! isset($_GET['code']))
	{
		// By sending no options it'll come back here
		$provider->authorize();
	}
	
	else
	{
		// Howzit?
		try
		{
			$token = $provider->access($_GET['code']);
			
			// Save access_token for now
			Session::set('access_token', $token->access_token);
			
			// Use that to grab basic data about the user
			$user = $provider->get_user_info($token);
			
			// Here you should use this information to A) look for a user B) help a new user sign up with existing data.
			// If you store it all in a cookie and redirect to a registration page this is crazy-simple.
			echo "<pre>";
			var_dump($user);
		}
		
		catch (OAuth2\Exception $e)
		{
			show_error('That didnt work: '.$e);
		}
		
	}
}
```



## Server Usage

```sql
DROP TABLE IF EXISTS oauth2_session_scopes, oauth2_sessions, applications, scopes;

CREATE TABLE `applications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `client_id` varchar(32) NOT NULL DEFAULT '',
  `client_secret` varchar(32) NOT NULL DEFAULT '',
  `redirect_uri` varchar(250) NOT NULL DEFAULT '',
  `auto_approve` tinyint(1) NOT NULL DEFAULT '0',
  `autonomous` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('development','pending','approved','rejected') NOT NULL DEFAULT 'development',
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `notes` tinytext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `scopes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `scope` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `oauth2_sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` varchar(32) NOT NULL DEFAULT '',
  `redirect_uri` varchar(250) NOT NULL DEFAULT '',
  `type_id` varchar(64) DEFAULT NULL,
  `type` enum('user','auto') NOT NULL DEFAULT 'user',
  `code` text,
  `access_token` varchar(50) DEFAULT '',
  `stage` enum('request','granted') NOT NULL DEFAULT 'request',
  `first_requested` int(10) unsigned NOT NULL,
  `last_updated` int(10) unsigned NOT NULL,
  `limited_access` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Used for user agent flows',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `oauth_sessions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `applications` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `oauth2_session_scopes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(11) unsigned NOT NULL,
  `access_token` varchar(50) NOT NULL DEFAULT '',
  `scope` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `scope` (`scope`),
  KEY `access_token` (`access_token`),
  CONSTRAINT `oauth_session_scopes_ibfk_1` FOREIGN KEY (`scope`) REFERENCES `scopes` (`scope`),
  CONSTRAINT `oauth_session_scopes_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `oauth2_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;