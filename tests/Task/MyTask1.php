<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/3/2
 * Time: 12:21
 */

namespace Tests\Task;

class MyTask1 extends \Tasque\Task\Abstr {

    protected static $_delays = [
        1   => 3,
        2   => 7,
        3   => 15,
    ];

    public function perform()
    {
        throw new \Tasque\Task\NeedRetryException();
    }
}
