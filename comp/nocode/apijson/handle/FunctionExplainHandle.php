<?php

namespace Imee\Comp\Nocode\Apijson\Handle;

use Imee\Exception\ApiException;

class FunctionExplainHandle extends AbstractHandle
{
    protected $keyWord = '@explain';

    public function buildModel()
    {
        $conditions = $this->condition->getCondition();
        if (!isset($conditions[$this->keyWord])) {
            return;
        }

        $value = $conditions[$this->keyWord];
        
        // 输入清理和类型检查
        $enableExplain = $this->validateExplainValue($value);
        
        if ($enableExplain) {
            // 设置执行计划模式
            $this->condition->setExplainMode(true);
        }
        
        $this->unsetKey[] = $this->keyWord;
    }
    
    /**
     * 验证 @explain 参数值
     * @param mixed $value
     * @return bool
     * @throws ApiException
     */
    private function validateExplainValue($value): bool
    {
        // 类型检查
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // 输入清理 - 去除空白字符
            $cleanedValue = trim($value);
            
            // 正则验证 - 只允许 true/false/1/0
            if (!preg_match('/^(true|false|1|0)$/i', $cleanedValue)) {
                throw new ApiException(ApiException::MSG_ERROR, '@explain value must be boolean, true, false, 1, or 0');
            }
            
            // 转换为布尔值
            return strtolower($cleanedValue) === 'true' || $cleanedValue === '1';
        }
        
        if (is_int($value)) {
            return $value === 1;
        }
        
        if (is_null($value)) {
            return false;
        }
        
        // 数组过滤 - 如果是数组，取第一个有效值
        if (is_array($value)) {
            $filteredArray = array_filter($value, function($item) {
                return is_scalar($item);
            });
            
            if (!empty($filteredArray)) {
                $firstValue = reset($filteredArray);
                return $this->validateExplainValue($firstValue);
            }
            
            return false;
        }
        
        throw new ApiException(ApiException::MSG_ERROR, '@explain value must be boolean, string, integer, or null');
    }
} 