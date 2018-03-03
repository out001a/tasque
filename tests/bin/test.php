<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午4:22
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$redis = new \Redis();
$redis->pconnect('192.168.33.10', 6379);

$tasques = [];
foreach ($task_classes as $task_class) {
    $tasque = new \Tasque\Tasque('LOAN:' . $task_class, $redis);
    $tasques[] = $tasque;
    for ($i = 0; $i < 100000; $i++) {
        $tasque->enqueue(new $task_class($i, [rand(), time()]));
    }
}
