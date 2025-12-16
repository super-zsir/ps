<?php

namespace Imee\Cli\Libs\Log;

use Imee\Cli\Libs\Log\Adapter\File;

class TestLog extends BaseLog
{
    protected $_stream;

    public function __construct($msgKey)
    {
        parent::__construct($msgKey);
        $this->_stream = new File(ROOT . DS . 'cache' . DS . 'log' . DS . 'test' . DS);
        $this->_name = 'test'; //必须和LogTask类中声明的一致
    }

    protected function format($data)
    {
        $this->write($data['ip'] . "\t" . $data['time'] . "\t" . $data['data']['a']);
    }

    protected function flush(array &$data)
    {
        $this->_stream->write($data);
    }

    protected function close()
    {
        $this->_stream->close();
    }
}