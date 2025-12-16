<?php

namespace Imee\Cli\Libs\Thread;

class WorkerThread
{
    protected $_num;
    protected $_callback;

    private $_isSupport = false;
    private $_execute = 0;
    private $_message;

    public function __construct($callback, $path, $num = 1)
    {
        $this->_num = $num;
        $this->_callback = $callback;
        $this->_isSupport = self::isSupport();
        if ($this->_isSupport) {
            $this->_message = new WorkerQueue($path);
        } else {
            $this->_message = new WorkerQueueWindow($path);
        }
    }

    public static function isSupport()
    {
        return function_exists('pcntl_fork') && function_exists('msg_get_queue');
    }

    public function start(array $data)
    {
        if ($this->_isSupport) {
            return $this->fork($data);
        } else {
            return $this->exec($data);
        }

    }

    public function send($data)
    {
        return $this->_message->set($data);
    }

    public function receive()
    {
        return $this->_message->get();
    }

    protected function fork(array $data)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            return false;
        } elseif ($pid > 0) {
            $this->_execute++;
            if ($this->_execute >= $this->_num) {
                pcntl_wait($status);
                $this->_execute--;
            }
        } else {
            $pid = posix_getpid();
            $data[] = $pid;
            call_user_func_array($this->_callback, $data);
            exit;
        }
        return $pid;
    }

    protected function exec(array $data)
    {
        return call_user_func_array($this->_callback, $data);
    }
}