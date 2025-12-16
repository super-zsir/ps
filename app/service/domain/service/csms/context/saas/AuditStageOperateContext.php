<?php

namespace Imee\Service\Domain\Service\Csms\Context\Saas;

use Imee\Service\Domain\Context\BaseContext;

class AuditStageOperateContext extends BaseContext
{
    public $id;

    /**
     * 审核项标识id
     */
    public $cid;

    /**
     * 阶段
     */
    public $stage;

    /**
     * 质检百分比
     */
    public $inspect = 100;

    /**
     * 回调方式
     */
    public $review;

    public $admin;

    public $state;

    public $info;
}
