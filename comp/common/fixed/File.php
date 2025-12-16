<?php

namespace Imee\Comp\Common\Fixed;

use Phalcon\Logger\Adapter\File as XFile;
use Exception;

/*
	该程序主要修改以下功能
	在构造函数里，不在立即打开文件，而是等待需要写文件的时候在打开
	这样配合日志批量写入，一个实例，一次请求只写入一次
*/

class File extends XFile
{
    /*
        $name 必须有扩展名
    */
    public function __construct($name, $options = null)
    {
        $mode = null;

        $dir = dirname($name);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }

        if (!empty($options) && isset($options["mode"])) {
            $mode = $options["mode"];
            if (strpos($mode, 'r') !== false) {
                throw new Exception("Logger must be opened in append or write mode");
            }
        }

        if (empty($mode)) {
            $mode = 'ab';
        }
        $options['mode'] = $mode;

        //增加按日期写日志
        $this->_path = $this->splitFileByDay($name);
        $this->_options = $options;
    }

    protected function splitFileByDay($name)
    {
        $fileName = basename($name);
        $count = 0;
        $path = dirname($name) . DS . preg_replace_callback("/^(.*?)\./", function ($match) {
                return $match[1] . '_' . date('Y-m-d') . '.';
            }, $fileName, 1, $count);
        if ($count == 0) throw new Exception("Logger name must with ext");
        return $path;
    }

    public function logInternal($message, $type, $time, array $context)
    {
        $this->write($this->getFormatter()->format($message, $type, $time, $context));
    }

    protected function write($msg)
    {
        if (is_null($this->_fileHandler)) {
            $this->_fileHandler = @fopen($this->_path, $this->_options['mode']);
        }
        if (!is_resource($this->_fileHandler)) {
            throw new Exception("Cannot send message to the log because it is invalid path:" . $this->_path);
        }
        @fwrite($this->_fileHandler, $msg);
    }

    public function commit()
    {
        if (!$this->_transaction) {
            throw new Exception("There is no active transaction");
        }

        $this->_transaction = false;
        if (is_array($this->_queue) && !empty($this->_queue)) {
            $len = count($this->_queue);
            $message = null;
            $writeBuffer = '';
            for ($i = 0; $i < $len; $i++) {
                $message = $this->_queue[$i];
                $writeBuffer .= $this->getFormatter()->format(
                    $message->getMessage(),
                    $message->getType(),
                    $message->getTime(),
                    $message->getContext()
                );
            }
            $this->_queue = array();
            if (!empty($writeBuffer)) {
                $this->write($writeBuffer);
            }
        }
        return $this;
    }

    public function close()
    {
        if ($this->_fileHandler) {
            parent::close();
        }
        return true;
    }

    public function __destruct()
    {
        if ($this->_transaction && !empty($this->_queue)) {
            $this->commit();
        }
        $this->close();
    }
}
