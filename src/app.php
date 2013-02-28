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


// ******* Application *****
$app->get('/', function(Application $app) {
	return "Init ok";
});

return $app;