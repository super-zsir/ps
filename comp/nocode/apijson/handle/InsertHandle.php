<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;

class InsertHandle extends AbstractHandle
{
    protected $insertData = [];

    public function handle()
    {
        $condition = $this->condition->getCondition();
        
        // 提取 @insert 数据
        if (isset($condition['@insert'])) {
            $this->insertData = $condition['@insert'];
            unset($condition['@insert']);
            
            // 更新 condition，移除 @insert
            $this->condition->setCondition($condition);
        }
    }

    public function getInsertData(): array
    {
        return $this->insertData;
    }

    public function hasInsertData(): bool
    {
        return !empty($this->insertData);
    }

    protected function buildModel()
    {
        // 无需构建模型
    }
} 