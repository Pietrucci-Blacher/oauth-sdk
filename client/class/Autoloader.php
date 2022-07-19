<?php

namespace SDK;

class Autoloader
{
    public static function init(): void
    {
        spl_autoload_register(function ($class) {
            $class = str_ireplace("SDK\\", "", $class);
            if (file_exists("class/" . $class . ".php")) {
                include "class/" . $class . ".php";
            }
        });
    }
}
