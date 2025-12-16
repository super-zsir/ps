<?php

namespace Imee\Comp\Nocode\Apijson\Validator;

use Imee\Comp\Nocode\Apijson\Exception\ApiJsonException;

class DataValidator
{
    // 支持的字段类型
    const FIELD_TYPES = [
        'string', 'integer', 'float', 'boolean', 'array', 'object', 'null'
    ];

    // 支持的运算符
    const OPERATORS = [
        '=', '>', '<', '>=', '<=', '!=', '{}', '!{}', '$', '^', '%', '|', '@'
    ];

    // 必填字段配置
    protected $requiredFields = [];

    // 字段类型配置
    protected $fieldTypes = [];

    // 字段长度限制
    protected $fieldLengths = [];

    // 字段值范围
    protected $fieldRanges = [];

    public function __construct(array $config = [])
    {
        $this->requiredFields = $config['required_fields'] ?? [];
        $this->fieldTypes = $config['field_types'] ?? [];
        $this->fieldLengths = $config['field_lengths'] ?? [];
        $this->fieldRanges = $config['field_ranges'] ?? [];
    }

    /**
     * 验证 APIJSON 数据
     * @param array $data
     * @param string $table
     * @return bool
     * @throws ApiJsonException
     */
    public function validate(array $data, string $table): bool
    {
        // 1. 验证 JSON 结构
        $this->validateJsonStructure($data);

        // 2. 验证必填字段
        $this->validateRequiredFields($data, $table);

        // 3. 验证字段类型
        $this->validateFieldTypes($data, $table);

        // 4. 验证字段长度
        $this->validateFieldLengths($data, $table);

        // 5. 验证字段值范围
        $this->validateFieldRanges($data, $table);

        // 6. 验证特殊语法
        $this->validateSpecialSyntax($data, $table);

        return true;
    }

    /**
     * 验证 JSON 结构
     * @param array $data
     * @throws ApiJsonException
     */
    protected function validateJsonStructure(array $data): void
    {
        if (empty($data)) {
            throw ApiJsonException::invalidSyntax('JSON 数据不能为空');
        }

        foreach ($data as $tableName => $tableData) {
            if (!is_string($tableName)) {
                throw ApiJsonException::invalidSyntax('表名必须是字符串');
            }

            if (!is_array($tableData)) {
                throw ApiJsonException::invalidSyntax("表 {$tableName} 的数据必须是对象");
            }

            // 验证表名格式
            // [] 是特殊的顶级键，用于查询数组，不是表名
            if ($tableName === '[]') {
                continue; // 跳过对 [] 的验证
            }
            
            if (!preg_match('/^[A-Z][a-zA-Z0-9_]*(\[\])?$/', $tableName)) {
                throw ApiJsonException::invalidSyntax("无效的表名格式: {$tableName}");
            }
        }
    }

    /**
     * 验证必填字段
     * @param array $data
     * @param string $table
     * @throws ApiJsonException
     */
    protected function validateRequiredFields(array $data, string $table): void
    {
        if (!isset($this->requiredFields[$table])) {
            return;
        }

        $requiredFields = $this->requiredFields[$table];
        $tableData = $data[$table] ?? [];

        foreach ($requiredFields as $field) {
            if (!isset($tableData[$field])) {
                throw ApiJsonException::missingRequiredField($field, $table);
            }
        }
    }

    /**
     * 验证字段类型
     * @param array $data
     * @param string $table
     * @throws ApiJsonException
     */
    protected function validateFieldTypes(array $data, string $table): void
    {
        if (!isset($this->fieldTypes[$table])) {
            return;
        }

        $fieldTypes = $this->fieldTypes[$table];
        $tableData = $data[$table] ?? [];

        foreach ($tableData as $field => $value) {
            if (strpos($field, '@') === 0) {
                continue; // 跳过特殊指令
            }

            if (isset($fieldTypes[$field])) {
                $expectedType = $fieldTypes[$field];
                $actualType = $this->getValueType($value);

                if ($actualType !== $expectedType) {
                    throw ApiJsonException::invalidDataType($field, $expectedType, $value);
                }
            }
        }
    }

    /**
     * 验证字段长度
     * @param array $data
     * @param string $table
     * @throws ApiJsonException
     */
    protected function validateFieldLengths(array $data, string $table): void
    {
        if (!isset($this->fieldLengths[$table])) {
            return;
        }

        $fieldLengths = $this->fieldLengths[$table];
        $tableData = $data[$table] ?? [];

        foreach ($tableData as $field => $value) {
            if (strpos($field, '@') === 0) {
                continue; // 跳过特殊指令
            }

            if (isset($fieldLengths[$field])) {
                $maxLength = $fieldLengths[$field];
                $actualLength = is_string($value) ? mb_strlen($value) : strlen((string)$value);

                if ($actualLength > $maxLength) {
                    throw ApiJsonException::invalidDataType(
                        $field,
                        "最大长度 {$maxLength}",
                        "实际长度 {$actualLength}"
                    );
                }
            }
        }
    }

