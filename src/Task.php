<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/22
 * Time: 12:10
 */

namespace Tasque;

abstract class Task {
    public $id;
    public $score;
    public $payload = [];
    public $times = 0;
    public $delays = [];

    public function __construct($id, $score, array $payload)
    {
        $this->id = $id;
        $this->score = $score;
        $this->payload = $payload;
    }

    final public function __toString()
    {
        return static::class;
    }

    abstract public function perform();
}