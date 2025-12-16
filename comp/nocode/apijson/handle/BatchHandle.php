<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;

class BatchHandle extends AbstractHandle
{
    protected $batchData = [];
    protected $isBatch = false;

    public function handle()
    {
        $condition = $this->condition->getCondition();
        
        // 检查是否为批量操作
        foreach ($condition as $key => $value) {
            if (is_array($value) && $this->isBatchArray($value)) {
                $this->isBatch = true;
                $this->batchData = $value;
                unset($condition[$key]);
                
                // 更新 condition，移除批量数据
                $this->condition->setCondition($condition);
                break;
            }
        }
    }

    /**
     * 判断是否为批量数组
     * @param array $data
     * @return bool
     */
    protected function isBatchArray(array $data): bool
    {
        // 检查是否为索引数组且包含对象数据
        if (array_keys($data) !== range(0, count($data) - 1)) {
            return false;
        }

        foreach ($data as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取批量数据
     * @return array
     */
    public function getBatchData(): array
    {
        return $this->batchData;
    }

    /**
     * 是否为批量操作
     * @return bool
     */
    public function isBatch(): bool
    {
        return $this->isBatch;
    }

    /**
     * 获取批量数据数量
     * @return int
     */
    public function getBatchCount(): int
    {
        return count($this->batchData);
    }

    /**
     * 分割批量数据
     * @param int $chunkSize
     * @return array
     */
    public function splitBatchData(int $chunkSize = 100): array
    {
        return array_chunk($this->batchData, $chunkSize);
    }

    protected function buildModel()
    {
        // 无需构建模型
    }
} 