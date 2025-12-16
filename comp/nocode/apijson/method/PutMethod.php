<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Comp\Nocode\Apijson\Model\MysqlQuery;
use Imee\Comp\Nocode\Apijson\Parse\Handle;
use Imee\Exception\ApiException;
use Phalcon\Di;
use Throwable;

class PutMethod extends AbstractMethod
{
    protected function validateCondition(): bool
    {
        return $this->method === 'PUT';
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
                $result = $this->handleBatchUpdate();
            } else {
                $result = $this->recursiveUpdate($this->tableEntity);
            }
            
            $db->commit();
            return $result;
        } catch (Throwable $e) {
            $db->rollback();
            // 保持异常信息的具体性，但使用统一的错误码
            throw new ApiException(ApiException::MSG_ERROR, "更新失败: " . $e->getMessage());
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
     * 处理批量更新
     * @return array
     */
    private function handleBatchUpdate(): array
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
        $totalCount = 0;
        
        // 分批处理，每批100条
        $chunks = array_chunk($batchData, 100);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkResults = [];
            $chunkCount = 0;
            
            foreach ($chunk as $data) {
                // 创建单个 TableEntity 进行更新
                $singleEntity = new TableEntity(
                    $this->tableEntity->getTableName(),
                    $data,
                    [],
                    []
                );
                
                $result = $this->recursiveUpdate($singleEntity);
                $chunkResults[] = $result;
                $chunkCount += $result['count'] ?? 0;
            }
            
            $results["batch_{$chunkIndex}"] = [
                'results' => $chunkResults,
                'count' => $chunkCount
            ];
            $totalCount += $chunkCount;
        }
        
        return [
            'count' => $totalCount,
            'batches' => count($chunks),
            'results' => $results
        ];
    }

    private function recursiveUpdate(TableEntity $tableEntity): array
    {
        $childTableEntities = [];
        $content = $tableEntity->getContent();

        foreach ($content as $key => $value) {
            if ($this->isChildTableKey($key, $value)) {
                $childTableEntities[$key] = new TableEntity($key, [$key => $value], [], []);
            }
        }

        // 拆分条件字段与更新字段，并重建 Condition
        [$conditionOnly, $updateData] = $this->splitUpdatePayload($tableEntity);
        $conditionEntity = $tableEntity->getConditionEntity();
        $conditionEntity->setCondition($conditionOnly);

        // 根据更新语义重建 WHERE
        $handle = new Handle($conditionEntity, $tableEntity);
        $handle->buildUpdate();

        if (empty($conditionEntity->getQueryWhere())) {
            throw new ApiException(ApiException::MSG_ERROR, 'PUT 操作必须至少包含一个 WHERE 条件（如主键或 @where）');
        }
        
        // 为当前操作创建独立的查询对象
        $query = new MysqlQuery($tableEntity);
        
        $whereKeys = $query->getWhereKeys();
        foreach ($whereKeys as $key) {
            unset($updateData[$key]);
        }
        
        $count = 0;
        if (!empty($updateData)) {
            $query->update($updateData);
            $count = $query->getRowCount();
        }

        if (!empty($childTableEntities)) {
            if (empty($whereKeys)) {
                throw new ApiException(ApiException::MSG_ERROR, '嵌套 PUT 操作的主表必须包含 WHERE 条件');
            }
            $query->setColumns($query->getPrimaryKey());
            $mainRecords = $query->all();
            $mainIds = array_column($mainRecords, $query->getPrimaryKey());

            if (!empty($mainIds)) {
                foreach ($childTableEntities as $tableName => $childEntity) {
                    $foreignKey = strtolower($tableEntity->getRealTableName()) . '_id';
                    $childContent = $childEntity->getContent();
                    $childPrimaryKey = $childEntity->getPrimaryKey();

                    if (isset($childContent[$childPrimaryKey])) { // 更新子表
                        // 创建临时的 PutMethod 实例以进行递归，并确保它在自己的上下文中运行
                        $tempPutMethod = new self($childEntity);
                        $tempPutMethod->recursiveUpdate($childEntity);
                    } else { // 新增子表
                        $childInsertData = $childContent;
                        // 关联主表ID (一对多场景)
                        $childInsertData[$foreignKey] = current($mainIds);
                        // 为新增操作创建独立的查询对象
                        $childInsertQuery = new MysqlQuery($childEntity);
                        $childInsertQuery->insert($childInsertData);
                    }
                }
            }
        }

        $finalResult = ['ok' => true, 'count' => $count];
        
        // 处理 @update 语法
        $updateResult = $this->handleUpdateSyntax($tableEntity);
        if (!empty($updateResult)) {
            $finalResult = array_merge($finalResult, $updateResult);
        }

        return $finalResult;
    }

    /**
     * 处理 @update 语法
     * @param TableEntity $tableEntity
     * @return array
     */
    private function handleUpdateSyntax(TableEntity $tableEntity): array
    {
        $content = $tableEntity->getContent();
        $updateData = [];
        
        // 提取 @update 数据
        if (isset($content['@update'])) {
            $updateData = $content['@update'];
            unset($content['@update']);
        }
        
        if (empty($updateData)) {
            return [];
        }
        
        $result = [];
        foreach ($updateData as $tableName => $data) {
            if (is_string($tableName) && ctype_upper($tableName[0])) {
                $childEntity = new TableEntity($tableName, $data, [], []);
                $childResult = $this->recursiveUpdate($childEntity);
                $result[$tableName] = $childResult;
            }
        }
        
        return $result;
    }

    /**
     * 拆分 PUT 请求中的条件字段与更新字段
     */
    private function splitUpdatePayload(TableEntity $tableEntity): array
    {
        $conditionEntity = $tableEntity->getConditionEntity();
        $originalCondition = $conditionEntity->getCondition();
        $conditionOnly = [];
        $updateData = [];

        foreach ($originalCondition as $key => $value) {
            if ($key === '@where' && is_array($value)) {
                foreach ($value as $whereKey => $whereValue) {
                    $conditionOnly[$whereKey] = $whereValue;
                }
                continue;
            }

            if ($this->isChildTableKey($key, $value)) {
                continue;
            }

            if (strpos($key, '@') === 0) {
                $conditionOnly[$key] = $value;
                continue;
            }

            if ($this->shouldTreatAsConditionField($tableEntity, $key)) {
                $conditionOnly[$key] = $value;
            } else {
                $updateData[$key] = $value;
            }
        }

        return [$conditionOnly, $updateData];
    }

    private function isChildTableKey($key, $value): bool
    {
        return is_string($key) && ctype_upper($key[0]) && is_array($value);
    }

    private function shouldTreatAsConditionField(TableEntity $tableEntity, string $key): bool
    {
        if ($this->fieldHasOperator($key)) {
            return true;
        }

        $primaryKey = $tableEntity->getPrimaryKey();
        if ($key === $primaryKey) {
            return true;
        }

        return $this->isUniqueField($tableEntity, $key);
    }

    private function fieldHasOperator(string $key): bool
    {
        return (bool)preg_match('/[!$}{%~<>|]/', $key);
    }

    private function isUniqueField(TableEntity $tableEntity, string $key): bool
    {
        foreach ($tableEntity->getUniqueIndexes() as $fields) {
            if (count($fields) === 1 && $fields[0] === $key) {
                return true;
            }
        }

        return false;
    }
}