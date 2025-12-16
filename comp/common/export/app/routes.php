<?php

use Phalcon\Mvc\Router;

class ExportRoute
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle(): Router
    {
        $apiPrefix = defined('API_PREFIX') ? API_PREFIX : 'api';

        $this->router->add(
            "/{$apiPrefix}/common/export/:controller/:action/:params(|\/)",
            [
                "namespace"  => 'Imee\Controller\Common\Export',
                "controller" => 1,
                "action"     => 2,
                "params"     => 3
            ]
        );

        return $this->router;
    }
}

return (new ExportRoute($router))->handle();