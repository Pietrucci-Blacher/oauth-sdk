<?php

function readDatabase($filename) {
    $data = file($filename);

    return array_map(fn ($line) => json_decode($line, true), $data);
}

function writeDatabase($filename, $data) {
    file_put_contents($filename, implode(
        "\n",
        array_map(
            fn($line) => json_encode($line),
            $data
        )
    ));
}
function insertData($filename, $data) {
    $database = readDatabase($filename);
    $database[] = $data;
    writeDatabase($filename, $database);
}

function findBy($filename, $criteria) {
    $database = readDatabase($filename);

    $result = array_filter(
        $database, 
        fn($app) => count(array_intersect_assoc($app, $criteria)) === count($criteria)
    );

    return $result[0] ?? null;
}

function findAppByName($name) {
    return findBy('./data/apps.db', ['name' => $name]);
}


function register() {
    ['name' => $name, 'url' => $url, 'redirect_uri' => $redirectUri] = $_POST;
    if (findAppByName($name)) {
        http_response_code(409);
        return;
    }
    $app = array_merge(
        ['name' => $name, 'url' => $url, 'redirect_uri' => $redirectUri],
        ['client_id' => uniqid(), 'client_secret' => uniqid()]);
    insertData('./data/apps.db', $app);
    http_response_code(201);
    echo json_encode($app);
}

function auth() {
    ['client_id' => $clientId, 'scope'=> $scope, 'state' => $state, 'redirect_uri' => $redirect_uri] = $_GET;
    $app = findBy(
        "./data/apps.db",
        ['client_id'=> $clientId, 'redirect_uri' => $redirect_uri]
    );
    if(!$app) {
        http_response_code(404);
        return;
    }
    echo "Name: {$app['name']}<br>";
    echo "Scope: {$scope}<br>";
    echo "URL: {$app['url']}<br>";
    echo "<a href='/auth-success?client_id={$app['client_id']}&state={$state}'>Oui</a>&nbsp;";
    echo "<a href='/failed'>Non</a>";
}


$route = $_SERVER['REQUEST_URI'];
switch(strtok($route, "?")) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    default:
        echo '404';
        break;
}
