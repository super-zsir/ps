<?php

namespace Imee\Comp\Nocode\Service\Logic\Init;

/**
 * 模块初始化逻辑
 */
class ModuleInitLogic extends BaseModuleLogic
{
    protected function getModuleList(): array
    {
        return [
            [
                'module_name'      => '零代码平台',
                'parent_module_id' => 0,
                'is_action'        => 0,
                'controller'       => '',
                'action'           => '',
                'm_type'           => 1,
                'deleted'          => 0,
                'root_path'        => 'comp/nocode/app/controller',
                'children'         => [
                    [
                        'module_name' => '表单管理',
                        'is_action'   => 0,
                        'controller'  => 'nocode/form',
                        'action'      => 'main',
                        'm_type'      => 2,
                        'deleted'     => 0,
                        'children'    => [
                            [
                                'module_name' => '列表',
                                'is_action'   => 1,
                                'controller'  => 'nocode/form',
                                'action'      => 'list',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ],
                            [
                                'module_name' => '保存',
                                'is_action'   => 1,
                                'controller'  => 'nocode/form',
                                'action'      => 'save',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ],
                            [
                                'module_name' => '校验',
                                'is_action'   => 1,
                                'controller'  => 'nocode/form',
                                'action'      => 'check',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ],
                            [
                                'module_name' => '删除',
                                'is_action'   => 1,
                                'controller'  => 'nocode/form',
                                'action'      => 'delete',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ],
                            [
                                'module_name' => '详情',
                                'is_action'   => 1,
                                'controller'  => 'nocode/form',
                                'action'      => 'info',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ]
                        ],
                    ],
                    [
                        'module_name' => '模型管理',
                        'is_action'   => 0,
                        'controller'  => 'nocode/model',
                        'action'      => 'main',
                        'm_type'      => 2,
                        'deleted'     => 0,
                        'children'    => [
                            [
                                'module_name' => '列表',
                                'is_action'   => 1,
                                'controller'  => 'nocode/model',
                                'action'      => 'list',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ],
                            [
                                'module_name' => '详情',
                                'is_action'   => 1,
                                'controller'  => 'nocode/model',
                                'action'      => 'info',
                                'm_type'      => 2,
                                'deleted'     => 0,
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }
}