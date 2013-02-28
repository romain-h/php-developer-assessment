<?php
// (*****  Bootstrap *****
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Igorw\Silex\ConfigServiceProvider;

$app = new Silex\Application();

// Add config service
$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));


// ******* Application *****
$app->get('/', function(Application $app) {
	return "Init ok";
});

return $app;