<?php
/**
 * 处理多进程任务
 */

namespace Tasque;

class Process {

    protected $_mq;
    protected $_maxWorkerNum = 10;    // 同时存在的最大工作进程数
    protected $_reforkInterval = 3;   // fork工作进程的时间间隔，秒；如果非数字或小于0，则主进程执行一次后立即退出
    protected $_taskBacklog = 3;      // 当积压的任务数大于此值时，才fork新进程处理
    protected $_maxWorkerTtl = 900;   // 工作进程的存活时间，如果大于这个时间则在当前任务处理完成后退出，秒

    protected $_registers = array();
    protected $_workers = array();

    protected $_ppid = 0;

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

    public function __construct($mq, $max_worker_num = 10, $refork_interval = 8) {
        $this->_mq = $mq;
        $this->_maxWorkerNum = intval($max_worker_num);
        $this->_reforkInterval = intval($refork_interval);
        $this->_ppid = getmypid();
        cli_set_process_title('[master] ' . $this->_mq->name());
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
            if (count($this->_workers) < $this->_maxWorkerNum) {
                $task_count = $this->_getTaskCount();
                if ((count($this->_workers) == 0 && $task_count > 0) || $task_count > $this->_taskBacklog) {
                    $pid = pcntl_fork();
                    if ($pid < 0) {
                        throw new \Exception('could not fork process!');
                    } elseif ($pid > 0) {
                        $this->_workers[$pid] = $pid;
                        echo "worker[{$pid}] starts\n";
                    } else {
                        cli_set_process_title('[worker] ' . $this->_mq->name());
                        $stime = time();
                        while (true) {
                            if (time() - $stime > $this->_maxWorkerTtl) {
                                break;
                            }
//                            if (posix_getppid() != $this->_ppid) {
//                                echo "parent prcess [$this->_ppid] not found!\n";
//                                break;
//                            }
                            $task = $this->_mq->dequeue();
                            if ($task) {
                                $task->perform();
//                                $this->handleWorker($task);
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

    public function handleWorker($task) {
        return $this->_worker(array($task));
    }

    public function register($type, $callable) {
        $method = 'register' . ucfirst($type);
        if ($type && method_exists(__CLASS__, $method) && is_callable($callable)) {
            $this->_registers[$type] = $callable;
            return true;
        }
        throw new \Exception('bad type or callable!');
    }

    public function registerWorker($callable) {
        $this->register('worker', $callable);
    }

    protected function _getRegisterCallable($type) {
        if (isset($this->_registers[$type]) && is_callable($this->_registers[$type])) {
            return $this->_registers[$type];
        }
        throw new \Exception("'{$type}' method not registered!");
    }

    protected function _worker($args = array()) {
        $callable = self::_getRegisterCallable('worker');
        return call_user_func_array($callable, $args);
    }

    protected function _getTaskCount() {
        return $this->_mq->len(time() + 60);
    }

}