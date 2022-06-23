<?php

define("CLIENT_ID", '67dc2be521bec2ff862d3ab057de216b');
define("CLIENT_SECRET", '04054cf433eeb3976252c81b6d657fda');

define("FB_CLIENT_ID", '350564083472357');
define("FB_CLIENT_SECRET", '33061052c98835fbcbfee7009dd9862c');
define("FB_TOKEN_URL", "https://graph.facebook.com/v13.0/oauth/access_token");
define("FB_API_URL", "https://graph.facebook.com/v13.0/me?fields=last_name,first_name,email");
define("FB_REDIRECT_URL", "https://localhost/fb_oauth_success");

define("DISCORD_CLIENT_ID", "989090035972317264");
define("DISCORD_CLIENT_SECRET", "8T4sYLQGBtcWFlIvEDlQBc30mKpcOTII");
define("DISCORD_TOKEN_URL", "https://discordapp.com/api/v6/oauth2/token");
define("DISCORD_API_URL", "https://discord.com/api/users/@me");
define("DISCORD_REDIRECT_URL", "https://localhost/discord_oauth_success");


define("TWITCH_CLIENT_ID", "tviv1pom889jjrvfcrq5bzc3km5qed");
define("TWITCH_CLIENT_SECRET", "4vkcw6rix7dgylghlpgyt1dqmp346i");
define("TWITCH_TOKEN_URL", "https://id.twitch.tv/oauth2/token");
define("TWITCH_API_URL", "https://api.twitch.tv/helix/users");
define("TWITCH_REDIRECT_URL", "https://localhost/twitch_oauth_success");


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
        "redirect_uri"=> FB_REDIRECT_URL,
    ]);

    $discordQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> DISCORD_CLIENT_ID,
        "scope"=>"identify",
        "response_type" => "code",
        "redirect_uri"=> DISCORD_REDIRECT_URL,
    ]);

    $twitchQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> TWITCH_CLIENT_ID,
        "scope"=>"user:read:email",
        "response_type" => "code",
        "redirect_uri"=> TWITCH_REDIRECT_URL,
    ]);

    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Login with Oauth-Server</a><br>";
    echo "<a href=\"https://www.facebook.com/v13.0/dialog/oauth?{$fbQueryParams}\">Login with Facebook</a><br>";
    echo "<a href=\"https://discord.com/api/oauth2/authorize?{$discordQueryParams}\">Login with Discord</a><br>";
    echo "<a href=\"https://id.twitch.tv/oauth2/authorize?{$twitchQueryParams}\">Login with Twitch</a><br>";

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

function app_callback($app)
{
    switch($app) {
        case "fb":
            $token = getFbToken(FB_TOKEN_URL, FB_CLIENT_ID, FB_CLIENT_SECRET);
            $apiURL = FB_API_URL;
            break;
        case "discord":
            $token = getDiscordToken(DISCORD_TOKEN_URL, DISCORD_CLIENT_ID, DISCORD_CLIENT_SECRET);
            $apiURL = DISCORD_API_URL;
            break;
        case "twitch":
            $token = getTwitchToken(TWITCH_TOKEN_URL, TWITCH_CLIENT_ID, TWITCH_CLIENT_SECRET);
            $apiURL = TWITCH_API_URL;
            break;
        default:
            return;
    }
    $user = getUser($token, $apiURL);
    var_dump($user);
}

function getUser($token, $apiURL) 
{
    $context = stream_context_create([
        "http"=>[
            "header"=>"Authorization: Bearer {$token}"
        ]
    ]);
    $response = file_get_contents($apiURL, false, $context);
    if (!$response) {
        var_dump($http_response_header);
        return;
    }
    return json_decode($response, true);
}

function getFbToken($baseUrl, $clientId, $clientSecret)
{
    ["code"=> $code, "state" => $state] = $_GET;
    $queryParams = http_build_query([
        "client_id"=> $clientId,
        "client_secret"=> $clientSecret,
        "redirect_uri"=> FB_REDIRECT_URL,
        "code"=> $code,
        "grant_type"=>"authorization_code",
    ]);

    $url = $baseUrl . "?{$queryParams}";
    $response = file_get_contents($url);

    if (!$response) {
        var_dump($http_response_header);
        return;
    }
    ["access_token" => $token] = json_decode($response, true);

    return $token;
}

function getDiscordToken($baseUrl, $clientId, $clientSecret)
{
    ["code"=> $code, "state" => $state] = $_GET;

    $postData = http_build_query(
        array(
            "client_id"=> $clientId,
            "client_secret"=> $clientSecret,
            "redirect_uri"=> DISCORD_REDIRECT_URL,
            "code"=> $code,
            "grant_type"=>"authorization_code",
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
        )
    );

    $context  = stream_context_create($opts);
    $response = file_get_contents($baseUrl, false, $context);

    if (!$response) {
        var_dump($http_response_header);
        return;
    }

    ["access_token" => $token] = json_decode($response, true);

    return $token;
}

function getTwitchToken($baseUrl, $clientId, $clientSecret)
{
    ["code"=> $code, "state" => $state] = $_GET;
    $postData = http_build_query(
        array(
            "client_id"=> $clientId,
            "client_secret"=> $clientSecret,
            "redirect_uri"=> TWITCH_REDIRECT_URL,
            "code"=> $code,
            "grant_type"=>"authorization_code",
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
        )
    );


    $context  = stream_context_create($opts);
    $response = file_get_contents($baseUrl, false, $context);

    if (!$response) {
        
        var_dump($http_response_header);
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
        app_callback("fb");
        break;
    case '/discord_oauth_success':
        app_callback("discord");
        break;
    case '/twitch_oauth_success':
        app_callback("twitch");
        break;
    default:
        http_response_code(404);
}
