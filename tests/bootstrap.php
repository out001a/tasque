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

    protected static $_delays = [
        1   => 3,
        2   => 7,
        3   => 15,
    ];

    public function perform()
    {
        $s = date('Y-m-d H:i:s') . "\t" . __CLASS__ . ": {$this->id}\t{$this->times}\t{$this->score}\n";
        file_put_contents('/tmp/tasque.log', $s, FILE_APPEND | LOCK_EX);
        return rand(0, 99) < 50 ? true : false;
    }
}

class MyTask2 extends \Tasque\Task {

    protected static $_delays = [
        1   => 2,
        2   => 3,
        3   => 4,
    ];

    public function perform()
    {
        $s = date('Y-m-d H:i:s') . "\t" .  __CLASS__ . ": {$this->id}\t{$this->times}\t{$this->score}\n";
        file_put_contents('/tmp/tasque.log', $s, FILE_APPEND | LOCK_EX);
        return rand(0, 99) < 50 ? true : false;
    }
}