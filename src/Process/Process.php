<?php
/**
 * 处理多进程任务
 *
 * @author shanhuanming shanhuanming@foxmail.com
 * @version 1.0.0
 *
 * Usage:
 *  // 初始化，自定义一些参数
 *  Process::init(消息队列对象, 同时存在的最大子进程数, fork子进程的时间间隔);
 * Process::register('taskCount', function () {
 *      // 返回待处理的任务数（随便写个数也可以）
 *      return 1;
 *  });
 *  Process::register('dispatch', function() {
 *      // 分发待处理的任务列表，需要返回array
 *      return array();
 *  });
 *  Process::register('worker',  function() {
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

use Exception;

class Process {

    protected static $_title;
    protected static $_mq;

    protected static $_maxWorkerNum = 10;    // 同时存在的最大工作进程数
    protected static $_reforkInterval = 5;   // fork工作进程的时间间隔，秒；如果非数字或小于0，则主进程执行一次后立即退出
    protected static $_taskBacklog = 20;     // 当积压的任务数大于此值时，才fork新进程处理
    protected static $_maxWorkerTtl = 600;   // 工作进程的存活时间，如果大于这个时间则在当前任务处理完成后退出，秒

    protected static $_registers = array();
    protected static $_dispatchers = array();
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

    public static function init($title, $mq, $max_worker_num = 10, $refork_interval = 5) {
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
                    exit();
                } else {
                    // 子进程接受到SIGTERM信号时的操作
                    //posix_kill($pid, $signo);
                    //exit();
                    self::$_needExit = true;
                }
                break;
            case SIGCHLD:
                $cpid = pcntl_wait($status);
                if (isset(self::$_dispatchers[$cpid])) {
                    unset(self::$_dispatchers[$cpid]);
                    echo "dispatcher[{$cpid}] exits with {$status}.\n";
                } else {
                    unset(self::$_workers[$cpid]);
                    echo "worker[{$cpid}] exits with {$status}.\n";
                }
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
            if (empty(self::$_dispatchers)) {
                $pid = pcntl_fork();
                if ($pid < 0) {
                    throw new Exception('could not fork dispatcher process!');
                } elseif ($pid > 0) {
                    self::$_dispatchers[$pid] = time();
                    echo "dispatcher[{$pid}] starts\n";
                } else {
                    cli_set_process_title('[dispatcher] ' . self::$_title);
                    while (true) {
                        self::_setTasks(self::_dispatch());
                        usleep(20 * 1000);

                        if (posix_getppid() != self::$_ppid) {
                            // echo "parent prcess [" . self::$_ppid . "] not found!\n";
                            break;
                        }
                        if (self::$_needExit) {
                            break;
                        }
                    }
                    $cpid = getmypid();
                    $count = self::_taskCount();
                    echo date("Y-m-d H:i:s") . ", dispatcher[{$cpid}] finished, {$count} tasks remain.\n";
                    exit();
                }
            }

            foreach (self::$_workers as $pid => $stime) {
                if (time() - $stime > self::$_maxWorkerTtl) {
                    posix_kill($pid, SIGTERM);
                }
            }

            if (self::_trigger()) {
                $pid = pcntl_fork();
                if ($pid < 0) {
                    throw new Exception('could not fork worker process!');
                } elseif ($pid > 0) {
                    self::$_workers[$pid] = time();
                    echo "worker[{$pid}] starts\n";
                } else {
                    cli_set_process_title('[worker] ' . self::$_title);
                    while (true) {
                        $task = self::_getTask();
                        if ($task) {
                            self::handleWorker($task);
                        }

                        if (posix_getppid() != self::$_ppid) {
                            // echo "parent prcess [" . self::$_ppid . "] not found!\n";
                            break;
                        }
                        if (self::$_needExit) {
                            break;
                        }
                    }
                    exit();
                }
            }

            if (!is_numeric(self::$_reforkInterval) || self::$_reforkInterval < 0) {
                $ppid = self::$_ppid;
                echo date("Y-m-d H:i:s") . ", master[{$ppid}] finished.\n";
                exit();
            } else {
                sleep(self::$_reforkInterval);
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

    public static function registerTaskCount($callable) {
        self::register('taskCount', $callable);
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

    protected static function _taskCount($args = array()) {
        return call_user_func_array(self::_getRegisterCallable('taskCount'), $args);
    }

    protected static function _trigger() {
        $worker_count = count(self::$_workers);
        $mq_len = intval(self::$_mq->len());
        return $worker_count < self::$_maxWorkerNum && (($worker_count == 0 && $mq_len > 0) || $mq_len > self::$_taskBacklog);
    }

    protected static function _setTasks($tasks) {
        foreach ($tasks as $task) {
            if ($task) {
                self::$_mq->send($task);
            }
        }
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