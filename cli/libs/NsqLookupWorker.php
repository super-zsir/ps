<?php

namespace Imee\Cli\Libs;

use Phalcon\Di;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Phpnsq\PhpNsq;
use Imee\Comp\Common\Phpnsq\Command\BaseLookup;

class NsqLookupWorker extends BaseLookup
{
    public function __construct($onMessage, $onException = null, $onExit = null, $lookup = null)
    {
        $manager = Di::getDefault()->getShared('config');
        if (is_null($lookup)) {
            $lookupName = 'lookup';
        } else {
            $lookupName = $lookup;
        }
        $addr = $manager->{$lookupName};
        if (empty($addr)) {
            throw new \Exception("error lookupname {$lookupName}");
        }
        parent::__construct($addr, $onMessage, $onException, $onExit);
        $this->addPeriodicTimer(5, array($this, '_pingNsd'));

        $this->_debug = DEBUG;

        //向DI里注入点东西，用于判断cli进程是否在NsqLookupWorker里运行
        $proxy = Di::getDefault()->getShared('nsq_proxy');
        $proxy->setWorker($this);
        //这样可以直接，在cli里判断是否有注入，然后调用相关方法
    }

    public function _pingNsd()
    {
        foreach ($this->_nsqInstanceMap as $item) {
            $item['instance']->ping();
        }
    }

    //ip 到 nsd instance 映射
    private $_nsqInstanceMap = array();
    private $_manager;
    private $_debug = false;

    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    //发送消息，这是持久链接的，一旦打开，就不会关闭，避免在cli中是NsqClient::publish 方法带来的问题
    //参数是一样的
    //这里发送到的NSD和上面建立连接的方，没什么关系
    //废弃$nsdName
    public function publish($topic, $message, $delay = 0, $nsdName = null)
    {
        if (!$this->_manager) {
            $this->_manager = Di::getDefault()->getShared('config');
        }
        $manager = $this->_manager;
        $nsdName = NsqClient::getNsdNameByTopic($topic);
        if (!isset($this->_nsqInstanceMap[$nsdName]['instance'])) {
            if (!$manager->{$nsdName}) throw new \Exception("error nsd name {$nsdName}");
            $config = $manager->{$nsdName};
            $instance = new PhpNsq($config);
            $this->_nsqInstanceMap[$nsdName] = array(
                "instance" => $instance,
                "errorNum" => 0,
            );
            echo "NsqLookupWorker publish connected to " . $nsdName . "\n";
        } else {
            $instance = $this->_nsqInstanceMap[$nsdName]['instance'];
        }

        $error = null;
        for ($i = 0; $i < 3; $i++) {
            if ($delay > 0) {
                $r = $instance->setTopic($topic)->publishDefer($message, 1000 * $delay, $error);
            } else {
                $r = $instance->setTopic($topic)->publish($message, $error);
            }
            if ($r) {
                $this->_nsqInstanceMap[$nsdName]['errorNum'] = 0;
                if ($this->_debug) {
                    //getLastSendNsd
                    echo "NsqLookupWorker publish ok, use nsd " . $instance->getLastSendNsd() . " topic {$topic}\n";
                }
                return true;
            } else {
                $instance->close();
                unset($this->_nsqInstanceMap[$nsdName]);
                usleep(1000 * 20);
                echo "NsqLookupWorker publish error, use topic {$topic} with " . $error . "\n";
            }
        }
        $this->_nsqInstanceMap[$nsdName]['errorNum']++;
        if ($this->_nsqInstanceMap[$nsdName]['errorNum'] > 10) {
            throw new \Exception("Nsd {$nsdName} with so much errors");
        }
        return false;
    }

