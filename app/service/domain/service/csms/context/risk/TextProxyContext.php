<?php

namespace Imee\Service\Domain\Service\Csms\Context\Risk;

use Imee\Service\Domain\Context\BaseContext;

class TextProxyContext extends BaseContext
{
    /**
     * @var string 审核项标识
     */
    protected $choice;

    /**
     * @var string 文本检测的策略模式
     */
    protected $mode;

    /**
     * @var array 场景数组
     */
    protected $scenes;

    /**
     * @var string|array 文本
     */
    protected $path;

    /**
     * @var string 主键名
     */
    protected $pkValue;

    /**
     * @var string 唯一标识，幂等
     */
    protected $dataId;

    /**
     * @var boolean 是否严格模式
     */
    protected $strict = false;

    /**
     * @var int 用户id
     */
    protected $uid;

}