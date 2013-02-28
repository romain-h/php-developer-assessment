<?php
// (*****  Bootstrap *****
require_once __DIR__.'/../vendor/autoload.php';
require(__DIR__."/twitteroauth/twitteroauth.php");

use Silex\Application;
use Igorw\Silex\ConfigServiceProvider;

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
$app->get('/', function(Application $app) {
	return $app['twig']->render('index.html.twig');
})->bind('homepage');

$app->get('/signin', function(Application $app) {
	// Create TwitterOAuth instance  
	$twitteroauth = new TwitterOAuth($app['api_twitter']['consumer_key'], $app['api_twitter']['consumer_secret']);
	// Get authentication tokens and redirect to profile 
	$auth_tokens = $twitteroauth->getRequestToken($app['url_generator']->generate('profile', array(), 1));  
	// Save into the session  
	$app['session']->set('oauth_token', $auth_tokens['oauth_token']);  
	$app['session']->set('oauth_token_secret', $auth_tokens['oauth_token_secret']);

	// Twitter accept oauth:  
	if($twitteroauth->http_code==200){  
	    // Redirect to profile:
	    $url = $twitteroauth->getAuthorizeURL($auth_tokens['oauth_token']); 
	    return $app->redirect($url);
	} else { 
		return $app->redirect('/index');
	}

})->bind('signin');

$app->get('/profile', function(Application $app) {
	return "profile";
})->bind('profile');
return $app;