<?php

function register() {
    echo "register";
}

function auth() {
    echo "auth";
}


$route = $_SERVER['REQUEST_URI'];
switch(strtok($route, "?")) {
    case '/register':
        register();
        break;
    case '/auth':
        auth();
        break;
    default:
        echo '404';
        break;
}