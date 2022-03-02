<?php

function readDatabase($filename) {
    return array_map(
        fn ($line) => json_decode($line, true), 
        file($filename)
    );
}

function writeDatabase($filename, $data) {
    file_put_contents(
        $filename, 
        implode(
            "\n", 
            array_map(
                fn ($line) => json_encode($line), 
                $data
            )
        )
    );
}

function findBy($filename, $criteria) {
    $data = readDatabase($filename);
    $result = array_values(array_filter(
        $data, 
        fn ($line) => count(array_intersect_assoc($line, $criteria)) == count($criteria)
    ));

    return count($result) > 0 ? $result[0] : null;
}

function findAppBy($criteria) {
    return findBy('./data/apps.db', $criteria);
}

function insertRow($filename, $row) {
    $data = readDatabase($filename);
    $data[] = $row;
    writeDatabase($filename, $data);
}

function insertApp($app) {
    insertRow('./data/apps.db', $app);
}

function register() {
    ['name' => $name, 'url'=> $url, 'redirect_success' => $redirect] = $_POST;
    if (findAppBy(['name' => $name])) {
        http_response_code(409);
        return;
    }
    $app = [
        'name' => $name,
        'url' => $url,
        'redirect_success' => $redirect,
        'client_id' => bin2hex(random_bytes(16)),
        'client_secret' => bin2hex(random_bytes(16)),
    ];
    insertApp($app);
    http_response_code(201);
    echo json_encode($app);
}

function auth() {
    ['client_id' => $clientId, 'state' => $state, 'redirect_uri' => $redirect, 'scope' => $scope] = $_GET;
    $app = findAppBy(['client_id' => $clientId, 'redirect_success' => $redirect]);
    if (is_null($app)) {
        http_response_code(400);
        return;
    }
    echo "App: $app[name]<br>";
    echo "Url: $app[url]<br>";
    echo "Scope: $scope<br>";
    echo "<a href='/auth-success?scope=$scope&client_id=$clientId&state=$state'>Oui</a>&nbsp;";
    echo "<a href='/auth-failed'>Non</a>";
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
}