<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/22
 * Time: 12:10
 */

namespace Tasque;

abstract class Task {
    public $id;              // 任务id
    public $payload = [];   // 任务负载
    public $times = 0;      // 任务执行次数
    public $score;          // 任务权重

    protected static $_delays = [   // 任务重试的延迟时间（如果执行失败，则按计划重新入队并在指定的延迟时间后再次执行，直到成功或者所有延迟计划都执行完毕为止）
        // times    => delay seconds
        // 0    => 0,
        // 1    => 15,
    ];

    abstract public function perform();

    final public function handle(Tasque $tasque)
    {
        try {
            if (!$this->perform()) {
                throw new \Exception('need retry');
            }
        } catch (\Exception $e) {
            $this->times++;
            if (isset(static::$_delays[$this->times])) {
                // $this->score += static::$_delays[$this->times];
                $this->score = time() + static::$_delays[$this->times];
                $tasque->enqueue($this);
            }
        }
    }

    public function __construct($id, array $payload = [], $times = 0)
    {
        $this->id = $id;
        $this->payload = $payload;
        $this->times = $times;
        $this->score = time() + (isset(static::$_delays[$this->times]) ? static::$_delays[$this->times] : 0);
    }

    final public function __toString()
    {
        return static::class;
    }

}