<?php

namespace Imee\Libs\Event;

use Phalcon\Events\Event;
use Phalcon\Mvc\Application;

class ApplicationEventListener extends EventListener
{
    public function viewRender(Event $event, Application $application)
    {
        return true;
    }

    public function beforeSendResponse(Event $event, Application $application)
    {
        $di = $application->getDi();
        $uuid = $di->getShared('uuid');
        $response = $application->response;
        if ($response->getHeaders()->get('Content-Type') === false) {
            $response->setHeader('Content-Type', 'text/html; charset=utf-8');
        }

        $response->setHeader('TRACE_ID', $uuid);
        $config = $this->getActionConfig($application->dispatcher);
        if ($config === false) {
            $this->headerNoCache($response);
            return true;
        }

        return false;
    }
}
