<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');


$app->get('/coffeeshops', function (Request $request, Response $response, $args) {
    // Render index view
        $stmt = $this->db->prepare('SELECT * FROM coffeeshops ORDER BY name');
        $stmt->execute(); // zde mame ulozene data z databaze
        $data['coffeeshops'] = $stmt->fetchall();

    return $this->view->render($response, 'coffeeshops.latte', $data);
})->setName('coffeeshops');

$app->get('/coffeeshop/profile', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $id_coffeeshop = $request->getQueryParam('id_coffeeshop');

    $stmt = $this->db->prepare('SELECT * FROM coffeeshops WHERE id_coffeeshop = :idc');
    $stmt->bindParam(':idc', $id_coffeeshop);
    $stmt->execute();
    $data['coffeeshop'] = $stmt->fetch();

    $stmt = $this->db->prepare('SELECT * FROM offer WHERE id_coffeeshop = :idc');
    $stmt->bindParam(':idc', $id_coffeeshop);
    $stmt->execute();
    $data['offer'] = $stmt->fetch();

    $stmt = $this->db->prepare('SELECT * FROM service WHERE id_coffeeshop = :idc');
    $stmt->bindParam(':idc', $id_coffeeshop);
    $stmt->execute();
    $data['service'] = $stmt->fetch();

    $stmt = $this->db->prepare('SELECT * FROM opening_hours WHERE id_coffeeshop = :idc ORDER BY day ASC');
    $stmt->bindParam(':idc', $id_coffeeshop);
    $stmt->execute();
    $data['opening_hours'] = $stmt->fetchall();

    // echo var_dump($data['opening_hours']); 
    // echo var_dump($data['coffeeshop']); 

    return $this->view->render($response, 'profile.latte', $data);
})->setName('profile');

//Edit coffeeshop's info - nacitani
$app->get('/edit-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $params = $request->getQueryParams(); # $params = [id_person => 1232, firstname => aaa]

    if(!empty($params['id_coffeeshop'])){
        $stmt = $this->db->prepare('SELECT * FROM coffeeshops WHERE id_coffeeshop = :idc');
        $stmt->bindValue(':idc', $params['id_coffeeshop']);
        $stmt->execute();
        $data['coffeeshop'] = $stmt->fetch();

        $stmt = $this->db->prepare('SELECT * FROM offer WHERE id_coffeeshop = :idc');
        $stmt->bindValue(':idc', $params['id_coffeeshop']);
        $stmt->execute();
        $data['offer'] = $stmt->fetch();

        $stmt = $this->db->prepare('SELECT * FROM service WHERE id_coffeeshop = :idc');
        $stmt->bindValue(':idc', $params['id_coffeeshop']);
        $stmt->execute();
        $data['service'] = $stmt->fetch();

        $stmt = $this->db->prepare('SELECT * FROM opening_hours WHERE id_coffeeshop = :idc ORDER BY day ASC');
        $stmt->bindValue(':idc', $params['id_coffeeshop']);
        $stmt->execute();
        $data['opening_hours'] = $stmt->fetchall();

        if(empty($data['coffeeshop'])) {
            exit('person not found');
        }else {
            return $this->view->render($response, 'edit-coffeeshop.latte', $data);
        }
    }
})->setName('edit-coffeeshop');

