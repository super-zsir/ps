<?php

namespace Imee\Comp\Nocode\Service\Context;

/**
 * BaseContext
 * 上下文基类
 */
abstract class BaseContext
{
    /**
     * 初始化数据
     * @param  array  $conditions
     */
    public function __construct(array $conditions)
    {
        if (count($conditions) == 0) {
            return;
        }
        $this->initial($conditions);
    }

    private function initial(array $conditions)
    {
        foreach ($conditions as $key => $value) {
            $attribute = camel_case($key);
            if (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            }
        }
    }

    /**
     * 数据初始化或新增赋值
     * @param  array  $conditions
     */
    public function setParams(array $conditions)
    {
        $this->initial($conditions);
    }

    /**
     * 结构体转化成数组
     * @return array
     */
    public function toArray()
    {
        $varList = get_object_vars($this);
        return $this->formatLevelVar($varList);
    }

    /**
     * format结构体（暂不考虑递归）
     */
    private function formatLevelVar($varList)
    {
        $formatVars = [];
        if (empty($varList)) {
            return $formatVars;
        }

        foreach ($varList as $key => $value) {
            $formatVars[snake_case($key)] = $value;
        }

        return $formatVars;
    }

    public function __get($propertyName)
    {
        return $this->$propertyName;
    }

    public function __isset($propertyName)
    {
        $attribute = $this->$propertyName;

        return !empty($attribute);
    }
}

