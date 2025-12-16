<?php

use Phalcon\Mvc\Router;

class NocodeRoute
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        //非cms不加载路由
        if (defined('IS_CMS') && !IS_CMS) {
            return $this->router;
        }

        $apiPrefix = defined('API_PREFIX') ? API_PREFIX : 'api';

        $this->router->add(
            "/{$apiPrefix}/nocode/:controller/:action/:params(|\/)",
            [
                "namespace"  => 'Imee\Controller\Nocode',
                "controller" => 1,
                "action"     => 2,
                "params"     => 3
            ]
        );

        return $this->router;
    }
}

return (new NocodeRoute($router))->handle();