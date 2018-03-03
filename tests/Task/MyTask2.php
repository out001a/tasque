<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/3/2
 * Time: 12:21
 */

namespace Tests\Task;

class MyTask2 extends \Tasque\Task\Abstr {

    protected static $_delays = [
        1   => 2,
        2   => 3,
        3   => 4,
    ];

    public function perform()
    {
        $s = date('Y-m-d H:i:s') . "\t" .  __CLASS__ . ": {$this->id}\t{$this->times}\t{$this->score}\n";
        file_put_contents('/tmp/tasque.log', $s, FILE_APPEND | LOCK_EX);
        if (rand(0, 99) < 50) {
            throw new \Tasque\Task\NeedRetryException();
        }
        return true;
    }
}
