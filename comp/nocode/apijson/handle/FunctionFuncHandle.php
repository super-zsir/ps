<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionFuncHandle extends AbstractHandle
{
    const FUNC_KEY = '@func';

    /**
     * @var array 定义允许被调用的安全函数白名单
     */
    protected $allowedFunctions = [
        'NOW', 'COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 'CONCAT'
    ];

    public function handle()
    {
        $condition = $this->condition->getCondition();
        if (!isset($condition[self::FUNC_KEY])) {
            return;
        }

        $funcs = $condition[self::FUNC_KEY];
        if (!is_array($funcs)) {
            $this->unsetKey[] = self::FUNC_KEY;
            return;
        }

        $columns = $this->condition->getColumn();
        if ($columns === '*') {
            $columns = [];
        } else {
            $columns = array_map('trim', explode(',', $columns));
        }

        foreach ($funcs as $alias => $expression) {
            if (!is_string($alias) || !is_string($expression)) {
                continue;
            }
            
            // 解析函数表达式, e.g., "CONCAT(name, '(', id, ')')"
            if (!preg_match('/^([a-zA-Z_]\w*)\((.*)\)$/', $expression, $matches)) {
                throw new ApiException(ApiException::MSG_ERROR, "无效的 @func 表达式: {$expression}");
            }
            
            $funcName = strtoupper($matches[1]);
            $rawParams = trim($matches[2]);

            // 安全校验：函数名必须在白名单内
            if (!in_array($funcName, $this->allowedFunctions)) {
                throw new ApiException(ApiException::MSG_ERROR, "禁止调用不安全的函数: {$funcName}");
            }

            // 处理参数：参数可以是字段名、常量字符串或数字
            $params = preg_split('/,\s*/', $rawParams);
            $processedParams = [];
            foreach ($params as $param) {
                if (preg_match("/^'[^']*'$/", $param) || is_numeric($param)) {
                    // 常量字符串或数字
                    $processedParams[] = $param;
                } elseif (preg_match('/^[a-zA-Z_]\w*$/', $param)) {
                    // 字段名, 添加反引号保护
                    $processedParams[] = "`{$param}`";
                } else {
                    throw new ApiException(ApiException::MSG_ERROR, "无效的 @func 参数: {$param}");
                }
            }
            $columns[] = "{$funcName}(" . implode(', ', $processedParams) . ") AS `{$alias}`";
        }

        $this->condition->setColumn(implode(', ', $columns));
        $this->unsetKey[] = self::FUNC_KEY;
    }
    
    protected function buildModel()
    {
        // 该 Handle 无需构建模型
    }
} 