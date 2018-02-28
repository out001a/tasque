<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午4:22
 */

require_once __DIR__ . '/bootstrap.php';

$redis = new \Redis();
$redis->pconnect('192.168.33.10', 6379);

$tasques = [];

$task_classes = [
    MyTask1::class,
    MyTask2::class,
];
foreach ($task_classes as $task_class) {
    $tasque = new \Tasque\Tasque('LOAN:' . $task_class, $redis);
    $tasques[] = $tasque;
    for ($i = 0; $i < 10000; $i++) {
        $tasque->enqueue(new $task_class($i, [rand(), time()]));
    }
}
