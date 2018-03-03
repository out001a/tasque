<?php
/**
 * Created by PhpStorm.
 * User: shanhuanming
 * Date: 2018/3/2
 * Time: 12:31
 */

use PHPUnit\Framework\TestCase;

use Tests\Task\MyTask1;

final class TaskTest extends TestCase
{
    private $_task;

    public function setUp()
    {
        $this->_task = new MyTask1(1, [3,4,5], 2);
    }

    public function testAssert()
    {
        self::assertEquals(1, $this->_task->id);
        self::assertEquals([3,4,5], $this->_task->payload);
        self::assertEquals(2, $this->_task->times);
    }

    public function testPerformNeedRetryException()
    {
        $this->expectException(\Tasque\Task\NeedRetryException::class);
        $this->_task->perform();
    }

    public function testHandle()
    {
        $mockTasque = $this->getMockBuilder(\Tasque\Tasque::class)->setConstructorArgs(['test', 'redis'])->getMock();
        $this->_task->handle($mockTasque);
        self::assertEquals(3, $this->_task->times);
    }

}
