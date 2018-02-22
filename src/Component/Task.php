<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/11
 * Time: 15:57
 */

namespace Tasque\Component;

abstract class Task {

    protected $_id;         // 任务id
    protected $_score;      // 任务权重
    protected $_payload;    // 任务负载

    protected $_times  = 0; // 任务重试次数
    protected $_delays = [  // 任务重试的延迟时间（如果执行失败，则按计划重新入队并在指定的延迟时间后再次执行，直到成功或者所有延迟计划都执行完毕为止）
        // times => delay seconds
        0   => 0,   // 表示第0次在延迟0秒后执行
    ];

    public function __construct($id = null, $score = 0, array $payload = [])
    {
        if (!is_null($id)) {
            $this->__set('id', $id);
            $this->__set('score', $score);
            $this->__set('payload', $payload);
        }
    }

    final public function __toString()
    {
        return static::class;
    }

    final public function __get($name)
    {
        $prop = '_' . lcfirst($name);
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
        return null;
    }

    final public function __set($name, $value)
    {
        $prop = '_' . lcfirst($name);
        if (property_exists($this, $prop)) {
            $this->$prop = $value;
        }
    }

    final public function incrTimes() {
        return ++$this->_times;
    }

    final public function getDelay($times = null) {
        if (is_null($times)) {
            $times = $this->_times;
        }
        return isset($this->_delays[$times]) ? $this->_delays[$times] : false;
    }

    abstract function perform();
}