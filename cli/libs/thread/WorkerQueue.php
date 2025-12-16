<?php

namespace Imee\Cli\Libs\Thread;

class WorkerQueue
{
    protected $_queueKey;

    public function __construct($path)
    {
        if (preg_match("/^\d+$/", $path)) {
            $key = $path;
        } else {
            $key = ftok($path, 'a');
        }
        $this->_queueKey = msg_get_queue($key, 0666);
    }

    public function set($data)
    {
        msg_send($this->_queueKey, 1, json_encode($data), false, true);
    }

    public function get()
    {
        msg_receive($this->_queueKey, 0, $type, 1024, $message, false, MSG_IPC_NOWAIT);
        return json_decode($message, true);
    }

    public function dispose()
    {
        msg_remove_queue($this->_queueKey);
    }
}