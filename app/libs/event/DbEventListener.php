<?php

namespace Imee\Libs\Event;

use Phalcon\Logger;
use Phalcon\Db\Profiler as DbProfiler;
use Phalcon\Di;

class DbEventListener
{
    protected $_logger;
    protected $_profiler;
    protected $_dispatcher;
    private $_slowTimer = 0.1; // 1=1秒, 0.1 = 100ms
    private $_slowTimerCli = 1; // 1=1秒

    public function __construct()
    {
        $this->_profiler = new DbProfiler();
        $this->_logger = Di::getDefault()->getShared('dblogger');
        $this->_dispatcher = Di::getDefault()->getShared('dispatcher');
    }

    public function beforeQuery($event, $connection)
    {
        // if (IS_CLI || (defined('ENV') && ENV != 'dev')) {
        //     // 命令行和非测试环境不记录
        //     return true;
        // }

        // if (preg_match('/DROP|ALTER/i', $connection->getSQLStatement())) {
        //     return false;
        // }
        $this->_profiler->startProfile($connection->getSQLStatement());
        return true;
    }

    public function afterQuery($event, $connection)
    {
        // if (IS_CLI || (defined('ENV') && ENV != 'dev')) {
        //     // 命令行和非测试环境不记录
        //     return true;
        // }

        $loggerLevel = Logger::INFO;

        $this->_profiler->stopProfile();
        $used = sprintf("%0.4f", $this->_profiler->getLastProfile()->getTotalElapsedSeconds());
        $this->_profiler->reset();

        if (RUNNING == 'testing') {
            $controller = '';
            $action = '';
        } else {
            if (IS_CLI) {
                if ($used >= $this->_slowTimerCli) {
                    $loggerLevel = Logger::WARNING;
                }
                $controller = $this->_dispatcher->getTaskName();
                $action = $this->_dispatcher->getActiveMethod();
            } else {
                if ($used >= $this->_slowTimer) {
                    $loggerLevel = Logger::WARNING;
                }
                $nameSpace = $this->_dispatcher->getNamespaceName();
                $controller = $nameSpace . '\\' . $this->_dispatcher->getControllerName();
                $action = $this->_dispatcher->getActionName();
            }
        }


        $sql = $connection->getSQLStatement();

        if (ENV == 'dev') {
            $bindParmas = $connection->getSqlVariables();
            if ($bindParmas) {
                foreach ($bindParmas as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        continue;
                    }
                    $v = (is_numeric($v) && $v < 4100000000) ? $v : '"' . $v . '"';
                    $sql = str_replace(':' . $k, $v, $sql);
                }
            }
        }

        $this->_logger->log(
            '[Sql][' . $used . '][' . $controller . '/' . $action . ']' . $sql . PHP_EOL,
            $loggerLevel
        );

        return true;
    }
}
