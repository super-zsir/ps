<?php

namespace Imee\Service\Domain\Context\User\User;

use Imee\Service\Domain\Context\BaseContext;

class BaseInfosContext extends BaseContext
{
    /**
     * 用户ids
     * @var array
     */
    protected $userIds;

    /**
     * @var 用户姓名
     */
    protected $userName;
}
