<?php

namespace Imee\Service\Domain\Service\Csms\Context\Saas;

use Imee\Service\Domain\Context\BaseContext;

class ServicerOperateContext extends BaseContext
{
    public $id;

    /**
     * 服务商标记
     */
    public $mark;

    /**
     * 服务商名称
     */
    public $name;

    /**
     * 类型
     */
    public $type;

    public $admin;

    public $state;
}
