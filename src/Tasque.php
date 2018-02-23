<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/22
 * Time: 11:48
 */

namespace Tasque;

class Tasque {
    private $_redis;

    private $_name;
    private $_queue;
    private $_dict;

    public function __construct($redis, $name)
    {
        $this->init($redis, $name);
    }

    public function init($redis, $name)
    {
        $this->_redis = $redis;

        $this->_name = 'tasque:' . $name;
        $this->_queue = $this->_name . ':queue';
        $this->_dict = $this->_name . ':dict';
    }

    public function name() {
        return $this->_name;
    }

    public function len($score = null)
    {
        if (is_null($score)) {
            return $this->_redis->zCard($this->_queue);
        } else {
            return $this->_redis->zCount($this->_queue, 0, $score);
        }
    }

    public function enqueue(Task $task)
    {
        $this->_redis->hSet($this->_dict, $task->id, serialize($task));
        return $this->_redis->zAdd($this->_queue, $task->score, $task->id);
    }

    public function dequeue()
    {
        $result = $this->_pop($this->_redis, $this->_queue, time(), 1);
        if ($result) {
            list($task_id,) = $result[0];
            $task = $this->_redis->hGet($this->_dict, $task_id);
            if ($task) {
                return unserialize($task);
            }
        }
        return false;
    }

    public function remove($member)
    {
        return $this->_redis->zRem($this->_queue, $member)
            && $this->_redis->hDel($this->_dict, $member);
    }

    private function _pop($redis, $queue, $score, $limit) {
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

        return $redis->eval($lua, [$queue, 0, $limit - 1, $score], 1);
    }
}