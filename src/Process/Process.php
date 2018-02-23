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

namespace Tasque\Process;

class Process {

    protected static $_title;
    protected static $_mq;

    protected static $_maxWorkerNum = 15;    // 同时存在的最大工作进程数
    protected static $_reforkInterval = 500; // fork工作进程的时间间隔，毫秒；如果非数字或小于0，则主进程执行一次后立即退出
    protected static $_taskBacklog = 10;     // 当积压的任务数大于此值时，才fork新进程处理
    protected static $_maxWorkerTtl = 1800;  // 工作进程的存活时间，如果大于这个时间则在当前任务处理完成后退出，秒

    protected static $_registers = array();
    protected static $_workers = array();

    protected static $_ppid = 0;
    protected static $_needExit = false;

    /**
     * 监控master进程是否存在，不存在则启动
     * @param $name
     * @param callable $callback
     */
    public static function monitor($name, callable $callback) {
        $cmd = "ps aux | fgrep master | fgrep '{$name}' | fgrep -v fgrep | wc -l";
        if (shell_exec($cmd) == 0) {
            $pid = pcntl_fork();
            if ($pid == 0) {
                call_user_func_array($callback, []);
            } else {
                echo "forked $name {$pid}\n";
            }
        }
    }

    public static function init($title, $mq, $max_worker_num = 15, $refork_interval = 500) {
        self::$_title = $title;
        self::$_mq = $mq;
        self::$_maxWorkerNum = intval($max_worker_num);
        self::$_reforkInterval = intval($refork_interval);
        self::$_ppid = getmypid();
    }

    public static function handleSign($signo) {
        $pid = getmypid();
        switch ($signo) {
            case SIGTERM:
                if ($pid == self::$_ppid) {
                    // 不要在这里kill子进程，而是让子进程自己判断父进程是否存在并退出
                    //foreach (self::$_workers as $pid) {
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
                unset(self::$_workers[$cpid]);
                echo "worker[{$cpid}] exits with {$status}.\n";
                break;
            default:
                break;
        }
    }

    public static function handle() {
        if (!self::_getRegisterCallable('worker')) {
            return false;
        }
        cli_set_process_title('[master] ' . self::$_title);

        declare(ticks = 1);
        pcntl_signal(SIGTERM, array(__CLASS__, 'handleSign'));
        pcntl_signal(SIGCHLD, array(__CLASS__, 'handleSign'));

        while (true) {
            if (self::$_needExit) {
                echo "I am exiting ...\n";
                return true;
            }

            $task_count = self::_setTasks(self::_dispatch());
            if (self::_trigger($task_count)) {
                $pid = pcntl_fork();
                if ($pid < 0) {
                    throw new Exception('could not fork process!');
                } elseif ($pid > 0) {
                    self::$_workers[$pid] = $pid;
                    echo "worker[{$pid}] starts\n";
                } else {
                    cli_set_process_title('[worker] ' . self::$_title);
                    $stime = time();
                    while (true) {
                        if (time() - $stime > self::$_maxWorkerTtl) {
                            break;
                        }
//                            if (posix_getppid() != self::$_ppid) {
//                                echo "parent prcess [" . self::$_ppid . "] not found!\n";
//                                break;
//                            }
                        $task = self::_getTask();
                        if ($task) {
                            self::handleWorker($task);
                        }
                    }
                    exit();
                }
            }

            if (!is_numeric(self::$_reforkInterval) || self::$_reforkInterval < 0) {
                $ppid = self::$_ppid;
                $count = self::_getTaskCount();
                echo date("Y-m-d H:i:s") . ", proc[{$ppid}] finished, {$count} tasks remain.\n";
                exit();
            } else {
                usleep(self::$_reforkInterval * 1000);
            }
        }
    }

    public static function handleWorker($task) {
        return self::_worker(array($task));
    }

    public static function register($type, $callable) {
        $method = 'register' . ucfirst($type);
        if ($type && method_exists(__CLASS__, $method) && is_callable($callable)) {
            self::$_registers[$type] = $callable;
            return true;
        }
        throw new Exception('bad type or callable!');
    }

    public static function registerWorker($callable) {
        self::register('worker', $callable);
    }

    public static function registerDispatch($callable) {
        self::register('dispatch', $callable);
    }

    protected static function _getRegisterCallable($type) {
        if (isset(self::$_registers[$type]) && is_callable(self::$_registers[$type])) {
            return self::$_registers[$type];
        }
        throw new Exception("'{$type}' method not registered!");
    }

    protected static function _worker($args = array()) {
        $callable = self::_getRegisterCallable('worker');
        return call_user_func_array($callable, $args);
    }

    protected static function _dispatch($args = array()) {
        $callable = self::_getRegisterCallable('dispatch');
        return call_user_func_array($callable, $args);
    }

    protected static function _trigger($task_count) {
        return count(self::$_workers) < self::$_maxWorkerNum
            && ((count(self::$_workers) == 0 && $task_count > 0) || $task_count > self::$_taskBacklog);
    }

    protected static function _getTaskCount() {
        return intval(self::$_mq->len());
    }

    protected static function _setTasks($tasks) {
        foreach ($tasks as $task) {
            if ($task) {
                self::$_mq->send($task);
            }
        }
        return self::_getTaskCount();
    }

    protected static function _getTask() {
        try {
            return self::$_mq->receive();
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            return null;
        }
    }

}