<?php 

namespace Imee\Comp\Nocode\Service;

class HelperService
{
    public static function getRealController($controller): array
    {
        $controllerMap = explode('/', $controller);
        $realController = end($controllerMap);
        array_pop($controllerMap);
        $path = array_map(function($item) {
            return ucfirst($item);
        }, $controllerMap);

        $path = implode('\\', $path);
        $namespace = '\\Imee\\Controller\\' . $path . '\\' . ucfirst($realController) . 'Controller';

        return ['path' => $path, 'controller' => $realController, 'namespace' => $namespace];
    }
}