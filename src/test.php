<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午4:22
 */

use \Tasque\Component\Storage\Adapter;

spl_autoload_register(function ($class) {
    $prefix = 'Tasque';
    if (substr($class, 0, 6) == $prefix) {
        $file = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, $prefix)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

$redis = new \Redis();
$redis->pconnect('127.0.0.1', 6379);

$storage = new \Tasque\Component\Storage('LOAN', 'test');
$storage->register(Adapter\Redis\Dict::class, $redis);
$storage->register(Adapter\Redis\Queue::class, $redis);

$storage->set('123', json_encode(['a' => 1, 'c' => 2]));
var_dump($storage->get('123'));

$storage->enqueue(3, 3);

var_dump($storage->dequeue(2, 1));