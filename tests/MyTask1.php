<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/17
 * Time: 下午8:56
 */

class MyTask1 extends \Tasque\Component\Task {

    public function perform()
    {
        var_dump(__CLASS__ . ': ' . $this->id);
    }
}