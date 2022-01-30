<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Cloudinary\Api\Upload\UploadApi;

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');


$app->get('/coffeeshops', function (Request $request, Response $response, $args) {
    // Render index view
        $stmt = $this->db->prepare('SELECT * FROM coffeeshops');
        $stmt->execute(); // zde mame ulozene data z databaze
        $data['coffeeshops'] = $stmt->fetchall();

        $counter = 0;
        foreach($data['coffeeshops'] as $cid){
            $stmt = $this->db->prepare('SELECT avg(rating) as rating FROM reviews WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':idc', $cid['id_coffeeshop']);
            $stmt->execute();
            $total = $stmt->fetch(); 
            $data['coffeeshops'][$counter]['rating'] = number_format(($total['rating']), 1);
            $counter++;
        }

    return $this->view->render($response, 'coffeeshops.latte', $data);
})->setName('coffeeshops');

$app->get('/coffeeshop/profile', function (Request $request, Response $response, $args) {
    // Render add-coffeeshop view
    $id_coffeeshop = $request->getQueryParam('id_coffeeshop');
    $id_user = $_SESSION['logged_user']['id_user'];


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

    $stmt = $this->db->prepare('SELECT * FROM reviews WHERE id_coffeeshop = :idc ORDER BY name ASC');
    $stmt->bindParam(':idc', $id_coffeeshop);
    $stmt->execute();
    $data['reviews'] = $stmt->fetchall();

    $stmt = $this->db->prepare('SELECT * FROM favourite WHERE id_user = :idu');
    $stmt->bindParam(':idu', $id_user);
    $stmt->execute();
    $tmp = $stmt->fetchall();

    $data['isFavourite'] = false;

    foreach($tmp as $csId){
        if($csId['id_coffeeshop'] == $id_coffeeshop){
            $data['isFavourite'] = true;
        }
    }

    echo var_dump($_SESSION['logged_user']);

    return $this->view->render($response, 'profile.latte', $data);
})->setName('profile');

$app->post('/coffeeshop/profile', function (Request $request, Response $response, $args){
    $id_coffeeshop = $request->getQueryParam('id_coffeeshop');
    $data = $request->getParsedBody();

    if(empty($data['review'])){
        $tmpVars['message'] = 'Please fill in the review';
    } else {
        $stmt = $this->db->prepare('INSERT INTO reviews (id_coffeeshop, rating, review, name) VALUES (:idc, :rat, :rev, :n)');
        $stmt->bindValue(':idc', $id_coffeeshop);
        $stmt->bindValue(':rat', $data['rating']);
        $stmt->bindValue(':rev', empty($data['review']) ? "No review" : $data['review']);
        $stmt->bindValue(':n', empty($data['uname']) ? 'anonymous' : $data['uname']);
        $stmt->execute();
    }
    

    return $response->withHeader('Location', $this->router->pathFor('profile') . '?id_coffeeshop=' . $id_coffeeshop);
});


$app->group('/auth', function() use($app){

    $app->post('/coffeeshop/profile/favourite', function (Request $request, Response $response, $args){
        $id_coffeeshop = $request->getQueryParam('id_coffeeshop');
        $id_user = $_SESSION['logged_user']['id_user'];
        $data = $request->getParsedBody();

        echo var_dump($data['id_coffeeshop']);
    
        if($data['isFavourite'] == false){
            $stmt = $this->db->prepare('INSERT INTO favourite (id_user, id_coffeeshop) VALUES (:idu, :idc)');
            $stmt->bindValue(':idu', $id_user);
            $stmt->bindValue(':idc', $data['id_coffeeshop']);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare('DELETE FROM favourite WHERE id_user = :idu AND id_coffeeshop = :idc');
            $stmt->bindValue(':idu', $id_user);
            $stmt->bindValue(':idc', $data['id_coffeeshop']);
            $stmt->execute();
        }

       if ($data['source'] == "coffeeshop-profile"){
           return $response->withHeader('Location', $this->router->pathFor('profile') . '?id_coffeeshop=' . $data['id_coffeeshop']);
       } else {
        return $response->withHeader('Location', $this->router->pathFor('user-profile') . '?id_user=' . $id_user);
       }
    });

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
        $id_user = $_SESSION['logged_user']['id_user'];
        
        if(empty($data['name']) || empty($data['address']) || empty($data['city'])) {
            $tmpVars['message'] = 'Please fill required fields';
        } else {
            if(!empty($_FILES['file']['tmp_name'])){
            $tmpFile = (new UploadApi())->upload($_FILES["file"]["tmp_name"]);
            }

            $stmt = $this->db->prepare('UPDATE coffeeshops SET name = :n, address = :adr, city = :c, id_user = :idu WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':n', $data['name']);
            $stmt->bindValue(':adr', $data['address']);
            $stmt->bindValue(':c', $data['city']);
            $stmt->bindValue(':idu', $id_user);
            $stmt->bindValue(':idc', $id_coffeeshop);
            $stmt->execute();

            echo var_export($tmpFile);
            if(isset($tmpFile)){
                echo var_export($tmpFile);
                $stmt = $this->db->prepare('UPDATE coffeeshops SET photo = :p WHERE id_coffeeshop = :idc');
                $stmt->bindValue(':p', $tmpFile['secure_url']);
                $stmt->bindValue(':idc', $id_coffeeshop);
                $stmt->execute();
            }

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

        return $response->withHeader('Location', $this->router->pathFor('profile') . '?id_coffeeshop=' . $id_coffeeshop);
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
                $stmt = $this->db->prepare('DELETE FROM reviews WHERE id_coffeeshop = :idc');
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
        $id_user = $_SESSION['logged_user']['id_user'];
        $id_coffeeshop = null;
        $data = [];

        if ( empty($formData['name']) || empty($formData['address']) || empty($formData['city']) ) {
            $data['message'] = 'Please fill required fields!';
        } else {
                $target_file = basename($_FILES["file"]["tmp_name"]);
                $tmpFile = (new UploadApi())->upload($_FILES["file"]["tmp_name"]);

                $stmt = $this->db->prepare('INSERT INTO coffeeshops (name, address, city, photo, id_user) VALUES (:nm, :adr, :city, :p, :idu)');
                $stmt->bindValue(':nm', $formData['name']);
                $stmt->bindValue(':adr', $formData['address']);
                $stmt->bindValue(':city', $formData['city']);
                $stmt->bindValue(':p', $tmpFile['secure_url']);
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
        }
        return $response->withHeader('Location', $this->router->pathFor('profile') . '?id_coffeeshop=' . $id_coffeeshop['id']);
    })->setName('add-coffeeshop');

    $app->get('/user-profile', function (Request $request, Response $response, $args) {
        // Render user profile
        $id_user = $request->getQueryParam('id_user');

        $stmt = $this->db->prepare('SELECT id_user, nickname, first_name, last_name, gender, birthday, profession FROM users WHERE id_user = :idu');
        $stmt->bindValue(':idu', $id_user);
        $stmt->execute();
        $data['personal'] = $stmt->fetch();

        $stmt = $this->db->prepare('SELECT cs.id_coffeeshop, cs.name FROM coffeeshops AS cs JOIN favourite AS f ON cs.id_coffeeshop = f.id_coffeeshop AND f.id_user = :idu');
        $stmt->bindValue(':idu', $id_user);
        $stmt->execute();
        $data['coffeeshops'] = $stmt->fetchall();

        // echo var_dump($data['coffeeshops']);

        $stmt = $this->db->prepare('SELECT * FROM favourite WHERE id_user = :idu');
        $stmt->bindParam(':idu', $id_user);
        $stmt->execute();
        $tmp = $stmt->fetchall();

        $counter = 0;
        
        foreach($data['coffeeshops'] as $cid){
            $stmt = $this->db->prepare('SELECT avg(rating) as rating FROM reviews WHERE id_coffeeshop = :idc');
            $stmt->bindValue(':idc', $cid['id_coffeeshop']);
            $stmt->execute();
            $total = $stmt->fetch(); 
            $data['coffeeshops'][$counter]['rating'] = number_format(($total['rating']), 1);

            $data['coffeeshops'][$counter]['isFavourite'] = false;

            foreach($tmp as $csId){
                if($csId['id_coffeeshop'] == $cid['id_coffeeshop']){
                    $data['coffeeshops'][$counter]['isFavourite'] = true;
                }
            }
            $counter++;
        }

        // echo var_dump($request->getUri()->getPath());

        return $this->view->render($response, 'user-profile.latte', $data);
    })->setName('user-profile');

    $app->get('/edit-user', function (Request $request, Response $response, $args) {
        // Render add-coffeeshop view
        $params = $request->getQueryParams(); # $params = [id_person => 1232, firstname => aaa]

        if(!empty($params['id_user'])){
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id_user = :idu');
            $stmt->bindValue(':idu', $params['id_user']);
            $stmt->execute();
            $data['user'] = $stmt->fetch();

            if(empty($data['user'])) {
                exit('person not found');
            }else {
                return $this->view->render($response, 'edit-user.latte', $data);
            }
        }

    })->setName('edit-user');

    $app->post('/edit-user', function (Request $request, Response $response, $args){
        $formData = $request->getParsedBody();
        $passwd_hash = hash('sha256', $formData['password']);
        $id_user = $_SESSION['logged_user']['id_user'];
        // $id_user = $request->getQueryParam('id_user');
    
        $stmt = $this->db->prepare('UPDATE users SET nickname = :nn, first_name = :fn, last_name = :ln, birthday = :bd, gender = :g, profession = :pf, password = :pw WHERE id_user = :idu');
        $stmt->bindValue(':nn', $formData['nickname']);
        $stmt->bindValue(':fn', $formData['first_name']);
        $stmt->bindValue(':ln', $formData['last_name']);
        $stmt->bindValue(':bd', empty($formData['birthday']) ? null : $formData['birthday']);
        $stmt->bindValue(':g', empty($formData['gender']) ? null : $formData['gender']);
        $stmt->bindValue(':pf', $formData['profession']);
        $stmt->bindValue(':pw', $passwd_hash);
        $stmt->bindValue(':idu', $id_user);
        $stmt->execute();
        
        return $response->withHeader('Location', $this->router->pathFor('user-profile') . '?id_user=' . $id_user);
    });

    $app->get('/delete-user', function (Request $request, Response $response, $args) {
        // Render add-coffeeshop view
        $id_user = $request->getQueryParam('id_user'); //vyctu id coffeeshopu z url

        if(!empty($id_user)){
            try{
                $stmt = $this->db->prepare('DELETE FROM users WHERE id_user = :idu');
                $stmt->bindValue(':idu', $id_user);
                $stmt->execute();

            } catch (PDOexception $e) {
                exit("error occured");
            }
        } else {
            exit("Person is missing");
        }

        return $response->withHeader('Location', $this->router->pathFor('logout'));
    })->setName('delete-user');

})->add(function($request, $response, $next){
    if(!empty($_SESSION['logged_user'])){ 
        return $next($request, $response);
    }else {
        return $response->withHeader('Location', $this->router->pathFor('login'));
    }
});


$app->get('/sign-up', function (Request $request, Response $response, $args) {
    // Render sign-up view
    $data['user'] = [
        'first_name' =>'',
        'last_name' => '',
        'nickname' => '',
        'birthday' => null,
        'gender' => '',
        'profession' => ''
    ];

    return $this->view->render($response, 'sign-up.latte', $data);
})->setName('sign-up');

$app->post('/sign-up', function (Request $request, Response $response, $args){
    $formData = $request->getParsedBody();
    $passwd_hash = hash('sha256', $formData['password']);

    $stmt = $this->db->prepare('INSERT INTO users (nickname, first_name, last_name, birthday, gender, profession, password) VALUES (:nn, :fn, :ln, :bd, :g, :pf, :pw)');

    $stmt->bindValue(':nn', $formData['nickname']);
    $stmt->bindValue(':fn', $formData['first_name']);
    $stmt->bindValue(':ln', $formData['last_name']);
    $stmt->bindValue(':bd', empty($formData['birthday']) ? null : $formData['birthday']);
    $stmt->bindValue(':g', empty($formData['gender']) ? null : $formData['gender']);
    $stmt->bindValue(':pf', $formData['profession']);
    $stmt->bindValue(':pw', $passwd_hash);
    $stmt->execute();
    
    return $response->withHeader('Location', $this->router->pathFor('login'));

});

$app->get('/login', function (Request $request, Response $response, $args) {
    // Render login view
    return $this->view->render($response, 'login.latte');
})->setName('login');

$app->post('/login', function (Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    $passwd_hash = hash('sha256', $formData['password']);

    $stmt = $this->db->prepare('SELECT id_user, first_name, nickname, last_name, isAdmin FROM users WHERE lower(nickname) = lower(:nn) AND password = :pw');
    $stmt->bindValue(':nn', $formData['nickname']);
    $stmt->bindValue(':pw', $passwd_hash);
    $stmt->execute();

    $logged_user = $stmt->fetch();

    if($logged_user){
        $_SESSION['logged_user'] = $logged_user;
        setcookie('first_name', $logged_user['first_name']);

        return $response->withHeader('Location', $this->router->pathFor('coffeeshops')); 
    }else {
        return $this->view->render($response, 'login.latte', ['message' => 'Wrong username or password']);

    }
});

    //Logout user
    $app->get('/logout', function (Request $request, Response $response, $args) {
        session_destroy();
        return $response->withHeader('Location', $this->router->pathFor('index'));
    })->setName('logout');
