<?php

namespace Imee\Comp\Nocode\Apijson\Method;

use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Exception\ApiException;
use Phalcon\Di;
use Throwable;
use Imee\Comp\Nocode\Apijson\Model\MysqlQuery;

class PostMethod extends AbstractMethod
{
    protected function validateCondition(): bool
    {
        return $this->method === 'POST';
    }

    protected function process()
    {
        // 写操作使用写库
        $writeService = $this->tableEntity->getWriteDbServiceName();
        if (empty($writeService)) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('写库服务名未配置：表=%s, system_id=%s', $this->tableEntity->getRealTableName(), defined('SYSTEM_ID') ? SYSTEM_ID : '未定义'));
        }
        $this->tableEntity->setDbServiceName($writeService);
        try {
            $db = Di::getDefault()->get($this->tableEntity->getDbServiceName());
        } catch (Throwable $e) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('写库服务未注册：service=%s, 表=%s, 原因=%s', $writeService, $this->tableEntity->getRealTableName(), $e->getMessage()));
        }
        
        try {
            $db->begin();
            
            // 检查是否为批量操作
            if ($this->isBatchOperation()) {
                $result = $this->handleBatchInsert();
            } else {
                $result = $this->recursiveInsert($this->tableEntity);
            }
            
            $db->commit();
            return $result;
        } catch (ApiException $e) {
            $db->rollback();
            throw new ApiException(ApiException::MSG_ERROR, $e->getMsg());
        } catch (Throwable $e) {
            $db->rollback();
            throw new ApiException(ApiException::MSG_ERROR, sprintf('写入失败：表=%s, service=%s, 详情=%s', $this->tableEntity->getRealTableName(), $writeService, $e->getMessage()));
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
     * 处理批量插入
     * @return array
     */
    private function handleBatchInsert(): array
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
        $query = new MysqlQuery($this->tableEntity);
        
        // 分批处理，每批100条
        $chunks = array_chunk($batchData, 100);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkResults = [];
            
            foreach ($chunk as $data) {
                // 创建单个 TableEntity 进行插入
                $singleEntity = new TableEntity(
                    $this->tableEntity->getTableName(),
                    [$this->tableEntity->getTableName() => $data],
                    [],
                    []
                );
                
                $result = $this->recursiveInsert($singleEntity);
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

    /**
     * 处理 @insert 语法
     * @param TableEntity $tableEntity
     * @return array
     */
    private function handleInsertSyntax(TableEntity $tableEntity): array
    {
        $content = $tableEntity->getContent();
        $insertData = [];
        
        // 提取 @insert 数据
        if (isset($content['@insert'])) {
            $insertData = $content['@insert'];
            unset($content['@insert']);
        }
        
        if (empty($insertData)) {
            return [];
        }
        
        $result = [];
        foreach ($insertData as $tableName => $data) {
            if (is_string($tableName) && ctype_upper($tableName[0])) {
                $childEntity = new TableEntity($tableName, [$tableName => $data], [], []);
                $childResult = $this->recursiveInsert($childEntity);
                $result[$tableName] = $childResult;
            }
        }
        
        return $result;
    }

    /**
     * @param TableEntity $tableEntity
     * @param array $parentData
     * @return array
     * @throws ApiException
     */
    private function recursiveInsert(TableEntity $tableEntity, array $parentData = []): array
    {
        $content = $tableEntity->getContent();
        
        // 1. 分离当前表的数据和子表的数据
        $currentTableData = [];
        $childTableEntities = [];

        foreach ($content as $key => $value) {
            if (is_string($key) && ctype_upper($key[0]) && $key !== $tableEntity->getTableName()) { // 约定：大写字母开头的键是子表，但排除表名自身
                $childTableEntities[$key] = new TableEntity($key, [$key => $value], [], []);
            } elseif ($key === $tableEntity->getTableName() && is_array($value)) {
                // 如果是当前表名，则提取其内部字段，同时检查是否有嵌套的子表
                foreach ($value as $field => $fieldValue) {
                    if (strpos($field, '@') !== 0) { // 忽略特殊指令
                        if (is_string($field) && ctype_upper($field[0]) && $field !== $tableEntity->getTableName()) {
                            // 这是嵌套的子表，需要递归处理
                            $childTableEntities[$field] = new TableEntity($field, [$field => $fieldValue], [], []);
                        } else {
                            $currentTableData[$field] = $fieldValue;
                        }
                    }
                }
            } else {
                if (strpos($key, '@') !== 0) { // 忽略特殊指令
                    $currentTableData[$key] = $value;
                }
            }
        }
        
        // 2. 注入父表的外键ID
        if (!empty($parentData['id']) && !empty($parentData['foreign_key'])) {
            $currentTableData[$parentData['foreign_key']] = $parentData['id'];
        }

        // 3. 插入当前表的数据
        // 3.1 自动唯一索引检测（所有唯一索引）
        $query = new MysqlQuery($tableEntity);
        $uniqueIndexes = $tableEntity->getUniqueIndexes();
        foreach ($uniqueIndexes as $fields) {
            $check = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $currentTableData)) {
                    $check[$field] = $currentTableData[$field];
                }
            }
            // 只在请求提供了该唯一索引涉及的全部列时才进行预检
            if (count($check) === count($fields)) {
                if ($query->exists($check)) {
                    $hint = sprintf('唯一键(%s)已存在，请更换或改用PUT更新', implode(',', $fields));
                    $detail = sprintf('%s 唯一索引冲突: %s', $tableEntity->getRealTableName(), json_encode($check, JSON_UNESCAPED_UNICODE));
                    throw new ApiException(ApiException::MSG_ERROR, $detail . '；' . $hint);
                }
            }
        }
        if (empty($currentTableData)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有可插入的数据');
        }

        // 为当前操作创建独立的查询对象
        $query = new MysqlQuery($tableEntity);
        $id = $query->insert($currentTableData, $tableEntity->getPrimaryKey());
        
        $primaryKey = $tableEntity->getPrimaryKey();
        $result = [
            $primaryKey => $id,
            'count' => 1
        ];

        // 4. 递归插入子表
        if (!empty($childTableEntities)) {
            foreach ($childTableEntities as $tableName => $childEntity) {
                // 获取子表内容并处理 @foreign_key 指令
                $childContent = $childEntity->getContent();
                
                // 从子表数据中获取 @foreign_key 指令
                $foreignKey = null;
                if (isset($childContent[$tableName]) && is_array($childContent[$tableName])) {
                    $foreignKey = $childContent[$tableName]['@foreign_key'] ?? null;
                }
                $foreignKey = $foreignKey ?? strtolower($tableEntity->getRealTableName()) . '_id';
                
                // 调试信息（生产环境可注释）
                // error_log("DEBUG: 子表 {$tableName}, 指定外键: {$foreignKey}, 原始内容: " . json_encode($childContent));
                // error_log("DEBUG: 主表 {$tableEntity->getRealTableName()}, 主键ID: {$id}");
                
                // 检查是否手动指定了外键值
                $hasManualForeignKey = false;
                if (isset($childContent[$tableName]) && is_array($childContent[$tableName])) {
                    // 检查是否手动指定了外键值（包括 @foreign_key 指定的字段名）
                    $hasManualForeignKey = array_key_exists($foreignKey, $childContent[$tableName]);
                    
                    // 如果没有找到，检查是否有其他可能的外键字段
                    if (!$hasManualForeignKey) {
                        // 检查常见的父表外键命名模式
                        $possibleForeignKeys = [
                            strtolower($tableEntity->getRealTableName()) . '_id',
                            $tableEntity->getPrimaryKey(),
                            'parent_id',
                            'parent_' . $tableEntity->getPrimaryKey()
                        ];
                        
                        foreach ($possibleForeignKeys as $possibleKey) {
                            if (array_key_exists($possibleKey, $childContent[$tableName])) {
                                $hasManualForeignKey = true;
                                break;
                            }
                        }
                    }
                }
                
                // 移除 @foreign_key 指令，避免被当作字段插入
                if (isset($childContent[$tableName]) && is_array($childContent[$tableName])) {
                    unset($childContent[$tableName]['@foreign_key']);
                }
                
                // 重新创建子表实体，不包含 @foreign_key 指令
                $cleanChildEntity = new TableEntity($tableName, $childContent, [], []);
                
                // 如果没有手动指定外键值，则自动注入
                if (!$hasManualForeignKey) {
                    $parentInfo = ['id' => $id, 'foreign_key' => $foreignKey];
                    // error_log("DEBUG: 注入外键 {$foreignKey} = {$id} 到子表 {$tableName}");
                    $result[$tableName] = $this->recursiveInsert($cleanChildEntity, $parentInfo);
                } else {
                    // 子表已指定外键值，直接插入
                    // error_log("DEBUG: 子表 {$tableName} 已包含外键值，直接插入");
                    $result[$tableName] = $this->recursiveInsert($cleanChildEntity);
                }
            }
        }

        // 5. 处理 @insert 语法
        $insertResult = $this->handleInsertSyntax($tableEntity);
        if (!empty($insertResult)) {
            $result = array_merge($result, $insertResult);
        }

        return $result;
    }
}