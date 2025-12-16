<?php

namespace Imee\Cli\Libs\Log;

use Imee\Cli\Libs\Thread\WorkerQueue;

abstract class BaseLog
{
    protected $_message;
    protected $_name = '';
    private $_max;
    private $_pid;

    public function __construct($msgKey, $max = 100)
    {
        $this->_message = new WorkerQueue($msgKey);
        $this->_max = $max;
        $this->_pid = getmypid();
    }

    public function init()
    {
        $title = 'Log ' . $this->_name;
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        } else if (function_exists('setproctitle')) {
            setproctitle($title);
        }

        while (true) {
            $message = $this->_message->get();
            if ($message) {
                if ($this->_name == $message['type']) $this->format($message);
            } else {
                usleep(1000 * 100);
            }
        }
    }

    public function __destruct()
    {
        $this->commit();
        $this->close();
    }

    private $_buffer = array();
    private $_length = 0;

    protected function commit()
    {
        if ($this->_length > 0) {
            $this->flush($this->_buffer);
            $this->_buffer = array();
            $this->_length = 0;
        }
    }

    protected function write($msg)
    {
        $this->_buffer[] = $msg;
        $this->_length++;
        if ($this->_length >= $this->_max) {
            $this->commit();
        }
    }

    abstract protected function format($data);

    abstract protected function flush(array &$data);

    abstract protected function close();
}