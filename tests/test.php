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

$task_classes = [MyTask1::class, MyTask2::class];
foreach ($task_classes as $task_class) {
    $tasque = new \Tasque\Tasque();
    $tasque->init($redis, 'LOAN:' . $task_class);
    $tasques[] = $tasque;
    $tasque->enqueue(new $task_class(rand(1,1000), time(), [rand(), time()]));
}

exit();

foreach ($tasques as $tasque) {
//    $task = $tasque->dequeue();
//    if ($task) {
//        $task->perform();
//    }
    $pid = pcntl_fork();
    if ($pid == 0) {
        (new \Tasque\Process($tasque))->handle();
    } else {
        echo "\n\nforked {$pid}\n\n";
    }
}