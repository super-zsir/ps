<?php


namespace Imee\Service\Domain\Context\Pretty\UserCustomize;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 靓号发放管理
 */
class ModifyContext extends BaseContext
{
    /**
     * @var int id
     */
    protected $id;

    /**
     * @var int 靓号有效天数
     */
    protected $prettyValidityDay;

    /**
     * @var date 资格使用有效日期
     */
    protected $qualificationExpireDateline;
}
