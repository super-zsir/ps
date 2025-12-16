<?php

use Phalcon\Mvc\Router;

$router = new Router();

$router->setDefaults(array(
    "namespace"  => "Imee\Controller",
    "controller" => "index",
    "action"     => "index"
));

//加载根目录
$rootPath = [];
foreach (glob(APP_PATH . '/controller/*', GLOB_ONLYDIR) as $path) {
    $tmp = explode('app/controller/', $path);
    if ($tmp[1] == 'validation') {
        continue;
    }
    $rootPath[] = $path;
    $nameSpace = ucfirst($tmp[1]);
    $router->add(
        "/api/{$tmp[1]}/:controller/:action/:params(|\/)",
        [
            "namespace"  => "Imee\Controller\\" . $nameSpace,
            "controller" => 1,
            "action"     => 2,
            "params"     => 3
        ]
    );
}

//子目录
foreach ($rootPath as $path) {
    $routerMap = function ($dir) use (&$routerMap, &$router) {
        foreach (glob("{$dir}/*", GLOB_ONLYDIR) as $path) {
            $tmp = explode('app/controller/', $path);
            $nameSpace = '';
            $routePath = '';
            foreach (explode('/', $tmp[1]) as $val) {
                $nameSpace .= ucfirst($val) . '\\';
                $routePath .= $val . '/';
            }
            $routePath = rtrim($routePath, '/');
            $nameSpace = rtrim($nameSpace, '\\');
            $router->add(
                "/api/{$routePath}/:controller/:action/:params(|\/)",
                [
                    "namespace"  => "Imee\Controller\\" . $nameSpace,
                    "controller" => 1,
                    "action"     => 2,
                    "params"     => 3
                ]
            );
            $routerMap($path);
        }
    };
    $routerMap($path);
}

//注册组件里路由
require_once ROOT . '/comp/router.php';

return $router;
