# Fuel OAuth2

Authorize users with your application in a driver-base fashion meaning one implementation works for multiple OAuth 2 providers.

Note that this Cell ONLY provides the authorization mechanism. You will need to implement the example controller so you can save this information to make API requests on the users behalf.

## Currently Supported

- Facebook
- GitHub
- Google
- Unmagnify
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