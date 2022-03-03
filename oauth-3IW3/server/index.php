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

function insertApp($app) {
    insertData('./data/apps.db', $app);
}

function insertCode($code) {
    insertData('./data/codes.db', $code);
}
function insertToken($token) {
    insertData('./data/tokens.db', $token);
}

function findBy($filename, $criteria) {
    $database = readDatabase($filename);

    $result = array_values(array_filter(
        $database, 
        fn($app) => count(array_intersect_assoc($app, $criteria)) === count($criteria)
    ));

    return $result[0] ?? null;
}

function findAppBy($criteria) {
    return findBy('./data/apps.db', $criteria);
}

function findCodeBy($criteria) {
    return findBy('./data/codes.db', $criteria);
}
function findTokenBy($criteria) {
    return findBy('./data/tokens.db', $criteria);
}


function register() {
    ['name' => $name, 'url' => $url, 'redirect_uri' => $redirectUri] = $_POST;
    if (findAppBy(['name'=> $name])) {
        http_response_code(409);
        return;
    }
    $app = array_merge(
        ['name' => $name, 'url' => $url, 'redirect_uri' => $redirectUri],
        ['client_id' => uniqid(), 'client_secret' => uniqid()]);
    insertApp('./data/apps.db', $app);
    http_response_code(201);
    echo json_encode($app);
}

function auth() {
    ['client_id' => $clientId, 'scope'=> $scope, 'state' => $state, 'redirect_uri' => $redirect_uri] = $_GET;
    $app = findAppBy(['client_id'=> $clientId, 'redirect_uri' => $redirect_uri]);
    if(!$app) {
        http_response_code(404);
        return;
    }
    if (findTokenBy(['client_id' => $clientId])) {
        return authSuccess();
    }
    echo "Name: {$app['name']}<br>";
    echo "Scope: {$scope}<br>";
    echo "URL: {$app['url']}<br>";
    echo "<a href='/auth-success?client_id={$app['client_id']}&state={$state}'>Oui</a>&nbsp;";
    echo "<a href='/failed'>Non</a>";
}

function authSuccess() {
    ['client_id' => $clientId, 'state' => $state] = $_GET;
    $app = findAppBy(['client_id'=> $clientId]);
    if(!$app) {
        http_response_code(404);
        return;
    }
    $code = [
        "code" => bin2hex(random_bytes(16)),
        "client_id" => $clientId,
        "expiresAt" => time() + (60*5),
    ];
    insertCode($code);
    header("Location: ${app['redirect_uri']}?state=${state}&code=${code['code']}");
}

function token() {
    ['client_id' => $clientId, 'client_secret' => $clientSecret, 'code' => $code, 'grant_type' => $grantType, 'redirect_uri' => $redirect] = $_GET;
    $app = findAppBy(['client_id'=> $clientId, 'client_secret' => $clientSecret, 'redirect_uri' => $redirect]);
    if(!$app) {
        http_response_code(401);
        return;
    }
    $code = findCodeBy(['code' => $code, 'client_id'=> $clientId]);
    if(!$code) {
        http_response_code(404);
        return;
    }
    if($code['expiresAt'] < time()) {
        http_response_code(400);
        return;
    }
    $token = [
        "access_token" => bin2hex(random_bytes(16)),
        "expiresAt" => time() + (60*60*24*30),
        "client_id" => $clientId,
        "user_id"=> bin2hex(random_bytes(16)),
        "code" => $code['code'],
    ];
    insertToken($token);
    http_response_code(201);
    echo json_encode([
        "access_token" => $token['access_token'], 
        "expires_in" => $token['expiresAt']
    ]);
}

function me() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    list($type, $token) = explode(' ', $authHeader);
    if($type !== 'Bearer') {
        http_response_code(401);
        return;
    }
    $token = findTokenBy(['access_token' => $token]);
    if(!$token) {
        http_response_code(401);
        return;
    }
    if($token['expiresAt'] < time()) {
        http_response_code(400);
        return;
    }
    $code = findCodeBy(['code' => $token['code']]);
    if(!$code) {
        http_response_code(401);
        return;
    }
    echo json_encode([
        "user_id" => $token['user_id'],
        "lastname" => 'Doe',
        "firstname" => 'John',
    ]);
}


$route = $_SERVER['REQUEST_URI'];
switch(strtok($route, "?")) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    case '/auth-success':
        authSuccess();
    case '/token':
        token();
        break;
    case '/me':
        me();
        break;
    default:
        echo '404';
        break;
}
