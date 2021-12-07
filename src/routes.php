<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');

$app->get('/coffeeshops', function (Request $request, Response $response, $args) {
    // Render coffeeshops view
    return $this->view->render($response, 'coffeeshops.latte');
})->setName('coffeeshops');

$app->get('/add-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    return $this->view->render($response, 'add-coffeeshop.latte');
})->setName('add-coffeeshop');

$app->get('/sign-up', function (Request $request, Response $response, $args) {
    // Render sign-up view
    return $this->view->render($response, 'sign-up.latte');
})->setName('sign-up');

$app->get('/login', function (Request $request, Response $response, $args) {
    // Render login view
    return $this->view->render($response, 'login.latte');
})->setName('login');

