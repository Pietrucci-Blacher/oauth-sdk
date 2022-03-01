<?php

function readDatabase($filename) {
    $data = file($filename);

    return array_map(fn ($line) => json_decode($line), $data);
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

function findAppByName($name) {
    $apps = readDatabase('./data/apps.db');

    foreach ($apps as $app) {
        if ($app->name === $name) {
            return $app;
        }
    }

    return null;
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
    echo "auth";
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