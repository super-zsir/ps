<?php

use Imee\Comp\Common\Fixed\Loader;

class LogLoader
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
            'Imee\Controller\Log'  => 'comp/common/log/src/controller',
            'Imee\Comp\Common\Log' => 'comp/common/log/src',
        ];
        foreach ($namespaceAdd as &$item) {
            if (PHP_SAPI == 'cli') {
                $item = ROOT . DS . $item;
            }
        }
        $namespaces = array_merge($namespaces, $namespaceAdd);
        if (PHP_SAPI == 'cli') {
            $this->loader->registerDirs([dirname(__DIR__).'/src/cli/tasks'], true);
        }
        return $this->loader->registerNamespaces($namespaces);
    }
}

return (new LogLoader($loader))->handle();