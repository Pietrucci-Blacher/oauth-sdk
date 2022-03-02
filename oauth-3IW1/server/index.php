<?php

function readDatabase($filename) {
    return array_map(fn ($line) => json_decode($line, true), file($filename));
}

function writeDatabase($filename, $data) {
    file_put_contents(
        $filename,
        implode("\n", 
            array_map(fn ($line) => json_encode($line), $data)
        )
    );
}

function insert($filename, $line) {
    $data = readDatabase($filename);
    $data[] = $line;
    writeDatabase($filename, $data);
}

function findBy($filename, $criteria) {
    $data = readDatabase($filename);
    $result = array_filter(
        $data,
        fn ($line) => count(array_intersect_assoc($line, $criteria)) == count($criteria)
    );
    return count($result) > 0 ? $result[0] : null;
}

function findAppByName($name) {
    return findBy('./data/apps.db', ['name' => $name]);
}
function insertApp($app) {
    insert('./data/apps.db', $app);
}

function register() {
    ['name' => $name, 'url' => $url, 'redirect_success' => $redirect] = $_POST;
    if (findAppByName($name)) {
        http_response_code(409);
        return;
    }
    $app= [
        'name' => $name,
        'url' => $url,
        'redirect_success' => $redirect,
        "client_id" => uniqid(), 'client_secret' => uniqid()
    ];
    insertApp($app);
    http_response_code(201);
    echo json_encode($app);
}

$route = $_SERVER["REQUEST_URI"];
switch(strtok($route, "?")) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    default:
        http_response_code(404);
        break;
}