<?php

namespace Imee\Comp\Nocode\Service\Logic\Init;

use Imee\Comp\Nocode\Service\Context\Init\PointInitContext;
use Imee\Comp\Nocode\Service\Exception\FormException;
use Imee\Comp\Nocode\Service\Logic\MenuLogic;
use Imee\Comp\Operate\Auth\Service\MenuConfigService;

/**
 * 节点初始化逻辑
 */
class PointInitLogic extends BaseModuleLogic
{
    /**
     * @var PointInitContext $context
     */
    private $context;

    /**
     * @var MenuLogic $menuLogic
     */
    private $menuLogic;

    /**
     * @var MenuConfigService $menuConfigService
     */
    private $menuConfigService;

    /**
     * 构造函数
     * @param PointInitContext $context
     */
    public function __construct(PointInitContext $context)
    {
        $this->context = $context;
        $this->menuLogic = MenuLogic::getInstance();
        $this->menuConfigService = new MenuConfigService();
    }

    /**
     * 验证参数
     * @return void
     */
    protected function verify(): void
    {
        if (empty($this->context->controller)) {
            [$code, $msg] = FormException::CONTROLLER_NOT_FOUND;
            throw new FormException($msg, $code);
        }

        if (empty($this->context->moduleId)) {
            [$code, $msg] = FormException::MODULE_ID_NOT_FOUND;
            throw new FormException($msg, $code);
        }

        if (empty($this->context->moduleName)) {
            [$code, $msg] = FormException::MODULE_NAME_NOT_FOUND;
            throw new FormException($msg, $code);
        }
    }

    /**
     * 获取模块控制器名称
     * @return string
     */
    private function getModuleControllerName(): string
    {
        // 查询菜单最上级菜单信息 取出模块名
        $info = $this->menuLogic->getTopParentMenu(['module_id' => $this->context->moduleId]);
        $pathName = $this->menuConfigService->getTopPathByMenu($info['root_path']);
        return strtolower($pathName . '/' . $this->context->controller);
    }

    protected function getModuleList(): array
    {
        $controller = $this->getModuleControllerName();
        return [
            [
                'module_name'      => $this->context->moduleName,
                'parent_module_id' => $this->context->moduleId,
                'is_action'        => 0,
                'controller'       => $controller,
                'action'           => 'main',
                'm_type'           => 2,
                'children'         => [
                    [
                        'module_name' => '列表',
                        'is_action'   => 1,
                        'controller'  => $controller,
                        'action'      => 'list',
                        'm_type'      => 2,
                    ],
                    [
                        'module_name' => '添加',
                        'is_action'   => 1,
                        'controller'  => $controller,
                        'action'      => 'create',
                        'm_type'      => 2,
                    ],
                    [
                        'module_name' => '编辑',
                        'is_action'   => 1,
                        'controller'  => $controller,
                        'action'      => 'modify',
                        'm_type'      => 2,
                    ],
                    [
                        'module_name' => '删除',
                        'is_action'   => 1,
                        'controller'  => $controller,
                        'action'      => 'delete',
                        'm_type'      => 2,
                    ],
                    [
                        'module_name' => '导出',
                        'is_action'   => 1,
                        'controller'  => $controller,
                        'action'      => 'export',
                        'm_type'      => 2,
                    ],
                    [
                        'module_name' => '详情',
                        'is_action'   => 1,
                        'controller'  => $controller,
                        'action'      => 'info',
                        'm_type'      => 2,
                    ],
                ],
            ],
        ];
    }
}