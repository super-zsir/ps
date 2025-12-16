<?php

use Imee\Comp\Common\Fixed\Loader;

class NocodeLoader
{
    /**
     * @var Loader
     */
    private $loader;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function handle()
    {
        $namespaces = $this->loader->getNamespaces();
        $namespaceAdd = [
            'Imee\Controller\Nocode'   => 'comp/nocode/app/controller',
            'Imee\Comp\Nocode\Models'  => 'comp/nocode/models',
            'Imee\Comp\Nocode\Apijson' => 'comp/nocode/apijson',
            'Imee\Comp\Nocode\Service' => 'comp/nocode/service',
        ];
        foreach ($namespaceAdd as &$item) {
            if (PHP_SAPI == 'cli') {
                $item = ROOT . DS . $item;
            }
        }
        $namespaces = array_merge($namespaces, $namespaceAdd);
        if (PHP_SAPI == 'cli') {
            $this->loader->registerDirs([dirname(__DIR__) . '/cli/tasks'], true);
        }
        return $this->loader->registerNamespaces($namespaces);
    }
}

return (new NocodeLoader($loader))->handle();