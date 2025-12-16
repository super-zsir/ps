<?php 

namespace Imee\Comp\Nocode\Service\Logic;

use Imee\Comp\Nocode\Service\Context\Menu\GetTopMenuContext;
use Imee\Comp\Nocode\Service\Logic\Menu\GetTopMenuLogic;
use Imee\Comp\Nocode\Service\Traits\SingletonTrait;
use Imee\Comp\Nocode\Service\Context\Menu\AttachContext;
use Imee\Comp\Nocode\Service\Logic\Menu\AttachLogic;
use Imee\Comp\Nocode\Service\Context\Form\InfoContext;
use Imee\Comp\Nocode\Service\Logic\Menu\InfoLogic;

/**
 * 菜单逻辑
 */
class MenuLogic
{
    use SingletonTrait;

    /**
     * 获取顶级父菜单
     * @param array $params
     * @return array
     */
    public function getTopParentMenu(array $params): array
    {
        $context = new GetTopMenuContext($params);
        $logic = new GetTopMenuLogic($context);
        return $logic->handle();
    }

    /**
     * 格式化菜单信息
     * @param array $params
     * @return array
     */
    public function attach(array $params)
    {
        $context = new AttachContext($params);
        $logic = new AttachLogic($context);
        return $logic->handle();
    }

    /**
     * 获取ncid 对应菜单信息
     * @param array $params
     * @return array
     */
    public function getInfo(array $params): array
    {
        $context = new InfoContext($params);
        $logic = new InfoLogic($context);
        return $logic->handle();
    }
}