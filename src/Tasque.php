<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/11
 * Time: 12:11
 */

namespace Tasque;

use Tasque\Component\Storage;
use Tasque\Component\Task;

class Tasque {

    private static $_storage;

    public static function setStorage($prefix) {
        self::$_storage = new Storage($prefix);
    }

    // 添加任务并入队
    public function enqueue(Task $task) {
        return self::$_storage->tag($task)->set($task->id, $task->payload)
            && self::$_storage->push($task->score, $task->id);
    }

    // 从队列和哈希中取出任务并执行
    public function dequeue(Task $task) {
        // TODO
    }

}