<?php

function readDatabase($filename)
{
    return array_map(fn ($line) => json_decode($line, true), file($filename));
}

function writeDatabase($filename, $data)
{
    file_put_contents(
        $filename,
        implode(
            "\n",
            array_map(fn ($line) => json_encode($line), $data)
        )
    );
}

function insert($filename, $line)
{
    $data = readDatabase($filename);
    $data[] = $line;
    writeDatabase($filename, $data);
}

function findBy($filename, $criteria)
{
    $data = readDatabase($filename);
    $result = array_values(
        array_filter(
            $data,
            fn ($line) => count(array_intersect_assoc($line, $criteria)) == count($criteria)
        )
    );
    return count($result) > 0 ? $result[0] : null;
}

function findAppBy($criteria)
{
    return findBy("./data/apps.db", $criteria);
}

function findCodeBy($criteria)
{
    return findBy("./data/codes.db", $criteria);
}
function findUserBy($criteria)
{
    return findBy("./data/users.db", $criteria);
}
function findTokenBy($criteria)
{
    return findBy("./data/tokens.db", $criteria);
}
function findAppByName($name)
{
    return findAppBy(['name' => $name]);
}
function insertApp($app)
{
    insert('./data/apps.db', $app);
}
function insertCode($code)
{
    insert('./data/codes.db', $code);
}
function insertToken($token)
{
    insert('./data/tokens.db', $token);
}

function register()
{
    ['name' => $name, 'url' => $url, 'redirect_success' => $redirect] = $_POST;
    if (findAppByName($name)) {
        http_response_code(409);
        return;
    }
    $app= [
        'name' => $name,
        'url' => $url,
        'redirect_success' => $redirect,
        "client_id" => bin2hex(random_bytes(16)), 'client_secret' => bin2hex(random_bytes(16))
    ];
    insertApp($app);
    http_response_code(201);
    echo json_encode($app);
}

function auth()
{
    ['client_id'=> $clientId, 'scope' => $scope, 'redirect_uri' => $redirect, 'state' => $state] = $_GET;
    $app = findAppBy(["client_id" => $clientId, 'redirect_success' => $redirect]);
    if (!$app) {
        http_response_code(404);
        return;
    }
    echo "Name: $app[name]<br>";
    echo "Scope: $scope<br>";
    echo "Url: $app[url]<br>";
    echo "<a href='/auth-success?state=$state&client_id=$clientId'>Oui</a>&nbsp;";
    echo "<a href='/failed'>Non</a>";
}

function authSuccess()
{
    ['state' => $state, 'client_id' => $clientId] = $_GET;
    $app = findAppBy(["client_id" => $clientId]);
    if (!$app) {
        http_response_code(404);
        throw new \RuntimeException();
    }
    $code = [
        "code" => bin2hex(random_bytes(16)),
        "client_id" => $clientId,
        "user_id" => 1,
        "expires_at" => time() + 3600,
    ];
    insertCode($code);
    header("Location: $app[redirect_success]?code=$code[code]&state=$state");
}

function handleAuthCode($input, $clientId)
{
    ['code' => $code] = $input;
    $code = findCodeBy(["code"=> $code, 'client_id' => $clientId]);
    if (!$code) {
        throw new \RuntimeException(404);
    }
    if ($code['expires_at'] < time()) {
        throw new \RuntimeException(400);
    }
    return $code['user_id'];
}

function handlePassword($input)
{
    ["username" => $username, "password" => $password] = $input;
    $user = findUserBy(["username" => $username, 'password' => $password]);
    if (!$user) {
        throw new \RuntimeException(404);
    }
    return $user['id'];
}

function handleDefault()
{
    throw new \RuntimeException(400);
}

function token()
{
    $input = $_SERVER['REQUEST_METHOD'] === "POST" ? $_POST : $_GET;

    ['redirect_uri' => $redirect, 'client_id'=> $clientId, 'client_secret' => $clientSecret, 'grant_type' => $grantType] = $input;

    $app = findAppBy([
        "client_id" => $clientId, "client_secret" => $clientSecret, 'redirect_success' => $redirect
    ]);
    if (!$app) {
        http_response_code(404);
        return;
    }
    
    try {
        $userId = match ($grantType) {
            "authorization_code" => handleAuthCode($input, $clientId),
            "password" => handlePassword($input),
            "client_credentials" => null,
            default =>  handleDefault()
        };

        $token = [
            'token' => bin2hex(random_bytes(16)),
            'expires_at' => time() + (60*60*24*30),
            'user_id' => $userId,
            'client_id' => $clientId,
        ];
        insertToken($token);
        http_response_code(201);
        echo json_encode(["access_token" => $token['token'], 'expire_in' => $token['expires_at']]);
    } catch (\RuntimeException $e) {
        http_response_code(intval($e->getMessage()));
    }
}

function me()
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    [$type, $token] = explode(" ", $auth);
    if ($type !== "Bearer") {
        http_response_code(401);
        return;
    }
    $token = findTokenBy(["token" => $token]);
    if (!$token || $token['expires_at'] < time()) {
        http_response_code(401);
        return;
    }
    $user = findUserBy([
        "id" => $token['user_id']
    ]);
    echo json_encode([
        'user_id' => $token['user_id'],
        'lastname' => $user['lastname'],
        'firstname' => $user['firstname']
    ]);
}

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    case '/auth-success':
        authSuccess();
        break;
    case '/token':
        token();
        break;
    case '/me':
        me();
        break;
    default:
        http_response_code(404);
        break;
}
