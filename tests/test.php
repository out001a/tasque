<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午4:22
 */

require_once __DIR__ . '/bootstrap.php';

use \Tasque\Component\Storage\Adapter;

//require_once './Task.php';
//$t = new Task(null);
//var_dump($t);
//
//exit();

$redis = new \Redis();
$redis->pconnect('127.0.0.1', 6379);

/*
$storage = new \Tasque\Component\Storage('LOAN');
$storage->tag('test---')->register($redis, 'redis');

$storage->set('123', json_encode(['a' => 1, 'c' => 2]));
var_dump($storage->get('123'));

$storage->push(1, 3333);
$storage->push(2, 2222);

var_dump($storage->pop(2, 1));
*/

\Tasque\Tasque::storage('Prefix', $redis);

var_dump(\Tasque\Tasque::enqueue(new MyTask(1234, time(), [456, time()])));
var_dump(\Tasque\Tasque::enqueue(new MyTask1(1234, time(), [456, time()])));

//$task = new MyTask();
//if (\Tasque\Tasque::dequeue($task)) {
//    $task->perform();
//}
//
//$task = new MyTask1();
//if (\Tasque\Tasque::dequeue($task)) {
//    $task->perform();
//}
//
//$task = new MyTask();
//if (\Tasque\Tasque::dequeue($task)) {
//    $task->perform();
//}

$task_classes = [MyTask::class, MyTask1::class];
foreach ($task_classes as $task_class) {
    $task = new $task_class();
    if (\Tasque\Tasque::dequeue($task)) {
        $task->perform();
    }
}
