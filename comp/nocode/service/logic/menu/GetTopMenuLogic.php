<?php

namespace Imee\Comp\Nocode\Service\Logic\Menu;

use Imee\Comp\Operate\Auth\Service\Context\Modules\InfoContext;
use Imee\Comp\Operate\Auth\Service\ModulesService;
use Imee\Comp\Nocode\Service\Context\Menu\GetTopMenuContext;
use Imee\Comp\Nocode\Service\Exception\FormException;

/**
 * 获取顶级父菜单逻辑
 */
class GetTopMenuLogic
{
    /**
     * @var GetTopMenuContext $context
     */
    private $context;

    /**
     * @var ModulesService $moduleService
     */
    private $moduleService;

    /**
     * 构造函数
     * @param GetTopMenuContext $context
     */
    public function __construct(GetTopMenuContext $context)
    {
        $this->context = $context;
        $this->moduleService = new ModulesService();
    }

    /**
     * 处理逻辑
     * @return array
     */
    public function handle(): array
    {
        $this->verify();
        $info = $this->moduleService->getInfoById(new InfoContext(['module_id' => $this->context->moduleId]));
        for ($i = 1; $i <= 8; ++ $i) {
            if (!empty($info) && !$info['root_path']) {
                $info = $this->moduleService->getInfoById(new InfoContext(['module_id' => $info['parent_module_id']]));
            }

            if (empty($info) || $info['parent_module_id'] == 0 || $info['root_path']) {
                break;
            }
        }

        return !empty($info) ? $info : [];
    }

    /**
     * 验证参数
     * @return void
     */
    private function verify(): void
    {
        if (empty($this->context->moduleId)) {
            [$code, $msg] = FormException::MODULE_ID_NOT_FOUND;
            throw new FormException($msg, $code);
        }
    }
}