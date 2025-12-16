<?php

namespace Imee\Cli\Libs\Thread;

class WorkerQueueWindowData
{
    private static $_instance;

    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new WorkerQueueWindowData();
        }
        return self::$_instance;
    }

    protected $_data = array();

    public function set($key, $val)
    {
        if (!isset($this->_data[$key])) $this->_data[$key] = array();
        $this->_data[$key][] = $val;
    }

    public function get($key)
    {
        if (isset($this->_data[$key])) {
            return array_shift($this->_data[$key]);
        }
        return false;
    }

    public function dispose($key)
    {
        if (isset($this->_data[$key])) {
            unset($this->_data[$key]);
        }
        return true;
    }
}

class WorkerQueueWindow
{
    protected $_queueKey;
    protected $_message;

    public function __construct($path)
    {
        $this->_queueKey = $this->ftok($path);
        $this->_message = WorkerQueueWindowData::instance();
    }

    public function set($data)
    {
        $this->_message->set($this->_queueKey, $data);
    }

    public function get()
    {
        return $this->_message->get($this->_queueKey);
    }

    public function dispose()
    {
        $this->_message->dispose($this->_queueKey);
    }

    protected function ftok($pathname)
    {
        $st = @stat($pathname);
        if (!$st) {
            return -1;
        }
        $proj = 250;
        return sprintf("%u", (($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($proj & 0xff) << 24)));
    }
}