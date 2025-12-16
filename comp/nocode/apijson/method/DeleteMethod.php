<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Exception\ApiException;
use Phalcon\Di;
use Imee\Comp\Nocode\Apijson\Model\MysqlQuery;
use Throwable;

class DeleteMethod extends AbstractMethod
{
    protected function validateCondition(): bool
    {
        return $this->method === 'DELETE';
    }

    protected function process()
    {
        $db = Di::getDefault()->get($this->tableEntity->getDbServiceName());
        
        try {
            $db->begin();
            $result = $this->recursiveDelete($this->tableEntity);
            $db->commit();
            return $result;
        } catch (Throwable $e) {
            $db->rollback();
            throw new ApiException(ApiException::MSG_ERROR, "删除失败: " . $e->getMessage());
        }
    }

    private function recursiveDelete(TableEntity $tableEntity): array
    {
        $childTableEntities = [];
        foreach ($tableEntity->getContent() as $key => $value) {
            if (is_string($key) && ctype_upper($key[0]) && is_array($value)) {
                $childTableEntities[$key] = new TableEntity($key, [$key => $value], [], []);
            }
        }

        // 为当前操作创建独立的查询对象
        $query = new MysqlQuery($tableEntity);

        if (!empty($childTableEntities)) {
            if (empty($query->getWhereKeys())) {
                 throw new ApiException(ApiException::MSG_ERROR, '嵌套 DELETE 操作的主表必须包含 WHERE 条件');
            }
            $query->setColumns($query->getPrimaryKey());
            $mainRecords = $query->all();
            $mainIds = array_column($mainRecords, $query->getPrimaryKey());

            if (!empty($mainIds)) {
                foreach ($childTableEntities as $tableName => $childEntity) {
                    $foreignKey = strtolower($tableEntity->getRealTableName()) . '_id';
                    
                    // 创建临时的 DeleteMethod 实例以进行递归，并确保它在自己的上下文中运行
                    $childEntity->getConditionEntity()
                        ->addQueryWhere($foreignKey, "`{$foreignKey}` IN (" . implode(',', array_fill(0, count($mainIds), '?')) . ")", $mainIds);
                    
                    $tempDeleteMethod = new self($childEntity);
                    $tempDeleteMethod->recursiveDelete($childEntity);
                }
            }
        }

        if (empty($query->getWhereKeys())) {
            throw new ApiException(ApiException::MSG_ERROR, 'DELETE 操作必须包含 WHERE 条件');
        }
        
        $query->delete();
        $count = $query->getRowCount();

        return ['ok' => true, 'count' => $count];
    }
}