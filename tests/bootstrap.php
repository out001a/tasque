<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/16
 * Time: 下午2:00
 */

// ./vendor/bin/phpunit --bootstrap ./tests/bootstrap.php tests/

require_once dirname(__DIR__) . "/vendor/autoload.php";

ini_set('default_socket_timeout', -1);

spl_autoload_register(function($class) {
    if (file_exists(__DIR__ . "/$class.php")) {
        require_once __DIR__ . "/$class.php";
    }
});

class MyTask1 extends \Tasque\Task {

    public function perform()
    {
        var_dump(__CLASS__ . ": {$this->id}");
    }
}

class MyTask2 extends \Tasque\Task {

    public function perform()
    {
        var_dump(__CLASS__ . ": {$this->id}");
    }
}