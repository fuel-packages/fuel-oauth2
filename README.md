# Fuel OAuth2

Authorize users with your application in a driver-base fashion meaning one implementation works for multiple OAuth 2 providers.

Note that this Cell ONLY provides the authorization mechanism. You will need to implement the example controller so you can save this information to make API requests on the users behalf.

## TODO

- Use a more Exception-happy Request logic, potentially built on top of [Buzz](https://github.com/kriswallsmith/Buzz) or [Requests](https://github.com/rmccue/Requests)
- Rebuild as a generic Composer package
- Add unit tests and get on Travis

## Examples

OAuth 2 is split into two sections, clients and providers. A client is an application - perhaps a basic Twitter feed aggregator - which 
authenticates with an OAuth 2 provider, which in this example would be Twitter itself. You can interact with any provider which is supported in 
the list below:

- Facebook
- Foursquare
- GitHub
- Google
- PayPal
- Instagram
- Soundcloud
- Windows Live
- YouTube

### Consumer Usage

This example will need the user to go to a certain URL, which will support multiple providers. I like to set a controller to handle it and either have one single "session" method - or have another method for callbacks if you want to separate out the code even more.

Here you'll see we have the provider passed in as a URI segment of "facebook" which can be used to find config in a database, or in a config multi-dimensional array. If you want to hard code it all then that is just fine too.

Send your user to `http://example.com/auth/session/facebook` where Auth is the name of the controller. This will also be the address of the "Callback URL" which will be required by many OAuth 2 providers such as Facebook.

```php
class Auth extends Controller
{
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
  			
  			// Optional: Use the token object to grab user data from the API
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
}
```



###  Provider (Server) Usage

If you would like to  make your application have an OAuth 2 Provider itself, then you can use the OAuth2\Server class, which is essentially
a collection of utilities to help you achieve this. You will need to make your own controller and views, which can be found in the examples folder.

The default controller example checks users with [Sentry](http://sentry.cartalyst.com/) but as of Auth 1.2.0 you will be able to use that too.

You can use either SQL (via the DB layer in FuelPHP) or MongoDB (using FuelPHP's Mongo_Db layer) as both have a server model provided. You can create your own and to work with whatever the hell system you like, so check the example controller to see how thats done.


Contribute
----------

1. Check for open issues or open a new issue for a feature request or a bug
2. Fork [the repository][] on Github to start making your changes to the
    `develop` branch (or branch off of it)
3. Write a test which shows that the bug was fixed or that the feature works as expected
4. Send a pull request and bug me until I merge it

[the repository]: https://github.com/fuel-packages/fuel-oauth2