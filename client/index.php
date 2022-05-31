<?php

const CLIENT_ID = '67dc2be521bec2ff862d3ab057de216b';
const CLIENT_SECRET = 'cf519bc17340aa347774be36443cdbf1';
const FB_CLIENT_ID = '651128429478820';
const FB_CLIENT_SECRET = '02f2ab1a9c018c523282015c3cb2a468';
const TW_CLIENT_ID = "s77c4qdvjygkwni85rsdj1k48nsy7l";
const TW_CLIENT_SECRET = "qgujena6pclcra5i2s4clmuafbc1vl";
const DISCORD_CLIENT_ID = "980825470713069598";
const DISCORD_CLIENT_SECRET = "HUkrGaLwXoqVuWf_9UwET__a73WYLoIj";

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
        "scope"=>"public_profile,email",
        "redirect_uri"=>"https://localhost/fb_oauth_success",
    ]);

    $twQueryParams = http_build_query([
        "client_id" => TW_CLIENT_ID,
        "scope" => "user:read:email",
        "redirect_uri"=>"https://localhost/twitch_oauth_success",
        "response_type" => "code",
        "state"=>bin2hex(random_bytes(16))
    ]);
    $discordQueryParams = http_build_query([
        "client_id" => DISCORD_CLIENT_ID,
        "scope"=>"email",
        "response_type" => "token",
        "redirect_uri"=> "https://localhost/discord_oauth_success",
        "state"=>bin2hex(random_bytes(16))
    ]);
    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Login with Oauth-Server</a><br>";
    echo "<a href=\"https://www.facebook.com/v13.0/dialog/oauth?{$fbQueryParams}\">Login with Facebook</a><br>";
    echo "<a href=\"https://id.twitch.tv/oauth2/authorize?{$twQueryParams}\">Login with Twitch</a><br>";
    echo "<a href=\"https://discord.com/api/oauth2/authorize?{$discordQueryParams}\">Login with Discord</a>";
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

// Facebook oauth: exchange code with token then get user info
function fbcallback()
{
    $token = getToken("https://graph.facebook.com/v13.0/oauth/access_token", FB_CLIENT_ID, FB_CLIENT_SECRET, "https://localhost/fb_oauth_success");
    $user = getFbUser($token);
    $unifiedUser = (fn () => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "firstName" => $user['first_name'],
        "lastName" => $user['last_name'],
    ])();
    var_dump($unifiedUser);
}
function getFbUser($token)
{
    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: Bearer {$token}"
        ]
    ]);
    $response = file_get_contents("https://graph.facebook.com/v13.0/me?fields=last_name,first_name,email", false, $context);
    if (!$response) {
        echo $http_response_header;
        return;
    }
    return json_decode($response, true);
}

function twCallback():void
{
    $token = getToken("https://id.twitch.tv/oauth2/token", TW_CLIENT_ID, TW_CLIENT_SECRET, "https://localhost/twitch_oauth_success");
}

function discordCallback(): void
{
    $token = getToken("https://discord.com/api/oauth2/token", DISCORD_CLIENT_ID, DISCORD_CLIENT_SECRET, "https://localhost/discord_oauth_success");
    var_dump($token);
}


function getTokenFb($baseUrl, $clientId, $clientSecret, $redirect_uri)
{
    ["code"=> $code, "state" => $state] = $_GET;
    print_r($_GET);
    $queryParams = http_build_query([
        "client_id"=> $clientId,
        "client_secret"=> $clientSecret,
        "redirect_uri"=> $redirect_uri,
        "code"=> $code,
        "grant_type"=>"authorization_code"
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $queryParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);

    $response = curl_exec($ch);
    print_r($response);
    curl_close($ch);

    //$response = file_get_contents($url);

    if (!$response) {
        echo $http_response_header;
        return;
    }
    ["access_token" => $token] = json_decode($response, true);

    return $token;
}

$route = $_SERVER["REQUEST_URI"];

switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/oauth_success':
        callback();
        break;
    case '/fb_oauth_success':
        fbcallback();
        break;
    case '/twitch_oauth_success':
        twCallback();
        break;
    case '/discord_oauth_success':
        discordCallback();
        break;
    default:
        http_response_code(404);
}
