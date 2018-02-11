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

abstract class Tasque {

    private $_storage;

    public function __construct(Storage $storage)
    {
        $this->_storage = $storage;
    }

    // 添加任务并入队
    public function enqueue(Task $task) {
        return $this->_storage->set($task->id, $task->payload)
            && $this->_storage->push($task->score, $task->id);
    }

    // 从队列和哈希中取出任务并执行
    public function dequeue($task_id) {
    }

}