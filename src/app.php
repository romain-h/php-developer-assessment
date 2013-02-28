<?php
// (*****  Bootstrap *****
require_once __DIR__.'/../vendor/autoload.php';
require(__DIR__."/twitteroauth/twitteroauth.php");

use Silex\Application;
use Igorw\Silex\ConfigServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PeerindexChallenge\User;
use Silex\Provider\DoctrineServiceProvider;

$app = new Silex\Application();

// Add config service
$env = getenv('APP_ENV') ?: 'prod';	
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));

// Add session Service
$app->register(new Silex\Provider\SessionServiceProvider());

// Add Doctrine Service
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(        
            'driver'    => $app['config_db']['driver'],
            'host'      => $app['config_db']['host'],
            'dbname'    => $app['config_db']['dbname'],
            'user'      => $app['config_db']['user'],
            'password'  => $app['config_db']['password'],
            'charset'   => $app['config_db']['charset'],
        ),
    ));
// Add Twig service
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
    'twig.class_path' => __DIR__.'/../vendor/Twig/lib',
));

// Add url generator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


// ******* Application *****
// Error handler
$app->error(function (\Exception $e, $code, $message = "") use ($app) { 
	$error_message = "Oups... Something goes wrong... :(";
	switch ($code) {
	 	case 404:
	 		$error_message = "Sorry, the requested page could not be found.";

	 } 
	 return $app['twig']->render('error.html.twig', array('error_message' => $error_message));
});

$app->get('/', function(Application $app) {
	return $app['twig']->render('index.html.twig');
})->bind('homepage');

$app->get('/signin', function(Application $app) {
	// Create TwitterOAuth instance  
	$twitteroauth = new TwitterOAuth($app['api_twitter']['consumer_key'], $app['api_twitter']['consumer_secret']);
	// Get authentication tokens and redirect to login 
	$auth_tokens = $twitteroauth->getRequestToken($app['url_generator']->generate('login', array(), 1));  
	// Save into the session  
	$app['session']->set('oauth_token', $auth_tokens['oauth_token']);  
	$app['session']->set('oauth_token_secret', $auth_tokens['oauth_token_secret']);

	// Twitter accept oauth:  
	if($twitteroauth->http_code==200){  
	    // Redirect to profile:
	    $url = $twitteroauth->getAuthorizeURL($auth_tokens['oauth_token']); 
	    return $app->redirect($url);
	} else { 
		// Handle with error:.  
		$app->abort(500);
	}

})->bind('signin');

$app->get('/login', function(Application $app, Request $request) {
	$params = $request->query->all();
	$oauth_token = $app['session']->get('oauth_token');
	$oauth_token_secret = $app['session']->get('oauth_token_secret');
	// Check if user is oauth:
	if(!empty($params['oauth_verifier']) && !empty($oauth_token) && !empty($oauth_token_secret)){  
		// TwitterOAuth instance, with two new parameters  
		$twitteroauth = new TwitterOAuth($app['api_twitter']['consumer_key'], $app['api_twitter']['consumer_secret'], $app['session']->get('oauth_token'), $app['session']->get('oauth_token_secret'));  
		// Let's request the access token  
		$access_token = $twitteroauth->getAccessToken($params['oauth_verifier']); 
		 
		$app['session']->set('access_token', $access_token); 
		// user's info 
		$user_info = $twitteroauth->get('account/verify_credentials'); 

		$user = new User($app['db'], $user_info->id);

		// User is not stored in our db:
		if(!$user->exist()){
			$user->add($user_info, $access_token);
		} else {
			// Update token if already stored:
			$user->updateToken($access_token);
		}

		// Store info into session;
		$app['session']->set('uid', $user->getUid());
		$app['session']->set('oauth_token', $user->getToken());
		$app['session']->set('oauth_token_secret', $user->getSecret());

		return $app->redirect('/profile');

	} else {
		// Something's missing, go back sigin  
    	return $app->redirect('/signin'); 
	}
	

	
})->bind('login');

$app->get('/profile', function(Application $app) {
	$uid = $app['session']->get('uid');
	// User not loged in..
	if(empty($uid)){
		return $app->redirect('/');
	} else{
		// Check right user into session:
		$user = new User($app['db'], $uid);
		$twitteroauth = new TwitterOAuth($app['api_twitter']['consumer_key'], $app['api_twitter']['consumer_secret'], $app['session']->get('oauth_token'), $app['session']->get('oauth_token_secret'));  
		if($user->exist())
			$user_info = $twitteroauth->get('account/verify_credentials');
		//If ok display profile:
		if($user_info->name){
			return $app['twig']->render('profile.html.twig',array('username' => $user_info->name, 'bio' => $user_info->description, 'img_url' =>$user_info->profile_image_url));
		} else {
			$app->abort(500);
		}
		
	}
	
})->bind('profile');

 

return $app;