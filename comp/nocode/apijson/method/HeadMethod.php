<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Parse\Handle;

class HeadMethod extends AbstractMethod
{
    protected function validateCondition(): bool
    {
        return $this->method == 'HEAD';
    }

    protected function process()
    {
        try {
            // 设置读取数据库服务
            $this->tableEntity->setDbServiceName($this->tableEntity->getReadDbServiceName());
            
            $handle = new Handle($this->tableEntity->getConditionEntity(), $this->tableEntity);
            $handle->buildQuery();

            // HEAD 请求只返回记录数量，不返回具体数据
            $count = $this->query->count();

            return [
                'count' => $count
            ];
        } catch (\Exception $e) {
            error_log("HeadMethod process - Table: {$this->tableName}, Exception: " . $e->getMessage());
            error_log("HeadMethod process - Table: {$this->tableName}, Exception trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}