<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/3/3
 * Time: 0:22
 */

use \PHPUnit\Framework\TestCase;

use Tests\Task\MyTask2;

final class TasqueTest extends TestCase
{
    private static $_tasque;

    public static function setUpBeforeClass()
    {
        self::$_tasque = new Tasque\Tasque('test', new Tests\Mock\Redis());
    }

    public function testEnqueue()
    {
        for ($i = 0; $i < 3; $i++) {
            self::$_tasque->enqueue(new MyTask2($i + 1, []));
        }
        self::assertEquals(3, self::$_tasque->len());
    }

    /**
     * @depends testEnqueue
     */
    public function testDequeue()
    {
        $tasks = self::$_tasque->dequeue(2);
        for ($i = 0; $i < count($tasks); $i++) {
            $task = unserialize($tasks[$i]);
            self::assertInstanceOf(Tasque\Task\Abstr::class, $task);
            self::assertEquals($i + 1, $task->id);
        }
        self::assertEquals(1, self::$_tasque->len());
    }

    public function testTaskScore()
    {
        self::$_tasque->dequeue(100);   // flush tasque first

        for ($i = 0; $i < 3; $i++) {
            self::$_tasque->enqueue(new MyTask2($i + 1, []));
        }
        $tasks = self::$_tasque->dequeue(1);
        $task = unserialize($tasks[0]);
        self::assertEquals(1, $task->id);

        $task = new MyTask2(4, []);
        $task->score = 123;
        self::$_tasque->enqueue($task);
        $tasks = self::$_tasque->dequeue(1);
        $task = unserialize($tasks[0]);
        self::assertEquals(4, $task->id);
        self::assertEquals(123, $task->score);

        $tasks = self::$_tasque->dequeue(1);
        $task = unserialize($tasks[0]);
        self::assertEquals(2, $task->id);
    }

}