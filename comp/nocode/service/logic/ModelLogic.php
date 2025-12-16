<?php

namespace Imee\Comp\Nocode\Service\Logic;

use Imee\Comp\Nocode\Service\Context\Model\InfoContext;
use Imee\Comp\Nocode\Service\Context\Model\ListContext;
use Imee\Comp\Nocode\Service\Logic\Model\InfoLogic;
use Imee\Comp\Nocode\Service\Logic\Model\ListLogic;
use Imee\Comp\Nocode\Service\Traits\SingletonTrait;

/**
 * 资源映射处理逻辑
 */
class ModelLogic
{
    use SingletonTrait;

    /**
     * 获取资源映射列表
     * @param array $params
     * @return array
     */
    public function getList(array $params): array
    {
        $context = new ListContext($params);
        $logic = new ListLogic($context);

        return $logic->handle();
    }

    /**
     * 获取资源映射详情
     * @param array $params
     * @return array
     */
    public function info(array $params): array
    {
        $context = new InfoContext($params);
        $logic = new InfoLogic($context);

        return $logic->handle();
    }
}

