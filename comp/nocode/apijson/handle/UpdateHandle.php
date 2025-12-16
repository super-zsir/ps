<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;

class UpdateHandle extends AbstractHandle
{
    protected $updateData = [];

    public function handle()
    {
        $condition = $this->condition->getCondition();
        
        // 提取 @update 数据
        if (isset($condition['@update'])) {
            $this->updateData = $condition['@update'];
            unset($condition['@update']);
            
            // 更新 condition，移除 @update
            $this->condition->setCondition($condition);
        }
    }

    public function getUpdateData(): array
    {
        return $this->updateData;
    }

    public function hasUpdateData(): bool
    {
        return !empty($this->updateData);
    }

    protected function buildModel()
    {
        // 无需构建模型
    }
} 