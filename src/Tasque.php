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

    public static function storage($prefix, $connector) {
        self::$_storage = new Storage('tasque:' . $prefix);
        switch (get_class($connector)) {
            case 'Redis':
                $adapter = 'redis';
                break;
            default:
                throw new \Exception("connector is inadaptable!");
                break;
        }
        self::$_storage->register($connector, $adapter);
    }

    // 添加任务并入队
    public static function enqueue(Task $task) {
        self::$_storage->tag($task)->set($task->id, serialize($task));
        return self::$_storage->push($task->score, $task->id);
    }

    // 从队列和哈希中取出任务
    public static function dequeue(Task $task) {
        $result = self::$_storage->tag($task)->pop(time(), 1);
        if ($result) {
            list($task->id, $task->score) = $result[0];
            $serialized = self::$_storage->get($task->id);
            self::$_storage->del($task->id);
            if ($serialized) {
                $task = unserialize($serialized);
//                $task->perform();
                return true;
            }
            return false;
        }
        return false;
    }

}