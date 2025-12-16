<?php

namespace Imee\Service\Domain\Context\Ka\User;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class BuildAlStatusContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var int uid
     */
    protected $uid;

    /**
     * @var int 建联类型
     */
    protected $buildAlType;

    /**
     * @var string 建联账号
     */
    protected $buildAccount;


    /**
     * @var string 建联时间
     */
    protected $friendDate;

    /**
     * @var string 来源
     */
    protected $source;

}