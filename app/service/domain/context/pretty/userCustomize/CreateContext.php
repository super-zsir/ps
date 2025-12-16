<?php


namespace Imee\Service\Domain\Context\Pretty\UserCustomize;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 靓号发放管理
 */
class CreateContext extends BaseContext
{
    /**
     * @var string uid_str
     */
    protected $uidStr;

    /**
     * @var int 类型ID
     */
    protected $customizePrettyId;

    /**
     * @var int 靓号有效天数
     */
    protected $prettyValidityDay;

    /**
     * @var int 资格使用有效天数
     */
    protected $qualificationExpireDay;

    /**
     * @var string 备注
     */
    protected $remark;

    /**
     * @var int 是否可转赠
     */
    protected $giveType;

    /**
     * @var int 发放数量
     */
    protected $sendNum;

    /**
     * @var int admin_id
     */
    protected $adminId;
}
