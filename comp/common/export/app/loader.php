<?php

use Imee\Comp\Common\Fixed\Loader;

class ExportLoader
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
            'Imee\Controller\Common\Export' => 'comp/common/export/src/controller',
            'Imee\Comp\Common\Export' => 'comp/common/export/src',
        ];
        if (ENV == 'dev') {
            $namespaceAdd += [
                'Imee\Comp\Common\Export\Test' => 'comp/common/export/test',
            ];
        }
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

return (new ExportLoader($loader))->handle();