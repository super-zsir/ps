<?php


namespace Imee\Service\Domain\Context\Pretty\Style;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 靓号类型
 */
class CreateContext extends BaseContext
{
    /**
     * @var string 类型名称
     */
    protected $name;

    /**
     * @var int 靓号最短字符数
     */
    protected $shortLimit;

    /**
     * @var int 靓号最长字符数
     */
    protected $longLimit;

    /**
     * @var int 同一字符最多出现次数
     */
    protected $repeatLimit;

    /**
     * @var string 正确实例1
     */
    protected $correctExample1;

    /**
     * @var string 正确实例2
     */
    protected $correctExample2;

    /**
     * @var string 错误实例1
     */
    protected $incorrectExample1;

    /**
     * @var string 错误实例2
     */
    protected $incorrectExample2;

    /**
     * @var string 备注
     */
    protected $remark;

    /**
     * @var int 靓号支持格式
     */
    protected $styleType;

    /**
     * @var int 靓号最短字符数-阿语
     */
    protected $arShortLimit;

    /**
     * @var int 靓号最长字符数-阿语
     */
    protected $arLongLimit;

    /**
     * @var int 靓号最短字符数-土语
     */
    protected $trShortLimit;

    /**
     * @var int 靓号最长字符数-土语
     */
    protected $trLongLimit;
}
