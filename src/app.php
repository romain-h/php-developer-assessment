<?php
// Bootstrap
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;

$app = new Silex\Application();

$app->get('/', function(Application $app) {
	return "Init ok";
});

return $app;