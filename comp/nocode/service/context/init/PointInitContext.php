<?php

namespace Imee\Comp\Nocode\Service\Context\Init;

use Imee\Comp\Nocode\Service\Context\BaseContext;

class PointInitContext extends BaseContext
{
    /**
     * @var string 标识
     */
    public $controller;

    /**
     * @var int 模块ID
     */
    public $moduleId;

    /**
     * @var string 模块名称
     */
    public $moduleName;
}

