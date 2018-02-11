<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午4:22
 */

require_once '../vendor/autoload.php';

use \Tasque\Component\Storage\Adapter;

//require_once './Task.php';
//$t = new Task(null);
//var_dump($t);
//
//exit();

$redis = new \Redis();
$redis->pconnect('192.168.33.10', 6379);

$storage = new \Tasque\Component\Storage('LOAN', 'test');
$storage->register($redis, 'redis');

$storage->set('123', json_encode(['a' => 1, 'c' => 2]));
var_dump($storage->get('123'));

$storage->push(1, 3);

var_dump($storage->pop(2, 1));