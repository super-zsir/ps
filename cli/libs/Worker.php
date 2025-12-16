<?php

namespace Imee\Cli\Libs;

use Imee\Comp\Common\Beanstalkd\Worker as QueueWorker;
use Phalcon\Di;


$_wait_exit = false;

declare(ticks=1);

pcntl_signal(SIGTERM, function ($signo) {
    global $_wait_exit;
    echo date('Y-m-d H:i:s') . " => sig_handler {$signo}\n";
    if ($signo == SIGTERM) {
        //wait to exit
        $_wait_exit = true;
    }
});

//单进程模式
class Worker
{
    protected $_queue;
    protected $_name;
    protected $_debug = false;
    private $_callback;
    private $_messageErrorCallback;

    private $_className;

    private $_delayArray = array(5, 5, 10, 30, 30, 60, 300);

    private $_dbs = array('db');

    public function __construct($name, $callback, array $delayArray = null, $debug = true, $autoInit = true)
    {
        $this->_queue = new QueueWorker();
        $this->_name = $name;
        $this->_callback = $callback;
        $this->_debug = $debug;
        $this->_className = get_class($callback[0]);
        cli_set_process_title("php-cli-$name");
        if (!is_null($delayArray)) $this->_delayArray = $delayArray;
        if ($autoInit) $this->init();
    }

    public function server()
    {
        return $this->_queue;
    }

    public function resetDbs($dbs = array("db"))
    {
        $this->_dbs = $dbs;
    }

    public function init()
    {
        while (!$this->_queue->connect()) {
            echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}][{$this->_name}]connect to Beanstalkd error " . $this->_queue->error() . "\n";
            sleep(3);
        }
        echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}][{$this->_name}]connect to Beanstalkd Success\n";
        $this->_queue->watch($this->_name);
        $this->_queue->choose($this->_name);
        $this->_queue->ignore('default');
        $this->run();
    }

    public function setMessageError($callback)
    {
        $this->_messageErrorCallback = $callback;
    }

    protected function isMysqlError($e)
    {
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, 'MySQL server has gone away') !== false
            || strpos($errorMessage, 'Lost connection to MySQL server during query') !== false
            || strpos($errorMessage, 'Can\'t connect to MySQL server') !== false
            || strpos($errorMessage, 'Trying to call method exec on a non-object') !== false
        ) {
            return true;
        }
        return false;
    }

    private function run()
    {
        global $_wait_exit;
        $job = null;
        while (true) {
            if ($_wait_exit) {
                echo "[" . date('Y-m-d H:i:s') . "] exit ok\n";
                for ($i = 0; $i < 10; $i++) {
                    echo "[" . date('Y-m-d H:i:s') . "] exit ok $i\n";
                    usleep(1000 * 10);
                }
                exit(0);
            }
            $job = $this->_queue->get(3);
            if ($job === false) {
                $error = $this->_queue->error();
                if ($error == 'DEADLINE_SOON' || $error == 'TIMED_OUT') {
                    echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}]get error {$error}\n";
                    if ($this->_messageErrorCallback) {
                        try {
                            $r = call_user_func($this->_messageErrorCallback, $error);
                        } catch (\Exception $e) {

                        }
                    }
                    continue;
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}]Beanstalkd Closed\n";
                    $this->_queue->close(true);
                    sleep(3);
                    $this->init();
                    break;
                }
            } else {
                //echo "[". date('Y-m-d H:i:s') ."][{$this->_className}]get job {$job->id}\n";
                $begin = $this->microtimeFloat();
                $rec = $job->body;
                try {
                    $r = call_user_func($this->_callback, $rec);
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}][Error]{$errorMessage}\n";
                    if (strpos($errorMessage, 'MySQL server has gone away') !== false) {
                        foreach ($this->_dbs as $dbname) {
                            $db = Di::getDefault()->getShared($dbname);
                            $db->close();
                            $db->connect();
                        }
                    } else if (strpos($errorMessage, 'have a valid data snapshot') !== false) {
                        echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}]delete job {$job->id}\n";
                        $job->delete();
                        continue;
                    } else if (strpos($errorMessage, '送消息内容 不能为空') !== false) {
                        $job->delete();
                        continue;
                    }
                    //将任务重新队列
                    $job->release(1023, 1);
                    continue;
                }
                if ($r === false) {
                    $job->delete();

                    $used_time = sprintf("%0.4f", $this->microtimeFloat() - $begin);
                    echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}]delete job {$job->id}, used time {$used_time}\n";
                } else if ($r === 1) {
                    //仍回队列
                    $job->touch();
                } else {
                    //任务过期后自动重试
                    $this->restore($job);
                }
            }
        }
    }

    protected function restore($job)
    {
        $rec = $job->body;
        $num = intval($rec['num']);
        if ($num >= count($this->_delayArray)) {
            echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}]delete job {$job->id}\n";
            return $job->delete();
        }
        $delay = $this->_delayArray[$num];
        $rec['num'] = $num + 1;
        $this->_queue->choose($this->_name);
        $this->_queue->ignore('default');
        $job->restore($rec, 1024, $delay);
        echo "[" . date('Y-m-d H:i:s') . "][{$this->_className}]restore job {$job->id} delay {$delay}\n";
    }

    protected function microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}