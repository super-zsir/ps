<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Exception\ApiException;
use Phalcon\Di;
use Imee\Comp\Nocode\Apijson\Model\MysqlQuery;
use Throwable;

class ReplaceMethod extends AbstractMethod
{
    protected function validateCondition(): bool
    {
        return $this->method === 'REPLACE';
    }

    protected function process()
    {
        // 写操作使用写库
        $this->tableEntity->setDbServiceName($this->tableEntity->getWriteDbServiceName());
        $db = Di::getDefault()->get($this->tableEntity->getDbServiceName());
        
        try {
            $db->begin();
            
            // 检查是否为批量操作
            if ($this->isBatchOperation()) {
                $result = $this->handleBatchReplace();
            } else {
                $result = $this->recursiveReplace($this->tableEntity);
            }
            
            $db->commit();
            return $result;
        } catch (Throwable $e) {
            $db->rollback();
            throw new ApiException(ApiException::MSG_ERROR, "替换失败: " . $e->getMessage());
        }
    }

    /**
     * 检查是否为批量操作
     * @return bool
     */
    private function isBatchOperation(): bool
    {
        $content = $this->tableEntity->getContent();
        foreach ($content as $value) {
            if (is_array($value) && $this->isBatchArray($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断是否为批量数组
     * @param array $data
     * @return bool
     */
    private function isBatchArray(array $data): bool
    {
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
     * 处理批量替换
     * @return array
     */
    private function handleBatchReplace(): array
    {
        $content = $this->tableEntity->getContent();
        $batchData = [];
        
        // 提取批量数据
        foreach ($content as $key => $value) {
            if (is_array($value) && $this->isBatchArray($value)) {
                $batchData = $value;
                break;
            }
        }
        
        if (empty($batchData)) {
            throw new ApiException(ApiException::MSG_ERROR, '批量数据为空');
        }
        
        $results = [];
        
        // 分批处理，每批100条
        $chunks = array_chunk($batchData, 100);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkResults = [];
            
            foreach ($chunk as $data) {
                // 创建单个 TableEntity 进行替换
                $singleEntity = new TableEntity(
                    $this->tableEntity->getTableName(),
                    [$this->tableEntity->getTableName() => $data],
                    [],
                    []
                );
                
                $result = $this->recursiveReplace($singleEntity);
                $chunkResults[] = $result;
            }
            
            $results["batch_{$chunkIndex}"] = $chunkResults;
        }
        
        return [
            'count' => count($batchData),
            'batches' => count($chunks),
            'results' => $results
        ];
    }

    private function recursiveReplace(TableEntity $tableEntity, array $parentData = []): array
    {
        $content = $tableEntity->getContent();
        
        // 1. 分离当前表的数据和子表的数据
        $currentTableData = [];
        $childTableEntities = [];

        foreach ($content as $key => $value) {
            if (is_string($key) && ctype_upper($key[0])) { // 约定：大写字母开头的键是子表
                $childTableEntities[$key] = new TableEntity($key, [$key => $value], [], []);
            } else {
                if (strpos($key, '@') !== 0) { // 忽略特殊指令
                    $currentTableData[$key] = $value;
                }
            }
        }
        
        // 注入父表的外键ID
        if (!empty($parentData['id']) && !empty($parentData['foreign_key'])) {
            $currentTableData[$parentData['foreign_key']] = $parentData['id'];
        }

        if (empty($currentTableData)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有可替换的数据');
        }

        // 2. 执行 REPLACE 操作
        $query = new MysqlQuery($tableEntity);
        $id = $query->replace($currentTableData, $tableEntity->getPrimaryKey());
        
        $primaryKey = $tableEntity->getPrimaryKey();
        $result = [
            $primaryKey => $id,
            'count' => 1
        ];

        // 3. 递归处理子表
        if (!empty($childTableEntities)) {
            foreach ($childTableEntities as $tableName => $childEntity) {
                // 约定：子表的外键名为 `主表名(小写)_id`
                $foreignKey = strtolower($tableEntity->getRealTableName()) . '_id';
                $parentInfo = ['id' => $id, 'foreign_key' => $foreignKey];
                $result[$tableName] = $this->recursiveReplaceChild($childEntity, $parentInfo);
            }
        }

        return $result;
    }

    private function recursiveReplaceChild(TableEntity $tableEntity, array $parentData = []): array
    {
        $content = $tableEntity->getContent();
        
        // 注入父表的外键ID
        if (!empty($parentData['id']) && !empty($parentData['foreign_key'])) {
            $content[$parentData['foreign_key']] = $parentData['id'];
        }

        if (empty($content)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有可替换的数据');
        }

        // 执行 REPLACE 操作
        $query = new MysqlQuery($tableEntity);
        $id = $query->replace($content, $tableEntity->getPrimaryKey());
        
        $primaryKey = $tableEntity->getPrimaryKey();
        return [
            $primaryKey => $id,
            'count' => 1
        ];
    }
} 