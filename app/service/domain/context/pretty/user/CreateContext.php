<?php


namespace Imee\Service\Domain\Context\Pretty\User;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 靓号管理
 */
class CreateContext extends BaseContext
{
    /**
     * @var int uid
     */
    protected $uid;

    /**
     * @var string 靓号
     */
    protected $prettyUid;

    /**
     * @var date 过期
     */
    protected $expireTime;

    /**
     * @var string 备注
     */
    protected $mark;
}