    //发送消息，这是持久链接的，一旦打开，就不会关闭，避免在cli中是NsqClient::publishJson 方法带来的问题
    //参数是一样的
    //这里发送到的NSD和上面建立连接的方，没什么关系
    public function publishJson($topic, $message, $delay = 0, $nsdName = null)
    {
        if (!$this->_manager) {
            $this->_manager = Di::getDefault()->getShared('config');
        }
        $manager = $this->_manager;
        $nsdName = NsqClient::getNsdNameByTopic($topic);
        if (!isset($this->_nsqInstanceMap[$nsdName]['instance'])) {
            if (!$manager->{$nsdName}) throw new \Exception("error nsd name {$nsdName}");
            $config = $manager->{$nsdName};
            $instance = new PhpNsq($config);
            $this->_nsqInstanceMap[$nsdName] = array(
                "instance" => $instance,
                "errorNum" => 0,
            );
            echo "NsqLookupWorker publish connected to " . $nsdName . "\n";
        } else {
            $instance = $this->_nsqInstanceMap[$nsdName]['instance'];
        }

        $error = null;
        for ($i = 0; $i < 3; $i++) {
            if ($delay > 0) {
                $r = $instance->setTopic($topic)->publishDeferJson($message, 1000 * $delay, $error);
            } else {
                $r = $instance->setTopic($topic)->publishJson($message, $error);
            }
            if ($r) {
                $this->_nsqInstanceMap[$nsdName]['errorNum'] = 0;
                if ($this->_debug) {
                    //getLastSendNsd
                    echo "NsqLookupWorker publish ok, use nsd " . $instance->getLastSendNsd() . " topic {$topic}\n";
                }
                return true;
            } else {
                $instance->close();
                unset($this->_nsqInstanceMap[$nsdName]);
                usleep(1000 * 20);
                echo "NsqLookupWorker publish error, use topic {$topic} with " . $error . "\n";
            }
        }
        $this->_nsqInstanceMap[$nsdName]['errorNum']++;
        if ($this->_nsqInstanceMap[$nsdName]['errorNum'] > 10) {
            throw new \Exception("Nsd {$nsdName} with so much errors");
        }
        return false;
    }

    //发送消息，这是持久链接的，一旦打开，就不会关闭，避免在cli中是
    //返回有特殊转换
    public function csmsPublish($topic, $message, $delay = 0, $nsdName = null)
    {
        try {
            if (!$this->_manager) {
                $this->_manager = Di::getDefault()->getShared('config');
            }
            $manager = $this->_manager;
            $nsdName = NsqClient::getNsdNameByTopic($topic);

            if (!isset($this->_nsqInstanceMap[$nsdName]['instance'])) {
                if (!$manager->{$nsdName}) {
                    throw new \Exception("error nsd name {$nsdName}");
                }
                $config = $manager->{$nsdName};
                $instance = new PhpNsq($config);
                $this->_nsqInstanceMap[$nsdName] = array(
                    "instance" => $instance,
                    "errorNum" => 0,
                );
                echo "NsqLookupWorker publish connected to " . $nsdName . "\n";
            } else {
                $instance = $this->_nsqInstanceMap[$nsdName]['instance'];
            }

            $error = null;
            for ($i = 0; $i < 3; $i++) {
                if ($delay > 0) {
                    $r = $instance->setTopic($topic)->publishDefer($message, 1000 * $delay, $error);
                } else {
                    $r = $instance->setTopic($topic)->publish($message, $error);
                }
                if ($r) {
                    $this->_nsqInstanceMap[$nsdName]['errorNum'] = 0;
                    if ($this->_debug) {
                        //getLastSendNsd
                        echo "NsqLookupWorker publish ok, use nsd " . $instance->getLastSendNsd() . " topic {$topic}\n";
                    }
                    // 空表示发送成功
                    return '';
                } else {
                    $instance->close();
                    unset($this->_nsqInstanceMap[$nsdName]);
                    usleep(1000 * 2);
                    echo "NsqLookupWorker publish error, use topic {$topic} with " . $error . "\n";
                }
            }
            return "NsqLookupWorker publish error, use topic {$topic} with " . $error;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}