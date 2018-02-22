<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/22
 * Time: 17:04
 */

require_once __DIR__ . '/bootstrap.php';

$redis = new \Redis();
$redis->pconnect('192.168.33.10', 6379);

$task_classes = [MyTask1::class, MyTask2::class];

foreach ($task_classes as $task_class) {
    $name = 'LOAN:' . $task_class;
    $tasque = new \Tasque\Tasque();
    $tasque->init($redis, $name);
    \Tasque\Process::monitor($name, $tasque);
}
