<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/16
 * Time: 下午2:00
 */

// ./vendor/bin/phpunit --bootstrap ./tests/bootstrap.php tests/

require_once dirname(__DIR__) . "/vendor/autoload.php";

spl_autoload_register(function($class) {
    if (file_exists(__DIR__ . "/$class.php")) {
        require_once __DIR__ . "/$class.php";
    }
});