<?php
namespace SDK;

class AutoLoader
{
    public static function init(): void
    {
        spl_autoload_register(function ($class) {
            $class = str_ireplace("SDK\\", "", $class);
            if (file_exists("class/" . $class . ".class.php")) {
                include "class/" . $class . ".class.php";
            }
        });
    }}