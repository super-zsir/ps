<?php

namespace Imee\Comp\Nocode\Service\Context\Form;

use Imee\Comp\Nocode\Service\Context\BaseContext;

class SaveContext extends BaseContext
{
    /**
     * @var int 模块ID
     */
    public $moduleId;

    /**
     * @var string 功能名称
     */
    public $moduleName;

    /**
     * @var string 标识
     */
    public $ncid;

    /**
     * @var string 表单Schena Json
     */
    public $schemaJson;
}