// Odeslani upraveneho formulare
$app->post('/edit-coffeeshop', function (Request $request, Response $response, $args) {
    $id_coffeeshop = $request->getQueryParam('id_coffeeshop');
    $data = $request->getParsedBody();
    $tmpVars = [];
    $id_user = 1;
    $photo = "";
    
    if(empty($data['name']) || empty($data['address']) || empty($data['city'])) {
        $tmpVars['message'] = 'Please fill required fields';
    } else {
        $stmt = $this->db->prepare('UPDATE coffeeshops SET name = :n, address = :adr, city = :c, id_user = :idu, photo = :p WHERE id_coffeeshop = :idc');
        $stmt->bindValue(':n', $data['name']);
        $stmt->bindValue(':adr', $data['address']);
        $stmt->bindValue(':c', $data['city']);
        $stmt->bindValue(':idu', $id_user);
        $stmt->bindValue(':p', $photo);
        $stmt->bindValue(':idc', $id_coffeeshop);
        $stmt->execute();

        $stmt = $this->db->prepare('UPDATE offer SET espresso = :e, filter = :f, plant_based = :pb, breakfast = :bf, brunch = :br, lunch = :l, alcohol = :a, sweets = :sw WHERE id_coffeeshop = :idc');
        $stmt->bindValue(':e', empty($data['espresso']) ? 0 : 1);
        $stmt->bindValue(':f', empty($data['filter']) ? 0 : 1);
        $stmt->bindValue(':pb', empty($data['plant-based']) ? 0 : 1);
        $stmt->bindValue(':bf', empty($data['breakfast']) ? 0 : 1);
        $stmt->bindValue(':br', empty($data['brunch']) ? 0 : 1);
        $stmt->bindValue(':l', empty($data['lunch']) ? 0 : 1);
        $stmt->bindValue(':a', empty($data['alcohol']) ? 0 : 1);
        $stmt->bindValue(':sw', empty($data['sweets']) ? 0 : 1);
        $stmt->bindValue(':idc', $id_coffeeshop);
        $stmt->execute();

        $stmt = $this->db->prepare('UPDATE service SET wifi = :wf, laptop = :lt, vegan = :vg, kids = :kds, dogs = :dgs, wheelchair = :wch, outdoor_seating = :os, creditcard_payments = :ccp WHERE id_coffeeshop = :idc');
        $stmt->bindValue(':wf', empty($data['wifi']) ? 0 : 1);
        $stmt->bindValue(':lt', empty($data['laptop']) ? 0 : 1);
        $stmt->bindValue(':vg', empty($data['vegan']) ? 0 : 1);
        $stmt->bindValue(':kds', empty($data['kids']) ? 0 : 1);
        $stmt->bindValue(':dgs', empty($data['dogs']) ? 0 : 1);
        $stmt->bindValue(':wch', empty($data['wheelchair']) ? 0 : 1);
        $stmt->bindValue(':os', empty($data['outdoor-seating']) ? 0 : 1);
        $stmt->bindValue(':ccp', empty($data['creditcard']) ? 0 : 1);
        $stmt->bindValue(':idc', $id_coffeeshop);
        $stmt->execute();

        for($i = 1; $i <= 7; $i++){
            $stmt = $this->db->prepare('UPDATE opening_hours SET day = :d, time_from = :f, time_to = :t WHERE id_coffeeshop = :idc AND day = :d');
            $stmt->bindValue(':idc', $id_coffeeshop);
            $stmt->bindValue(':d', $i);
            $stmt->bindValue(':f', empty($data["$i-from"]) ? 0 : $data["$i-from"]);
            $stmt->bindValue(':t', empty($data["$i-to"]) ? 0 : $data["$i-to"]);
            $stmt->execute();
        }

    }
    $tmpVars['formData'] = $data;
    return $this->view->render($response, 'index.latte', $tmpVars);
});

