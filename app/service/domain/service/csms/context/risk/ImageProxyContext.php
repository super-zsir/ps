<?php

namespace Imee\Service\Domain\Service\Csms\Context\Risk;

use Imee\Service\Domain\Context\BaseContext;

/**
 * 图片检测
 */
class ImageProxyContext extends BaseContext
{

    /**
     * @var string 审核项标识
     */
    protected $choice;

    /**
     * @var string 图片检测的策略模式
     */
    protected $mode;

    /**
     * @var array 场景数组
     */
    protected $scenes;

    /**
     * @var string|array 图片地址
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
}
