<?php

namespace Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 封禁用户
 */
class UserContext extends BaseContext
{
    /**
     * @var integer uid
     */
    protected $uid;

    /**
     * @var integer 封禁等级
     */
    protected $deleted;

    /**
     * @var integer 封禁时长
     */
    protected $duration;

    /**
     * @var string 原因
     */
    protected $reason;

    /**
     * @var string 备注
     */
    protected $mark;

    /**
     * @var string 设备号
     */
    protected $mac;

    /**
     * @var string 设备号(imei)
     */
    protected $imei;

    /**
     * @var int 是否封禁设备
     */
    protected $macneed;

    /**
     * @var int 是否同步安全手机号
     */
    protected $macneedphone;

    /**
     * @var int
     */
    protected $source;

    /**
     * @var int 数据ID
     */
    protected $opCheck;

    /**
     * @var string 设备号(did)
     */
    protected $did;

    /**
     * @var int
     */
    protected $language;

    /**
     * @var
     */
    protected $ruleType;

    /**
     * @var string 封禁用户来源
     */
    protected $forbiddenSource;
}
