<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/10
 * Time: 下午1:04
 */

namespace Tasque\Component\Storage\Adapter\Redis;

use Tasque\Component\Storage\Spec\QueueInterface;

class Queue implements QueueInterface {

    private $_name;
    private $_connector;

    public function __construct($name, $connector)
    {
        $this->_name = $name;
        $this->_connector = $connector;
    }

    public function len()
    {
        return $this->_connector->zCard($this->_name);
    }

    public function push($score, $member)
    {
        return $this->_connector->zAdd($this->_name, $score, $member);
    }

    public function pop($score, $limit)
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

        return $this->_connector->eval($lua, [$this->_name, 0, $limit - 1, $score], 1);
    }

    public function delByMember($member)
    {
        // TODO: Implement delByMember() method.
    }

    public function delByScore($score_min, $score_max)
    {
        // TODO: Implement delByScore() method.
    }
}