//Delete coffeeshop
$app->get('/delete-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $id_coffeeshop = $request->getQueryParam('id_coffeeshop'); //vyctu id coffeeshopu z url

    if(!empty($id_coffeeshop)){
        try{
            $stmt = $this->db->prepare('DELETE FROM coffeeshops WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':idc', $id_coffeeshop);
            $stmt->execute();
            $stmt = $this->db->prepare('DELETE FROM opening_hours WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':idc', $id_coffeeshop);
            $stmt->execute();
            $stmt = $this->db->prepare('DELETE FROM service WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':idc', $id_coffeeshop);
            $stmt->execute();
            $stmt = $this->db->prepare('DELETE FROM offer WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':idc', $id_coffeeshop);
            $stmt->execute();
        } catch (PDOexception $e) {
            exit("error occured");
        }
    } else {
        exit("Person is missing");
    }

    return $response->withHeader('Location', $this->router->pathFor('coffeeshops'));
})->setName('delete-coffeeshop');

//Add new cofffeshop
$app->get('/add-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $data['coffeeshop'] = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    $data['offer'] = [
        'espresso' => 0,
        'filter' => 0,
        'plant_based' => 0,
        'breakfast' => 0,
        'brunch' => 0,
        'lunch' => 0,
        'alcohol' => 0,
        'sweets' => 0,
    ];

    $data['service'] = [
        'wifi' => 0,
        'laptop' => 0,
        'vegan' => 0,
        'kids' => 0,
        'dogs' => 0,
        'wheelchair' => 0,
        'outdoor_seating' => 0,
        'creditcard_payments' => 0,
    ];

    $data['opening_hours'] = [
        'day' => null,
        'time_from' => null,
        'time_to' => null,
    ];

    return $this->view->render($response, 'add-coffeeshop.latte', $data);
})->setName('add-coffeeshop');

$app->post('/add-coffeeshop', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $formData = $request->getParsedBody();
    $id_user = 1;
    $id_coffeeshop = null;
    $data = [];

    if ( empty($formData['name']) || empty($formData['address']) || empty($formData['city']) ) {
		$data['message'] = 'Please fill required fields!';
	} else {

            $stmt = $this->db->prepare('INSERT INTO coffeeshops (name, address, city, id_user) VALUES (:nm, :adr, :city, :idu)');
            $stmt->bindValue(':nm', $formData['name']);
            $stmt->bindValue(':adr', $formData['address']);
            $stmt->bindValue(':city', $formData['city']);
            $stmt->bindValue(':idu', $id_user);
            $stmt->execute();
            $data['message'] = 'Person successfully added';

            $tmp = $this->db->prepare('SELECT max(id_coffeeshop) as id FROM coffeeshops');
            $tmp->execute();
            $id_coffeeshop = $tmp->fetch();


            $stmt = $this->db->prepare('INSERT INTO offer (espresso, filter, plant_based, breakfast, brunch, lunch, alcohol, sweets, id_coffeeshop) VALUES (:esp, :filter, :pb, :bf, :brunch, :lunch, :alco, :sw, :idc)');
            $stmt->bindValue(':esp', empty($formData['espresso']) ? 0 : 1);
            $stmt->bindValue(':filter', empty($formData['filter']) ? 0 : 1);
            $stmt->bindValue(':pb', empty($formData['plant-based']) ? 0 : 1);
            $stmt->bindValue(':bf', empty($formData['breakfast']) ? 0 : 1);
            $stmt->bindValue(':brunch', empty($formData['brunch']) ? 0 : 1);
            $stmt->bindValue(':lunch', empty($formData['lunch']) ? 0 : 1);
            $stmt->bindValue(':alco', empty($formData['alcohol']) ? 0 : 1);
            $stmt->bindValue(':sw', empty($formData['sweets']) ? 0 : 1);
            $stmt->bindValue(':idc', $id_coffeeshop['id'] ? $id_coffeeshop['id'] : 0);
            $stmt->execute();

            $stmt = $this->db->prepare('INSERT INTO service (wifi, laptop, vegan, kids, dogs, wheelchair, outdoor_seating, creditcard_payments, id_coffeeshop) VALUES (:wf, :lt, :vg, :kds, :dgs, :wch, :os, :ccp, :idc)');
            $stmt->bindValue(':wf', empty($formData['wifi']) ? 0 : 1);
            $stmt->bindValue(':lt', empty($formData['laptop']) ? 0 : 1);
            $stmt->bindValue(':vg', empty($formData['vegan']) ? 0 : 1);
            $stmt->bindValue(':kds', empty($formData['kids']) ? 0 : 1);
            $stmt->bindValue(':dgs', empty($formData['dogs']) ? 0 : 1);
            $stmt->bindValue(':wch', empty($formData['wheelchair']) ? 0 : 1);
            $stmt->bindValue(':os', empty($formData['outdoor-seating']) ? 0 : 1);
            $stmt->bindValue(':ccp', empty($formData['creditcard']) ? 0 : 1);
            $stmt->bindValue(':idc', $id_coffeeshop['id'] ? $id_coffeeshop['id'] : 0);
            $stmt->execute();

            for($i = 1; $i <= 7; $i++){
                $stmt = $this->db->prepare('INSERT INTO opening_hours (id_coffeeshop, day, time_from, time_to) VALUES (:idc, :d, :f, :t)');
                $stmt->bindValue('idc', $id_coffeeshop['id'] ? $id_coffeeshop['id'] : 0);
                $stmt->bindValue('d', $i);
                $stmt->bindValue('f', empty($formData["$i-from"]) ? 0 : $formData["$i-from"]);
                $stmt->bindValue('t', empty($formData["$i-to"]) ? 0 : $formData["$i-to"]);
                $stmt->execute();
            }
        $data['formData'] = $formData;
        // echo var_export($data);

    }
    return $this->view->render($response, 'add-coffeeshop.latte', $data);
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
