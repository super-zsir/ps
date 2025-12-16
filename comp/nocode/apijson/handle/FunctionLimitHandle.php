<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;
use Imee\Comp\Nocode\Apijson\Utils\Logger;
use Imee\Comp\Nocode\Apijson\Entity\TableEntity;

class FunctionLimitHandle extends AbstractHandle
{
    protected $keyWord = '@limit';

    protected function buildModel()
    {
        $conditions = $this->condition->getCondition();
        
        // 如果没有设置 @limit，检查是否需要优化
        if (!isset($conditions[$this->keyWord])) {
            $this->optimizeLimitForUniqueIndex();
            return;
        }

        $value = $conditions[$this->keyWord];
        if (!is_numeric($value) || $value < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '@limit value must be integer that egt 1');
        }

        $this->condition->setLimit((int)$value);
        $this->unsetKey[] = $this->keyWord;
    }

    /**
     * 优化 limit：如果关联查询的字段是主键或唯一索引，且没有带@limit参数，则移除默认的limit限制
     */
    protected function optimizeLimitForUniqueIndex()
    {
        try {
            // 获取表名
            $tableName = $this->condition->getTableName();
            
            // 检查是否有引用查询
            $hasReference = false;
            $referenceField = '';
            $conditions = $this->condition->getCondition();
            
            foreach ($conditions as $key => $value) {
                if (substr($key, -1) === '@' && is_string($value)) {
                    $hasReference = true;
                    $referenceField = substr($key, 0, -1); // 去掉 @ 后缀
                    break;
                }
            }
            
            if (!$hasReference) {
                return; // 没有引用查询，不需要优化
            }
            
            // 仍采用静态判定（方法内部自取 table_config）
            if (TableEntity::isPrimaryKeyOrUniqueIndexFromConfig($tableName, $referenceField)) {
                // 如果是主键或唯一索引，移除默认的 limit 限制
                $this->condition->setLimit(0); // 设置为0表示无限制
                Logger::info("FunctionLimitHandle - Optimized limit for unique index field: {$referenceField} in table: {$tableName}");
            }
            
        } catch (\Exception $e) {
            Logger::warning("FunctionLimitHandle - Failed to optimize limit: " . $e->getMessage());
        }
    }

    /**
     * 检查字段是否是主键或唯一索引
     * @param string $fieldName
     * @param array $tableConfig
     * @return bool
     */
    // 判定逻辑已统一迁移至 TableEntity::isPrimaryKeyOrUniqueIndexFromConfig
}