<?php

namespace Imee\Comp\Nocode\Service\Logic;

use Imee\Comp\Nocode\Service\Context\Init\PointInitContext;
use Imee\Comp\Nocode\Service\Logic\Init\ModuleInitLogic;
use Imee\Comp\Nocode\Service\Logic\Init\PointInitLogic;
use Imee\Comp\Nocode\Service\Traits\SingletonTrait;

/**
 * 初始化处理
 */
class InitLogic
{
    use SingletonTrait;
    
    /**
     * 模块初始化
     * @return bool
     */
    public function moduleInit(): bool
    {
        $logic = new ModuleInitLogic();
        return $logic->handle();
    }

    /**
     * 节点初始化
     * @param array $params
     * @return bool
     */
    public function pointInit(array $params): bool
    {
        $context = new PointInitContext($params);
        $logic = new PointInitLogic($context);
        return $logic->handle();
    }
}

