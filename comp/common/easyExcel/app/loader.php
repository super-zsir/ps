<?php

use Imee\Comp\Common\Fixed\Loader;

class EasyExcelLoader
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
            'Dcat\EasyExcel' => 'comp/common/easyExcel/src'
        ];
        foreach ($namespaceAdd as &$item) {
            if (PHP_SAPI == 'cli') {
                $item = ROOT . DS . $item;
            }
        }
        $namespaces = array_merge($namespaces, $namespaceAdd);
        $namespaces = array_merge($namespaces, $namespaceAdd);
        if (PHP_SAPI == 'cli') {
            $this->loader->registerDirs([dirname(__DIR__).'/src/cli/tasks'], true);
        }
        return $this->loader->registerNamespaces($namespaces);
    }
}

return (new EasyExcelLoader($loader))->handle();