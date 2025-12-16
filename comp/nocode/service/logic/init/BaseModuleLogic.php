<?php

namespace Imee\Comp\Nocode\Service\Logic\Init;

use Imee\Comp\Operate\Auth\Models\Cms\CmsModules;

/**
 * 模块逻辑基类
 */
abstract class BaseModuleLogic
{
    /**
     * @var CmsModules $cmsModuleModel
     */
    protected $cmsModuleModel = CmsModules::class;

    public function handle(): bool
    {        
        $this->verify();
        foreach ($this->getModuleList() as $item) {
            $moduleId = $this->createModule($item);
            if ($moduleId > 0) {
                $this->insertChildren($item, $moduleId);
            }
        }
        
        return true;
    }

    protected function verify(): void
    {
        // 默认不做验证，子类可重写
    }

    /**
     * 获取模块列表
     *
     * @return array 模块数据列表
     */
    abstract protected function getModuleList(): array;
    
    /**
     * 创建模块记录
     *
     * @param array $data 模块数据
     * @param int $parentId 父级模块ID
     * @return int 返回创建的模块ID
     */
    private function createModule(array $data, int $parentId = 0): int
    {
        $info = new $this->cmsModuleModel;
        $info->module_name = $data['module_name'];
        $info->parent_module_id = $parentId ?: ($data['parent_module_id'] ?? 0);
        $info->is_action = $data['is_action'];
        $info->controller = $data['controller'];
        $info->action = $data['action'];
        $info->m_type = $data['m_type'];
        $info->deleted = $data['deleted'] ?? 0;
        $info->root_path = $data['root_path'] ?? '';
        $info->system_id = SYSTEM_ID;
        $info->save();

        return $info->module_id ?? 0;
    }

    /**
     * 递归插入子模块
     *
     * @param array $item 模块数据
     * @param int $parentId 父级模块ID
     * @return void
     */
    private function insertChildren(array $item, int $parentId): void
    {
        if (empty($item['children'])) {
            return;
        }
        
        foreach ($item['children'] as $child) {
            $moduleId = $this->createModule($child, $parentId);
            if ($moduleId > 0) {
                $this->insertChildren($child, $moduleId);
            }
        }
    }
}