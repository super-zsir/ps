<?php

namespace Imee\Libs\Event;

use Imee\Exception\ApiException;
use Imee\Exception\ReportException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class DispatchEventListener extends EventListener
{
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        return true;
    }

    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        $di = $dispatcher->getDi();

        //记录操作日志
        //@logRecord(content = "后台用户修改", action = "2", model = "cms_user", model_id = "user_id")
        $annotations = $di->get('annotations');
        $annotations = $annotations->getMethod(
            $dispatcher->getControllerClass(),
            $dispatcher->getActiveMethod()
        );

        $logRecordInfo = [];
        if ($annotations->has('logRecord')) {
            $annotation = $annotations->get('logRecord');
            $logRecordInfo['content'] = $annotation->getArgument('content');
            if ($annotation->hasArgument('model_id')) {
                $logRecordInfo['model_id'] = $annotation->getArgument('model_id');
            }
            if ($annotation->hasArgument('model')) {
                $logRecordInfo['model'] = $annotation->getArgument('model');
            }
            if ($annotation->hasArgument('uid')) {
                $logRecordInfo['uid'] = $annotation->getArgument('uid');
            }
            if ($annotation->hasArgument('action')) {
                $logRecordInfo['action'] = $annotation->getArgument('action');
            } else {
                $logRecordInfo['action'] = '1';
            }
        }

        $di->set('logRecordInfo', function () use ($logRecordInfo) {
            if (!empty($logRecordInfo['model']) && !empty($logRecordInfo['model_id']) && !empty($logRecordInfo['content'])) {
                return $logRecordInfo;
            }
            return [];
        });

        return true;
    }

    public function beforeException(Event $event, Dispatcher $dispatcher, $e)
    {
        if ($e instanceof ReportException) {
            throw new ApiException(ApiException::MSG_ERROR, $e->getMessage());
        }
        if ($e instanceof ApiException) {
            throw new ApiException($e->getCode(), $e->getMsgBase());
        }
        if ($e && $e->getCode() == Dispatcher::EXCEPTION_HANDLER_NOT_FOUND) {
            throw new ApiException(ApiException::NO_FOUND_ERROR);
        }
        $msg = "[beforeException]" . ($e ? $e->getMessage() : '');
        throw new \Exception($msg, $e ? (int)$e->getCode() : 0, $e);
    }

    public function beforeNotFoundAction(Event $event, Dispatcher $dispatcher)
    {
        throw new ApiException(ApiException::NO_FOUND_ERROR);
    }
}
