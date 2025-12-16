<?php

namespace Imee\Comp\Nocode\Service\Logic;

use Imee\Comp\Nocode\Service\Context\Form\ListContext;
use Imee\Comp\Nocode\Service\Context\Form\InfoContext;
use Imee\Comp\Nocode\Service\Context\Form\SaveContext;
use Imee\Comp\Nocode\Service\Logic\Form\ListLogic;
use Imee\Comp\Nocode\Service\Logic\Form\InfoLogic;
use Imee\Comp\Nocode\Service\Logic\Form\CheckLogic;
use Imee\Comp\Nocode\Service\Logic\Form\DeleteLogic;
use Imee\Comp\Nocode\Service\Logic\Form\SaveLogic;
use Imee\Comp\Nocode\Service\Traits\SingletonTrait;

/**
 * 表单管理逻辑
 */
class FormLogic
{
    use SingletonTrait;

    /**
     * 获取表单列表
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
     * 保存表单
     * @param array $params
     * @return bool
     */
    public function save(array $params): bool
    {
        $context = new SaveContext($params);
        $logic = new SaveLogic($context);

        return $logic->handle();
    }

    /**
     * 删除表单
     * @param array $params
     * @return bool
     */
    public function delete(array $params): bool
    {
        $context = new InfoContext($params);
        $logic = new DeleteLogic($context);

        return $logic->handle();
    }

    /**
     * 获取表单详情
     * @param array $params
     * @return array
     */
    public function info(array $params): array
    {
        $context = new InfoContext($params);
        $logic = new InfoLogic($context);

        return $logic->handle();
    }

    /**
     * 校验表单是否存在
     * @param array $params
     * @return bool
     */
    public function check(array $params): bool
    {
        $context = new InfoContext($params);
        $logic = new CheckLogic($context);

        return $logic->handle();
    }
}

