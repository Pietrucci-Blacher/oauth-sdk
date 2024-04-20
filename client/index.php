<?php

use SDK\Autoloader;
use SDK\sdkInit;

require 'class/Autoloader.php';

Autoloader::init();


$SDK = new sdkInit();

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        $SDK->login();
        break;
    case '/oauth_success':
        $SDK->callback();
        break;
    case '/fb_oauth_success':
        $SDK->app_callback("fb");
        break;
    case '/discord_oauth_success':
        $SDK->app_callback("discord");
        break;
    case '/twitch_oauth_success':
        $SDK->app_callback("twitch");
        break;
    case '/github_oauth_success':
        $SDK->app_callback("github");
        break;
    default:
        http_response_code(404);
}
