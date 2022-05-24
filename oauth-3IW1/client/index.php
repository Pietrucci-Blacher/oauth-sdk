<?php

define('OAUTH_CLIENT_ID', '621f59c71bc35');
define('OAUTH_CLIENT_SECRET', '621f59c71bc36');

function login()
{
    $queryParams= http_build_query([
        'client_id' => OAUTH_CLIENT_ID,
        'redirect_uri' => 'http://localhost:8081/callback',
        'response_type' => 'code',
        'scope' => 'basic',
        "state" => bin2hex(random_bytes(16))
    ]);
    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Login with OauthServer</a>";
}

// Exchange code for token then get user info
function callback()
{
    ["code" => $code, "state" => $state] = $_GET;
    $queryParams = http_build_query([
        'client_id' => OAUTH_CLIENT_ID,
        'client_secret' => OAUTH_CLIENT_SECRET,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => 'http://localhost:8081/callback',
    ]);
    $response = file_get_contents("http://server:8080/token?{$queryParams}");
    $token = json_decode($response, true);
    
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer {$token['access_token']}"
            ]
        ]);
    $response = file_get_contents("http://server:8080/me", false, $context);
    $user = json_decode($response, true);
    echo "Hello {$user['lastname']} {$user['firstname']}";
}

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/callback':
        callback();
        break;
    default:
        http_response_code(404);
        break;
}
