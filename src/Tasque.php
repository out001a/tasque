<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/22
 * Time: 11:48
 */

namespace Tasque;

class Tasque {

    private $_name;
    private $_queue;
    private $_dict;

    private $_redis;

    public function __construct($name, $redis)
    {
        $this->init($name, $redis);
    }

    public function init($name, $redis)
    {
        $this->_name = 'tasque:' . $name;
        $this->_queue = $this->_name . ':queue';
        $this->_dict = $this->_name . ':dict';

        $this->_redis = $redis;
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

    public function dequeue($limit = 50)
    {
        $tasks = [];
        $result = $this->_pop($this->_redis, $this->_queue, time(), $limit);
        if ($result) {
            foreach ($result as $val) {
                list($task_id,) = $val;
                $task = $this->_redis->hGet($this->_dict, $task_id);
                if ($task) {
                    $tasks[] = $task;
                }
            }
        }
        return $tasks;
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

        static $sha = '';
        if (!$sha) {
            $sha = $redis->script('load', $lua);
        }

        //return $redis->eval($lua, [$queue, 0, $limit - 1, $score], 1);
        return $redis->evalSha($sha, [$queue, 0, $limit - 1, $score], 1);
    }
}