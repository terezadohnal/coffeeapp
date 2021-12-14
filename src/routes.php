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

$app->post('/sign-up', function (Request $request, Response $response, $args){
    $formData = $request->getParsedBody();

    // muzeme to zadat do databaze

    //do bindParams muzu hodit jen hotove hotdnoty, do bindValues i vyrazy a terenarni operatory
    $stmt = $this->db->prepare('INSERT INTO users (nickname, first_name, last_name, birthday, sex, profession, password) VALUES (:nn, :fn, :ln, :bd, :sx, :pf, :pw)');

    $stmt->bindValue(':nn', $formData['nickname']);
    $stmt->bindValue(':fn', $formData['first_name']);
    $stmt->bindValue(':ln', $formData['last_name']);
    $stmt->bindValue(':bd', empty($formData['birthday']) ? null : $formData['birthday']);
    $stmt->bindValue(':sx', empty($formData['sex']) ? null : $formData['sex']);
    $stmt->bindValue(':pf', $formData['profession']);
    $stmt->bindValue(':pw', $formData['password']);
    $stmt->execute();
    $data['message'] = 'Person successfully inserted';
    
    $data['formData'] = $formData;
    return $this->view->render($response, 'index.latte', $data);
});

$app->get('/login', function (Request $request, Response $response, $args) {
    // Render login view
    return $this->view->render($response, 'login.latte');
})->setName('login');

