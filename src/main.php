<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/17
 * Time: 下午5:22
 */

// 任务队列设计：
// 1. 队列：优先级队列，存储任务数据并可以按优先级取出
// 2. 生产者：往队列里写入任务
// 3. 消费者：不断从队列里获取任务并执行（父进程根据积压的任务数调整worker进程的数量，worker进程从队列中取出任务并执行）

abstract class Task {
    public $id;
    public $score;
    public $payload;

    public $times = 0;
    public $delays = [];

    final public function __toString()
    {
        return static::class;
    }

    abstract public function perform();
}

class Process {

    protected $_name;
    protected $_queue;
    protected $_workers = [];

    protected $_taskBacklog = 3;      // 当积压的任务数大于此值时，才fork新进程处理

    public function __construct($name, $queue)
    {
        $this->_name = $name;
        $this->_queue = $queue; // MUST implement len(), push()/enqueue(), pop()/dequeue()
    }

    public function handle()
    {
        while (true) {
            $task_count = $this->_queue->len();
            if (($task_count > 0 && count($this->_workers) == 0) || ($task_count > $this->_taskBacklog)) {
                $pid = pcntl_fork();
                if ($pid < 0) {
                    // TODO
                } elseif ($pid > 0) {
                    $this->_workers[$pid] = $pid;
                } else {
                    while (true) {
                        // check parent process alive
                        // dequeue task
                        // perform
                    }
                }
            }
        }
    }
}

class Tasque {
    private $_redis;
    private $_process;

    public function dispatch($task_classes)
    {
        foreach ($task_classes as $task_class) {
            // 每种任务分配一个主进程
            // 每个主进程定时检查任务队列中积压的任务数量，并根据需要启动新的worker进程
            // worker进程要在主进程中注册，并在退出时通知主进程
            // worker进程要检测主进程是否存活，若主进程已退出，则worker进程要在执行完当前的任务后主动退出
            // 需要有脚本定时监控主进程，若不存在则重启
        }
    }

    public function enqueue(Task $task)
    {
        $this->_redis->hSet(self::_dictName($task), $task->id, serialize($task));
        return $this->_redis->zAdd(self::_queueName($task), $task->score, $task->id);
    }

    public function dequeue(Task $task)
    {
        $result = $this->_pop(self::_queueName($task), time(), 1);
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

    private static function _dictName(Task $task)
    {
        return self::_componentName($task, 'dict');
    }

    private static function _queueName(Task $task)
    {
        return self::_componentName($task, 'queue');
    }

    private static function _componentName(Task $task, $component)
    {
        return "{$task}:{$component}";
    }

    private function _pop($name, $score, $limit)
    {
        $lua = <<<LUA
        local queue = KEYS[1]
        local elem = redis.call('zrange', queue, ARGV[1], ARGV[2], 'WITHSCORES')
        if (#elem == 0) then
            return nil
        end
    
        local result = {}
        local time = ARGV[3]
        for i = 2, #elem, 2 do
            if (elem[i] > time) then
                break
            end
            redis.call('zrem', queue, elem[i-1])
            result[i/2] = {elem[i-1], elem[i]}  -- {member, score}
        end
        return result
LUA;

        return $this->_redis->eval($lua, [$name, 0, $limit - 1, $score], 1);
    }
}