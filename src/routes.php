<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');

$app->get('/coffeeshops', function (Request $request, Response $response, $args) {
    // Render coffeeshops view
    $params = $request->getQueryParams(); //[query => 'johnny']

    if(! empty ($params['query'])){ //kontrolujem, zda uzivatel neco zadal
        $stmt = $this->db->prepare('SELECT * FROM coffeeshops WHERE lower(name) = lower(:n) OR lower(town) = :twn');
        $stmt->bindParam(':n', $params['query']);
        $stmt->bindParam(':twn', $params['query']);
        $stmt->execute();
        $data['coffeeshops'] = $stmt->fetchall();
        //utok na databazi - SQL injection, diky bindParams to zabezpecime, nesmime do vrchni listy propsat opravdove data
    }else {
        $stmt = $this->db->prepare('SELECT * FROM coffeeshops ORDER BY name');
        $stmt->execute(); // zde mame ulozene data z databaze
        $data['coffeeshops'] = $stmt->fetchall(); //ulozim do promenne vystup, databazovy objekt
    }
    //echo var_dump($data); //kontrola, zda to funguje

    return $this->view->render($response, 'coffeeshops.latte', $data);
})->setName('coffeeshops');

$app->get('/coffeeshops/profile/', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    return $this->view->render($response, 'profile.latte');
})->setName('profile');


$app->get('/add-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    return $this->view->render($response, 'add-coffeeshop.latte');
})->setName('add-coffeeshop');

$app->post('/add-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $formData = $request->getParsedBody();
    // $stmt = $this->db->prepare('INSERT ALL INTO coffeeshops (name, address, city) VALUES (:n, :adr, :ct) INTO openning_hours (day, from, to) VALUES (:d, :f, :t)');

    // $stmt->bindValue(':n', $formData['name']);
    // $stmt->bindValue(':adr', $formData['address']);
    // $stmt->bindValue(':ct', $formData['city']);
    // $stmt->bindValue(':d', $formData['day']);
    // $stmt->bindValue(':f', $formData['from']);
    // $stmt->bindValue(':t', $formData['to']);
    $stmt->execute();
    $data['formData'] = $formData;
    return $this->view->render($response, 'add-coffeeshop.latte');
})->setName('add-coffeeshop');

$app->get('/sign-up', function (Request $request, Response $response, $args) {
    // Render sign-up view
    return $this->view->render($response, 'sign-up.latte');
})->setName('sign-up');

$app->post('/sign-up', function (Request $request, Response $response, $args){
    $formData = $request->getParsedBody();
    $passwd_hash = hash('sha256', $formData['password']);

    // muzeme to zadat do databaze

    //do bindParams muzu hodit jen hotove hotdnoty, do bindValues i vyrazy a terenarni operatory
    $stmt = $this->db->prepare('INSERT INTO users (nickname, first_name, last_name, birthday, sex, profession, password) VALUES (:nn, :fn, :ln, :bd, :sx, :pf, :pw)');

    $stmt->bindValue(':nn', $formData['nickname']);
    $stmt->bindValue(':fn', $formData['first_name']);
    $stmt->bindValue(':ln', $formData['last_name']);
    $stmt->bindValue(':bd', empty($formData['birthday']) ? null : $formData['birthday']);
    $stmt->bindValue(':sx', empty($formData['sex']) ? null : $formData['sex']);
    $stmt->bindValue(':pf', $formData['profession']);
    $stmt->bindValue(':pw', $passwd_hash);
    $stmt->execute();
    $data['message'] = 'Person successfully inserted';
    
    $data['formData'] = $formData;
    return $this->view->render($response, 'index.latte', $data);
});

$app->get('/login', function (Request $request, Response $response, $args) {
    // Render login view
    return $this->view->render($response, 'login.latte');
})->setName('login');

$app->post('/login', function (Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    $passwd_hash = hash('sha256', $formData['password']);

    $stmt = $this->db->prepare('SELECT id_person, first_name, nickname, last_name FROM users WHERE lower(nickname) = lower(:nn) AND password = :pw'); // zjisteni, zda existuje uzivatel s takovym jmemen a heslem
    // :nn a :pw jsou vstupy od uzivatele
    $stmt->bindValue(':nn', $formData['nickname']);
    $stmt->bindValue(':pw', $passwd_hash);
    $stmt->execute();

    $logged_user = $stmt->fetch(); // vrati true / false

    if($logged_user){
        $_SESSION['logged_user'] = $logged_user;
        setcookie('first_name', $logged_user['first_name']);
        // je do nej ukladam vystup z databazoveho dotazu
        // dulezity radek pro prihlaseni a autentizaci uivatele, tato promenna zije po dobu celeho 'sezeni'
        return $response->withHeader('Location', $this->router->pathFor('coffeeshops')); //nastavujem hlavicku a url na seznam mych uzivatelu
    }else {
        return $this->view->render($response, 'login.latte', ['message' => 'Wrong username or password']);
        // v opacnem pripade ho posleme na prihlasovaci formular a posleme mu zpravu o chybnych udajich
    }
});

    //Logout user
    $app->get('/logout', function (Request $request, Response $response, $args) {
        session_destroy(); //tohle staci
        return $response->withHeader('Location', $this->router->pathFor('index'));
        //presmerovani
    })->setName('logout');
