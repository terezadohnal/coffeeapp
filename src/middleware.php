<?php
// Application middleware

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->add(function(Request $request, Response $response, $next) {
    $basePath = $request->getUri()->getBasePath();
    $this->view->addParam('basePath', $basePath);
    // $this->view->addParam('loggedUser', $_SESSION['logged_user']);
    return $next($request, $response);
});