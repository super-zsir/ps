<?php

namespace Imee\Comp\Nocode\Apijson\Exception;

use Exception;

class ApiJsonException extends Exception
{
    // 错误码定义
    const ERROR_INVALID_SYNTAX = 1001;
    const ERROR_MISSING_REQUIRED_FIELD = 1002;
    const ERROR_UNIQUE_INDEX_CONFLICT = 1003;
    const ERROR_FOREIGN_KEY_CONSTRAINT = 1004;
    const ERROR_INVALID_OPERATOR = 1005;
    const ERROR_INVALID_REFERENCE = 1006;
    const ERROR_TRANSACTION_FAILED = 1007;
    const ERROR_DATABASE_CONNECTION = 1008;
    const ERROR_PERMISSION_DENIED = 1009;
    const ERROR_INVALID_DATA_TYPE = 1010;

    protected $errorCode;
    protected $context;

    public function __construct(string $message, int $errorCode = 0, array $context = [], int $code = 0, Exception $previous = null)
    {
        $this->errorCode = $errorCode;
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error' => true,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }

    public static function invalidSyntax(string $syntax, array $context = []): self
    {
        return new self(
            "无效的 APIJSON 语法: {$syntax}",
            self::ERROR_INVALID_SYNTAX,
            array_merge($context, ['syntax' => $syntax])
        );
    }

    public static function missingRequiredField(string $field, string $table, array $context = []): self
    {
        return new self(
            "必填字段缺失: {$table}.{$field}",
            self::ERROR_MISSING_REQUIRED_FIELD,
            array_merge($context, ['field' => $field, 'table' => $table])
        );
    }

    public static function uniqueIndexConflict(string $table, array $fields, array $context = []): self
    {
        return new self(
            "{$table} 唯一索引冲突: " . json_encode($fields),
            self::ERROR_UNIQUE_INDEX_CONFLICT,
            array_merge($context, ['table' => $table, 'fields' => $fields])
        );
    }

    public static function foreignKeyConstraint(string $table, string $field, $value, array $context = []): self
    {
        return new self(
            "外键约束失败: {$table}.{$field} = {$value} 不存在",
            self::ERROR_FOREIGN_KEY_CONSTRAINT,
            array_merge($context, ['table' => $table, 'field' => $field, 'value' => $value])
        );
    }

    public static function invalidOperator(string $operator, array $context = []): self
    {
        return new self(
            "无效的操作符: {$operator}",
            self::ERROR_INVALID_OPERATOR,
            array_merge($context, ['operator' => $operator])
        );
    }

    public static function invalidReference(string $reference, array $context = []): self
    {
        return new self(
            "无效的引用: {$reference}",
            self::ERROR_INVALID_REFERENCE,
            array_merge($context, ['reference' => $reference])
        );
    }

    public static function transactionFailed(string $operation, array $context = []): self
    {
        return new self(
            "事务失败: {$operation}",
            self::ERROR_TRANSACTION_FAILED,
            array_merge($context, ['operation' => $operation])
        );
    }

    public static function databaseConnection(string $message, array $context = []): self
    {
        return new self(
            "数据库连接失败: {$message}",
            self::ERROR_DATABASE_CONNECTION,
            array_merge($context, ['db_message' => $message])
        );
    }

    public static function permissionDenied(string $operation, array $context = []): self
    {
        return new self(
            "权限不足: {$operation}",
            self::ERROR_PERMISSION_DENIED,
            array_merge($context, ['operation' => $operation])
        );
    }

    public static function invalidDataType(string $field, string $expectedType, $actualValue, array $context = []): self
    {
        return new self(
            "数据类型错误: {$field} 期望 {$expectedType}，实际值: " . json_encode($actualValue),
            self::ERROR_INVALID_DATA_TYPE,
            array_merge($context, ['field' => $field, 'expected_type' => $expectedType, 'actual_value' => $actualValue])
        );
    }
} 