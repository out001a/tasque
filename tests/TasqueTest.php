<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/2/11
 * Time: 17:47
 */

use PHPUnit\Framework\TestCase;

final class TasqueTest extends TestCase
{
    public function testTask() {
        $task = new MyTask(1, 2, [3,4,5]);
        self::assertEquals(1, $task->id);
        self::assertEquals(2, $task->score);
        self::assertEquals([3,4,5], $task->payload);
    }
}