    /**
     * 验证字段值范围
     * @param array $data
     * @param string $table
     * @throws ApiJsonException
     */
    protected function validateFieldRanges(array $data, string $table): void
    {
        if (!isset($this->fieldRanges[$table])) {
            return;
        }

        $fieldRanges = $this->fieldRanges[$table];
        $tableData = $data[$table] ?? [];

        foreach ($tableData as $field => $value) {
            if (strpos($field, '@') === 0) {
                continue; // 跳过特殊指令
            }

            if (isset($fieldRanges[$field])) {
                $range = $fieldRanges[$field];
                $min = $range['min'] ?? null;
                $max = $range['max'] ?? null;

                if ($min !== null && $value < $min) {
                    throw ApiJsonException::invalidDataType(
                        $field,
                        "最小值 {$min}",
                        $value
                    );
                }

                if ($max !== null && $value > $max) {
                    throw ApiJsonException::invalidDataType(
                        $field,
                        "最大值 {$max}",
                        $value
                    );
                }
            }
        }
    }

    /**
     * 验证特殊语法
     * @param array $data
     * @param string $table
     * @throws ApiJsonException
     */
    protected function validateSpecialSyntax(array $data, string $table): void
    {
        $tableData = $data[$table] ?? [];

        foreach ($tableData as $field => $value) {
            // 验证操作符
            $this->validateOperator($field, $value);

            // 验证引用语法
            if (strpos($field, '@') !== false && 
                $field !== '@' && 
                $field !== '@insert' && 
                $field !== '@update' && 
                $field !== '@replace' && 
                $field !== '@column' && 
                $field !== '@order' && 
                $field !== '@limit' && 
                $field !== '@offset' && 
                $field !== '@group' && 
                $field !== '@having' &&
                $field !== '@count' &&
                $field !== '@sum' &&
                $field !== '@distinct' &&
                $field !== '@alias' &&
                $field !== '@explain' &&
                $field !== '@function' &&
                $field !== '@cache') {
                $this->validateReference($field, $value);
            }
        }
    }

    /**
     * 验证操作符
     * @param string $field
     * @param mixed $value
     * @throws ApiJsonException
     */
    protected function validateOperator(string $field, $value): void
    {
        // 提取操作符
        if (preg_match('/^([a-zA-Z_]\w*)([!$}{%~<>]*)$/', $field, $matches)) {
            $operator = $matches[2];
            
            if (!empty($operator) && !in_array($operator, self::OPERATORS)) {
                throw ApiJsonException::invalidOperator($operator);
            }
        }
    }

    /**
     * 验证引用语法
     * @param string $field
     * @param mixed $value
     * @throws ApiJsonException
     */
    protected function validateReference(string $field, $value): void
    {
        if (!is_string($value)) {
            throw ApiJsonException::invalidReference($field);
        }

        // 允许相对引用: "/field"（官方语法，用于引用当前父对象字段）
        if (preg_match('/^\/[a-zA-Z_]\w*$/', $value)) {
            return;
        }

        // 允许可选前导斜杠、可选 []："/TableName/field" 或 "TableName/field" 或 "TableName[]/field"
        if (!preg_match('/^\/?[A-Za-z][a-zA-Z0-9_]*(\[\])?\/[a-zA-Z_]\w*$/', $value)) {
            throw ApiJsonException::invalidReference($field);
        }
    }

    /**
     * 获取值的类型
     * @param mixed $value
     * @return string
     */
    protected function getValueType($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'object';
        }

        return 'unknown';
    }

    /**
     * 设置必填字段
     * @param string $table
     * @param array $fields
     */
    public function setRequiredFields(string $table, array $fields): void
    {
        $this->requiredFields[$table] = $fields;
    }

    /**
     * 设置字段类型
     * @param string $table
     * @param array $types
     */
    public function setFieldTypes(string $table, array $types): void
    {
        $this->fieldTypes[$table] = $types;
    }

    /**
     * 设置字段长度限制
     * @param string $table
     * @param array $lengths
     */
    public function setFieldLengths(string $table, array $lengths): void
    {
        $this->fieldLengths[$table] = $lengths;
    }

    /**
     * 设置字段值范围
     * @param string $table
     * @param array $ranges
     */
    public function setFieldRanges(string $table, array $ranges): void
    {
        $this->fieldRanges[$table] = $ranges;
    }
} 