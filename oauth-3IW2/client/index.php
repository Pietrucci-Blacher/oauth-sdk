<?php

define("CLIENT_ID", '67dc2be521bec2ff862d3ab057de216b');
define("CLIENT_SECRET", '04054cf433eeb3976252c81b6d657fda');

// Create a login page with a link to oauth
function login()
{
    $queryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> CLIENT_ID,
        "scope"=>"profile",
        "response_type"=>"code",
        "redirect_uri"=>"http://localhost:8081/oauth_success",
    ]);
    echo "
        <form method=\"POST\" action=\"/oauth_success\">
            <input type=\"text\" name=\"username\"/>
            <input type=\"password\" name=\"password\"/>
            <input type=\"submit\" value=\"Login\"/>
        </form>
    ";
    $fbQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> FB_CLIENT_ID,
        "scope"=>"basic",
        "redirect_uri"=>"http://localhost:8081/fb_oauth_success",
    ]);
    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Login with Oauth-Server</a>";
    echo "<a href=\"https://www.facebook.com/v13.0/dialog/oauth?{$fbQueryParams}\">Login with Facebook</a>";
}

// get token from code then get user info
function callback()
{
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        ["username"=> $username, "password" => $password] = $_POST;
        $specifParams = [
            "grant_type" => "password",
            "username" => $username,
            "password" => $password,
       ];
    } else {
        ["code"=> $code, "state" => $state] = $_GET;
        $specifParams = [
            "grant_type" => "authorization_code",
            "code" => $code
        ];
    }
    $queryParams = http_build_query(array_merge(
        $specifParams,
        [
            "redirect_uri" => "http://localhost:8081/oauth_success",
            "client_id" => CLIENT_ID,
            "client_secret" => CLIENT_SECRET,
        ]
    ));
    $response = file_get_contents("http://server:8080/token?{$queryParams}");
    if (!$response) {
        echo $http_response_header;
        return;
    }
    ["access_token" => $token] = json_decode($response, true);


    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: Bearer {$token}"
        ]
    ]);
    $response = file_get_contents("http://server:8080/me", false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    var_dump(json_decode($response, true));
}

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/oauth_success':
        callback();
        break;
    default:
        http_response_code(404);
}
