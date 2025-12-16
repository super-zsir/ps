<?php

use Imee\Comp\Common\Fixed\Loader;

class UploadLoader
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
            'Imee\Controller\Common' => 'comp/common/upload/app/controller/'
        ];
        foreach ($namespaceAdd as &$item) {
            if (PHP_SAPI == 'cli') {
                $item = ROOT . DS . $item;
            }
        }
        $namespaces = array_merge($namespaces, $namespaceAdd);
        return $this->loader->registerNamespaces($namespaces);
    }
}

return (new UploadLoader($loader))->handle();