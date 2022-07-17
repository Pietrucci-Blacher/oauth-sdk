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

define("GITHUB_CLIENT_ID", "a4dca4e9106e4fecde7f");
define("GITHUB_CLIENT_SECRET", "3cc52ce3b00981b64fce5a5a11f80676076008d0");
define("GITHUB_TOKEN_URL", "https://github.com/login/oauth/access_token");
define("GITHUB_API_URL", "https://api.github.com/user");
define("GITHUB_REDIRECT_URL", "https://localhost/github_oauth_success");




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

    $githubQueryParams = http_build_query([
        "state"=>bin2hex(random_bytes(16)),
        "client_id"=> GITHUB_CLIENT_ID,
        "scope"=>"user",
        "redirect_uri"=> GITHUB_REDIRECT_URL,
    ]);

    echo "<a href=\"http://localhost:8080/auth?{$queryParams}\">Login with Oauth-Server</a><br>";
    echo "<a href=\"https://www.facebook.com/v13.0/dialog/oauth?{$fbQueryParams}\">Login with Facebook</a><br>";
    echo "<a href=\"https://discord.com/api/oauth2/authorize?{$discordQueryParams}\">Login with Discord</a><br>";
    echo "<a href=\"https://id.twitch.tv/oauth2/authorize?{$twitchQueryParams}\">Login with Twitch</a><br>";
    echo "<a href=\"https://github.com/login/oauth/authorize?{$githubQueryParams}\">Login with Github</a><br>";

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
}

function app_callback($app)
{
    switch($app) {
        case "fb":
            $token = getFbToken(FB_TOKEN_URL, FB_CLIENT_ID, FB_CLIENT_SECRET);
            $apiURL = FB_API_URL;
            $headers = [
                "Authorization: Bearer $token",
            ];
            break;
        case "discord":
            $token = getToken(DISCORD_TOKEN_URL, DISCORD_CLIENT_ID, DISCORD_CLIENT_SECRET, DISCORD_REDIRECT_URL);
            $apiURL = DISCORD_API_URL;
            $headers = [
                "Authorization: Bearer $token",
            ];
            break;
        case "twitch":
            $token = getToken(TWITCH_TOKEN_URL, TWITCH_CLIENT_ID, TWITCH_CLIENT_SECRET, TWITCH_REDIRECT_URL);
            $apiURL = TWITCH_API_URL;
            $headers = [
                "Authorization: Bearer $token",
                "Client-ID: " . TWITCH_CLIENT_ID
            ];
            break;
        case "github":
            $token = getToken(GITHUB_TOKEN_URL, GITHUB_CLIENT_ID, GITHUB_CLIENT_SECRET, GITHUB_REDIRECT_URL);
            $apiURL = GITHUB_API_URL;
            $headers = [
                "Authorization: token $token",
                "User-Agent: benjaminli7"
            ];
            break;
        default:
            return;
    }

    $user = getUser($apiURL, $headers);
    switch($apiURL) {
        case FB_API_URL:
            echo "Hello {$user['last_name']} {$user['first_name']}";
            break;
        case DISCORD_API_URL:
            echo "Hello {$user['username']}";
            break;
        case TWITCH_API_URL:
            echo "Hello {$user['data'][0]['login']}";
            break;
        case GITHUB_API_URL:
            echo "Hello {$user['login']}";
            break;
        default:
            return;
    }
    
}

function getUser($apiURL, $headers) 
{
    $context = stream_context_create([
        "http"=>[
            "header"=>$headers,
        ]
    ]);

    $response = file_get_contents($apiURL, false, $context);
    if (!$response) {
        return;
    }

    return json_decode($response, true);
}

// discord, twitch, 
function getToken($baseUrl, $clientId, $clientSecret, $redirect) {
    ["code"=> $code, "state" => $state] = $_GET;

    $postData = http_build_query(
        array(
            "client_id"=> $clientId,
            "client_secret"=> $clientSecret,
            "redirect_uri"=> $redirect,
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
        return;
    }

    if($redirect == GITHUB_REDIRECT_URL) {
        parse_str( $response, $output );
        $result = json_encode($output);
        ["access_token" => $token] = json_decode($result, true);
        return $token;
    }

    ["access_token" => $token] = json_decode($response, true);
    return $token;
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
    case '/github_oauth_success':
        app_callback("github");
        break;
    default:
        http_response_code(404);
}