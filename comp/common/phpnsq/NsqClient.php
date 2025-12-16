<?php

namespace Imee\Comp\Common\Phpnsq;

use Imee\Helper\Constant\NsqConstant;
use Phalcon\Di;

class NsqClient
{
    public static function getNsdNameByTopic($topic)
    {
        if (isset(NsqConstant::$forwardToCircle[$topic])) {
            return NsqConstant::$forwardToCircle[$topic];
        } else {
            throw new \Exception("error params topic {$topic}");
        }
    }

    private static function getAddrsByTopic($topic)
    {
        $nsdName = self::getNsdNameByTopic($topic);
        $manager = Di::getDefault()->getShared('config');
        if (!$manager->{$nsdName}) {
            throw new \Exception("error nsd name {$nsdName}");
        }
        return $manager->{$nsdName};
    }

    private static function getAddrsByNsdName($nsdName)
    {
        if (is_null($nsdName)) {
            $nsdName = NsqConstant::Nsq;
        }
        $manager = Di::getDefault()->getShared('config');
        if (!$manager->{$nsdName}) {
            throw new \Exception("error nsd name {$nsdName}");
        }
        return $manager->{$nsdName};
    }

    //废弃$nsdName参数
    public static function publish($topic, $message, $delay = 0, $nsdName = null)
    {
        //做点比较恶心的事情
        if (IS_CLI) {
            $proxy = Di::getDefault()->getShared('nsq_proxy');
            if ($proxy->enabled()) {
                return $proxy->publish($topic, $message, $delay);
            }
        }
        $config = self::getAddrsByTopic($topic);
        $phpnsq = new PhpNsq($config);
        if ($delay > 0) {
            $r = $phpnsq->setTopic($topic)->publishDefer($message, 1000 * $delay, $error);
        } else {
            $r = $phpnsq->setTopic($topic)->publish($message, $error);
        }
        $phpnsq->close();
        return $r;
    }

    public static function publishJson($topic, $message, $delay = 0)
    {
        //做点比较恶心的事情
        if (IS_CLI) {
            $proxy = Di::getDefault()->getShared('nsq_proxy');
            if ($proxy->enabled()) {
                return $proxy->publishJson($topic, $message, $delay);
            }
        }
        $config = self::getAddrsByTopic($topic);
        $phpnsq = new PhpNsq($config);
        if ($delay > 0) {
            $r = $phpnsq->setTopic($topic)->publishDeferJson($message, 1000 * $delay, $error);
        } else {
            $r = $phpnsq->setTopic($topic)->publishJson($message, $error);
        }
        $phpnsq->close();
        return $r;
    }

    //废弃$nsdName参数 - 内容安全管理专用（其他组勿用）
    public static function csmsPublish($topic, $message, $delay = 0, $nsdName = null)
    {
        //做点比较恶心的事情
        if (IS_CLI) {
            $proxy = Di::getDefault()->getShared('nsq_proxy');
            if ($proxy->enabled()) {
                return $proxy->csmsPublish($topic, $message, $delay);
            }
        }
        try {
            $config = self::getAddrsByTopic($topic);
            $phpnsq = new PhpNsq($config);
            if ($delay > 0) {
                $r = $phpnsq->setTopic($topic)->publishDefer($message, 1000 * $delay, $error);
            } else {
                $r = $phpnsq->setTopic($topic)->publish($message, $error);
            }
            // 关闭链接
            $phpnsq->close();
            // csms 空表示成功，其他表示失败
            if ($r) {
                return '';
            } else {
                return "Nsq csms publish error, use topic {$topic} with " . $error;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    //废弃$nsdName参数
    public static function publishMulti($topic, $messages, $nsdName = null)
    {
        $config = self::getAddrsByTopic($topic);
        $phpnsq = new PhpNsq($config);
        $r = $phpnsq->setTopic($topic)->publishMulti($messages, $error);
        $phpnsq->close();
        return $r;
    }

    //别忘记 close,
    //如果多次调用pub时间相差太大，需要自己定时ping
    public static function instance($nsdName = null)
    {
        $config = self::getAddrsByNsdName($nsdName);
        return new PhpNsq($config);
    }
}
