<?php

function login()
{
    $queryParams= http_build_query(array(
        "client_id" => "621e3b8d1f964",
        "redirect_uri" => "http://localhost:8081/callback",
        "response_type" => "code",
        "scope" => "read,write",
        "state" => bin2hex(random_bytes(16))
    ));
    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Se connecter via Oauth Server</a>";
}

function callback()
{
    $code = $_GET['code'];
    $state = $_GET['state'];
    $clientId = "621e3b8d1f964";
    $clientSecret = "621e3b8d1f966";
    $redirectUri = "http://localhost:8081/callback";
    $data = http_build_query(array(
        "grant_type" => "authorization_code",
        "code" => $code,
        "redirect_uri" => $redirectUri,
        "client_id" => $clientId,
        "client_secret" => $clientSecret
    ));
    $url = "http://oauth-server:8080/token?{$data}";
    $result = file_get_contents($url);
    $result = json_decode($result, true);
    $accessToken = $result['access_token'];

    $url = "http://oauth-server:8080/me";
    $options = array(
        'http' => array(
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $accessToken
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result = json_decode($result, true);
    echo "Hello {$result['lastname']}";
}

$route = $_SERVER['REQUEST_URI'];
switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/callback':
        callback();
        break;
    default:
        echo '404';
        break;
}
