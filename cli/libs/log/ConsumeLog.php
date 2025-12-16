<?php

namespace Imee\Cli\Libs\Log;

use Imee\Cli\Libs\Log\Adapter\File;

class ConsumeLog extends BaseLog
{
    protected $_stream;

    public function __construct($msgKey)
    {
        parent::__construct($msgKey);
        $this->_stream = new File(ROOT . DS . 'cache' . DS . 'log' . DS . 'consume' . DS);
        $this->_name = 'consume'; //必须和LogTask类中声明的一致
    }

    protected function format($data)
    {
        $this->write(long2ip($data['ip']) . "\t" . date('Y-m-d H:i:s', $data['time']) . "\t" . $data['data']);
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