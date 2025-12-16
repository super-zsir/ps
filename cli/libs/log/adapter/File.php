<?php

namespace Imee\Cli\Libs\Log\Adapter;

class File
{
    const SPLIT_HOUR = 'hour';
    const SPLIT_DAY = 'day';
    const SPLIT_MONTH = 'month';

    protected $_splitType;
    protected $_dir;

    protected $_fp;
    protected $_preFp;

    public function __construct($dir, $splitType = 'day')
    {
        $this->_dir = $dir;
        $this->_splitType = $splitType;
        $this->init();
    }

    public function write(&$data)
    {
        if ($this->_preFp != $this->createFp()) {
            $this->close();
            $this->init();
        }

        if (is_array($data)) {
            $msg = implode("\n", $data);
            fwrite($this->_fp, $msg . "\n");
        } else {
            fwrite($this->_fp, $data . "\n");
        }
    }

    public function close()
    {
        if ($this->_fp) {
            fclose($this->_fp);
            $this->_fp = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    protected function init()
    {
        $file = $this->splitFile();
        $path = dirname($file);
        $this->createdir($path);
        $this->_fp = fopen($file, 'ab');
    }

    protected function splitFile()
    {
        $this->_preFp = $this->createFp();
        return rtrim($this->_dir, DS) . DS . $this->_preFp . '.log';
    }

    private function createFp()
    {
        $array = array(
            'hour'  => 'Y-m/d/H',
            'day'   => 'Y-m/d',
            'month' => 'Y-m',
        );
        return date($array[$this->_splitType]);
    }

    private function createdir($dir)
    {
        return is_dir($dir) || ($this->createdir(dirname($dir)) && @mkdir($dir));
    }
}