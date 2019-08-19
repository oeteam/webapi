<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
	$container = $app->getContainer();
	$app->get('/', function (Request $req,  Response $res, $args = []) {
	    return $res->withStatus(400)->write('Bad Request');
	});
	$app->get('/books/{name}', function ($request, $response, $args) {
	    // Show book identified by $args['id']
	    $name = $args['name'];
	    $response->getBody()->write("Hello! ,$name");
	    return $response;
	});


	$app->any('/login', 'App\Controllers\LoginController:index');
	$app->any('/search', 'App\Controllers\SearchController:index');
	$app->any('/search/home', 'App\Controllers\SearchController:home');
};

