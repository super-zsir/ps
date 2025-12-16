<?php

namespace Imee\Libs\Event;

use Phalcon\Mvc\Dispatcher;
use Phalcon\Http\Response;

class EventListener
{
    public function __construct()
    {
    }

    protected function redirect(Dispatcher $dispatcher, $uri)
    {
        $dispatcher->getDi()->getShared('response')->redirect($uri);
    }

    protected function headerCache(Response $response, $life)
    {
        $response->setHeader('Last-Modified', gmdate("D, d M Y H:i:s", $_SERVER['REQUEST_TIME']) . ' GMT');
        $response->setHeader('Expires', gmdate("D, d M Y H:i:s", $_SERVER['REQUEST_TIME'] + $life) . ' GMT');
        $response->setHeader('Cache-Control', 'max-age=' . $life);
        return true;
    }

    protected function headerNoCache(Response $response)
    {
        $response->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');
        return true;
    }

    protected function getActionConfig(Dispatcher $dispatcher)
    {
        $namespace = $dispatcher->getNamespaceName();
        $controller = strtolower($dispatcher->getControllerName());
        $action = strtolower($dispatcher->getActionName());
        $map = $dispatcher->getDi()->getShared('config')->map;
        if ($namespace != 'Imee\Controller') {
            $splits = explode("\\", $namespace);
            if (count($splits) == 3) {
                $controller = strtolower($splits[1]) . '/' . $controller;
            }
        }
        if (!isset($map[$controller]) || !isset($map[$controller][$action])) {
            return false;
        }

        return $map[$controller][$action];
    }

    protected function getPageName(Dispatcher $dispatcher)
    {
        $namespace = $dispatcher->getNamespaceName();
        $controller = strtolower($dispatcher->getControllerName());
        $action = strtolower($dispatcher->getActionName());
        if ($namespace != 'Imee\Controller') {
            $splits = explode("\\", $namespace);
            if (count($splits) == 3) {
                $controller = strtolower($splits[1]) . '/' . $controller;
            }
        }
        return array(
            'controller' => $controller,
            'action'     => $action,
        );
    }

    protected function getPageUrl(Dispatcher $dispatcher)
    {
        $config = $this->getPageName($dispatcher);
        return "/{$config['controller']}/{$config['action']}";
    }
}