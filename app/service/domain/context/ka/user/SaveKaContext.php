<?php

namespace Imee\Service\Domain\Context\Ka\User;

use Imee\Service\Domain\Context\BaseContext;

class SaveKaContext extends BaseContext
{
    /**
     * @var int 任务时间
     */
    protected $appDate;

    /**
     * @var string 是否生成全部ka
     */
    protected $type;

    /**
     * @var int 人工创建ka uid
     */
    protected $uid;
}