<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;

class ReplaceHandle extends AbstractHandle
{
    protected $replaceData = [];

    public function handle()
    {
        $condition = $this->condition->getCondition();
        
        // 提取 @replace 数据
        if (isset($condition['@replace'])) {
            $this->replaceData = $condition['@replace'];
            unset($condition['@replace']);
            
            // 更新 condition，移除 @replace
            $this->condition->setCondition($condition);
        }
    }

    public function getReplaceData(): array
    {
        return $this->replaceData;
    }

    public function hasReplaceData(): bool
    {
        return !empty($this->replaceData);
    }

    protected function buildModel()
    {
        // 无需构建模型
    }
} 