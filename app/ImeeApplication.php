<?php

use Imee\Comp\Common\Log\Service\ErrorLogService;
use Imee\Comp\Common\Log\Service\NoticeService;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Comp\Common\Fixed\OutputError;
use Imee\Exception\ApiException;
use Imee\Libs\Event\ApplicationEventListener;
use Imee\Libs\Event\DispatchEventListener;
use Imee\Service\Helper;
use Phalcon\Mvc\Application;

class ImeeApplication
{
    public function __construct()
    {
    }

    private static $_instance = null;

    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new ImeeApplication();
        }
        return self::$_instance;
    }

    public function run()
    {
        $this->init();
    }

    private function init()
    {
        add_header_origin();
        try {
            $loader = require_once __DIR__ . '/loader.php';
            $loader->register();

            // Create a DI
            $di = require_once __DIR__ . '/di.php';

            // Handle the request
            $application = new Application($di);
            $application->setEventsManager($eventsManager);

            $eventsManager->attach('application', new ApplicationEventListener());
            $eventsManager->attach('dispatch', new DispatchEventListener());

            echo $resp = $application->handle()->getContent();
            fastcgi_finish_request();
            //记录操作日志
            $get = $application->request->getQuery();
            $post = $application->request->getPost();
            $resp = json_decode($resp, true);

            if (!$resp['success']) {
                return;
            }
            $admin = Helper::getSystemUserInfo();
            $admin = ['operate_id' => $admin['user_id'], 'operate_name' => $admin['user_name']];
            $request = array_merge($get, $post, $admin);
            if ($di->get('logRecordInfo')) {
                OperateLog::addLog($di->get('logRecordInfo'), $request, $resp);
            }
            // 记录通知日志
            NoticeService::addNoticeLog($request);
        } catch (ApiException $e) {
            $array = array(
                'success' => false,
                'msg'     => $e->getMsg(),
                'code'    => $e->getCode()
            );
            echo json_encode($array, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            ob_end_clean();
            ErrorLogService::addLog(['message' => $e->getMessage()]);
            new OutputError($e);
        }
    }
}
