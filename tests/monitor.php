<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/22
 * Time: 17:04
 */

require_once __DIR__ . '/bootstrap.php';

use \Tasque\Process\MsgQueue;
use \Tasque\Process\Process;

$task_classes = [MyTask1::class, MyTask2::class];

foreach ($task_classes as $task_class) {
    $name = 'LOAN:' . $task_class;
    Process::monitor($name, function() use ($name) {
        $redis = new \Redis();
        $redis->pconnect('192.168.33.10', 6379);
        $tasque = new \Tasque\Tasque($name, $redis);

        // 消息队列对象
        $mq = new MsgQueue(array('path' => "/tmp/{$name}", 'proj' => $name));
        // 初始化，自定义一些参数
        Process::init($tasque->name(), $mq, 8, 300);
        Process::register('taskCount', function () use ($tasque) {
            return $tasque->len(time() + 60);
        });
        Process::register('dispatch', function() use ($tasque) {
            return $tasque->dequeue();
        });
        Process::register('worker',  function($task) {
            $task = unserialize($task);
            if ($task) {
                $task->perform();
            }
        });
        // 执行
        Process::handle();
    });
}
