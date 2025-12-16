<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionCombineHandle extends AbstractHandle
{
    public function handle()
    {
        $condition = $this->condition->getCondition();
        foreach ($condition as $key => $value) {
            if (strpos($key, '@') === false) {
                continue;
            }

            if (!is_string($value) || strpos($value, '/') === false) {
                continue;
            }

            // 解析 JOIN 表达式, e.g., "user_id@<": "/User/id"
            if (!preg_match('/^([a-zA-Z_]\w*)(@|@<|@>)$/', $key, $keyMatches)) {
                continue;
            }
            
            $localKey = $keyMatches[1];
            $joinSymbol = $keyMatches[2];

            list($remoteTablePath, $remoteKey) = explode('/', trim($value, '/'));

            if (empty($remoteTablePath) || empty($remoteKey)) {
                throw new ApiException(ApiException::MSG_ERROR, "无效的 JOIN 表达式: {$value}");
            }
            
            // 约定：JOIN 的表名可以是别名，例如 "User AS u"
            $remoteTableParts = preg_split('/\s+AS\s+/i', $remoteTablePath);
            $remoteTable = $remoteTableParts[0];
            $remoteAlias = $remoteTableParts[1] ?? $remoteTable;

            $joinType = 'INNER';
            if ($joinSymbol === '@<') {
                $joinType = 'LEFT';
            } elseif ($joinSymbol === '@>') {
                $joinType = 'RIGHT';
            }

            // 获取当前主查询的表名 (需要 TableEntity 支持)
            $localTable = $this->condition->getTableName();
            
            $onClause = "`{$remoteAlias}`.`{$remoteKey}` = `{$localTable}`.`{$localKey}`";
            
            $this->condition->addJoin($joinType, $remoteTablePath, $onClause);

            // 处理完后从条件中移除，避免干扰 WHERE 解析
            $this->unsetKey[] = $key;
        }
    }
    
    protected function buildModel()
    {
        // 无需构建模型
    }
}