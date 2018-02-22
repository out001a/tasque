<?php
/**
 * 处理多进程任务
 *
 * @author shanhuanming shanhuanming@foxmail.com
 * @version 0.9.1
 *
 * Usage:
 *  // 初始化，自定义一些参数
 *  Process::init(消息队列对象, 同时存在的最大子进程数, fork子进程的时间间隔);
 *  Process::register('dispatch', function() {
 *      // 分发待处理的任务列表，需要返回array
 *      return array();
 *  });
 *  Process::register('worker',  function($ppid) {
 *      // 注册work进程的业务逻辑
 *      return do_work();
 *  });
 *  // 执行
 *  Process::handle();
 *
 * 注意：
 *  使用的消息队列需要实现`send`、`receive`、`len`等方法
 */

namespace Tasque\Component;

class Process {

    protected $_queue;
    protected $_maxWorkerNum = 20;    // 同时存在的最大工作进程数
    protected $_reforkInterval = 8;   // fork工作进程的时间间隔，秒；如果非数字或小于0，则主进程执行一次后立即退出
    protected $_taskBacklog = 3;      // 当积压的任务数大于此值时，才fork新进程处理
    protected $_maxWorkerTtl = 1800;  // 工作进程的存活时间，如果大于这个时间则在当前任务处理完成后退出，秒

    protected $_registers = array();
    protected $_workers = array();

    protected $_ppid = 0;
    protected $_needExit = false;

    // TODO 监控master进程是否存在，不存在则启动
    public static function monitor() {

    }

    public function __construct($queue, $max_worker_num = 20, $refork_interval = 8) {
        $this->_ppid = getmypid();
        $this->_queue = $queue;
        $this->_maxWorkerNum = intval($max_worker_num);
        $this->_reforkInterval = intval($refork_interval);
    }

    // TODO 将进程与一个队列绑定，可以使用队列的方法
    public function bind($prefix, $task_name) {

    }

    public function handleSign($signo) {
        $pid = getmypid();
        switch ($signo) {
            case SIGTERM:
                if ($pid == $this->_ppid) {
                    // 不要在这里kill子进程，而是让子进程自己判断父进程是否存在并退出
                    //foreach ($this->_workers as $pid) {
                    //    posix_kill($pid, $signo);
                    //}
                } else {
                    // 子进程接受到SIGTERM信号时的操作
                    posix_kill($pid, $signo);
                }
                exit();
                break;
            case SIGCHLD:
                $cpid = pcntl_wait($status);
                unset($this->_workers[$cpid]);
                echo "worker[{$cpid}] exits with {$status}.\n";
                break;
            default:
                break;
        }
    }

    public function handle() {
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array(__CLASS__, 'handleSign'));
        pcntl_signal(SIGCHLD, array(__CLASS__, 'handleSign'));

        while (true) {
            if ($this->_needExit) {
                echo "I am exiting ...\n";
                return;
            }

            if (count($this->_workers) < $this->_maxWorkerNum) {
                $task_count = 0;    // TODO
                // 0. Prefix和Task类名一起，唯一决定一种任务
                // 1. 把任务注册到process中，有几种任务就要有几个master（dispatcher）进程！
                // 2. 根据任务获取到队列和哈希，以及队列中剩余任务的数量
                // 3. 根据剩余任务数量来决定开启的worker进程的数量
                if ((count($this->_workers) == 0 && $task_count > 0) || $task_count > $this->_taskBacklog) {
                    $pid = pcntl_fork();
                    if ($pid < 0) {
                        throw new \Exception('could not fork process!');
                    } elseif ($pid > 0) {
                        $this->_workers[$pid] = $pid;
                        echo "worker[{$pid}] starts\n";
                    } else {
                        $stime = time();
                        while (true) {
                            if (time() - $stime > $this->_maxWorkerTtl) {
                                break;
                            }
                            $task = $this->_getTask();
                            if ($task) {
                                $this->handleWorker($task);
                            }
                        }
                        exit();
                    }
                }
            }

            if (!is_numeric($this->_reforkInterval) || $this->_reforkInterval < 0) {
                $ppid = $this->_ppid;
                $count = $this->_getTaskCount();
                echo date("Y-m-d H:i:s") . ", proc[{$ppid}] finished, {$count} tasks remain.\n";
                exit();
            } else {
                sleep($this->_reforkInterval);
            }
        }
    }

}