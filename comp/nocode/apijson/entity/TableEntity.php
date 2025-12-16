<?php

namespace Imee\Comp\Nocode\Apijson\Entity;

use Imee\Exception\ApiException;
use Phalcon\Di;
use Phalcon\Db;
use Imee\Comp\Nocode\Apijson\Replace\QuoteReplace;
use Imee\Comp\Nocode\Models\Cms\NocodeModelConfig;

class TableEntity
{
    /** @var ConditionEntity $ConditionEntity */
    protected $conditionEntity;

    /** @var string $dbServiceName db服务名 */
    protected $dbServiceName;

    /** @var string $realTableName 真实表名 */
    protected $realTableName;

    /** @var array $content 表名对应的数据 */
    protected $content;

    /**
     * @param string $tableName 表名
     */
    protected $tableName;

    /**
     * @param array $jsonContent json源数据
     */
    protected $jsonContent;

    protected $globalArgs;
    protected $extendData;
    protected $primaryKey = 'id'; // 默认主键
    protected $allowedFields = [];
    protected $forbiddenFields = [];

    /**
     * 唯一索引缓存
     * @var array
     */
    protected $uniqueIndexes = [];

    /**
     * 表配置缓存（静态缓存，跨实例共享）
     * @var array
     */
    protected static $tableConfigCache = [];

    /**
     * 驼峰转下划线
     */
    protected function camelToUnderscore(string $input): string
    {
        $output = preg_replace('/([A-Z])/', '_$1', $input);
        $output = ltrim($output, '_');
        return strtolower($output);
    }

    /**
     * 是否 count 查询
     */
    protected $isCountQuery = false;

    public function __construct(string $tableName, array $condition, array $globalArgs = [], array $extendData = [])
    {
        $this->tableName = $tableName;
        $this->jsonContent = $condition; // 只用于兼容老接口，实际用不到
        $this->globalArgs = $globalArgs;
        $this->extendData = $extendData;

        // 1. 先去除 []
        $sanitizeTableName = str_replace('[]', '', $this->tableName);
        // 2. 如果是 _count 结尾，自动识别为 count 查询，去掉 _count
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
            $this->isCountQuery = true;
        }
        // 调试信息：记录表名处理
        error_log("TableEntity __construct - Original tableName: {$this->tableName}, Sanitized: {$sanitizeTableName}, isCountQuery: " . ($this->isCountQuery ? 'true' : 'false'));

        // 获取配置信息
        $config = $this->getTableConfig($sanitizeTableName);
        // 默认使用读库，后续根据操作类型动态调整
        $this->dbServiceName = $this->resolveDbServiceName($sanitizeTableName, 'read');
        $this->realTableName = $config['table'] ?? $this->camelToUnderscore($sanitizeTableName);
        $this->content = is_array($condition) ? $condition : [];
        $this->detectPrimaryKey();
        $this->parseConditionEntity($condition);
        // 主动执行一次 QuoteReplace 替换
        $qr = new QuoteReplace($this->conditionEntity);
        $qr->handle();
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getRealTableName(): string
    {
        return $this->realTableName;
    }

    public function getDbServiceName(): string
    {
        return $this->dbServiceName;
    }

    public function setDbServiceName(string $dbServiceName): void
    {
        $this->dbServiceName = $dbServiceName;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getConditionEntity(): ConditionEntity
    {
        return $this->conditionEntity;
    }

    protected function getContentByTableName(): array
    {
        $content = $this->jsonContent[$this->tableName] ?? [];
        if (isset($content[$this->realTableName])) {
            $content = $content[$this->realTableName];
        }
        return is_array($content) ? $content : [];
    }

    protected function parseConditionEntity(array $condition)
    {
        $entity = new ConditionEntity(
            array_merge($this->globalArgs, $condition),
            $this->extendData,
            $this->realTableName
        );
        $this->conditionEntity = $entity;
    }

    /**
     * 自动检测主键字段
     */
    protected function detectPrimaryKey()
    {
        // 从 table_config 读取主键信息，禁止直连数据库
        $sanitizeTableName = str_replace('[]', '', $this->tableName);
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
        }

        $config = $this->getTableConfig($sanitizeTableName);
        $tableConfig = [];
        if (!empty($config['table_config'])) {
            $decoded = json_decode($config['table_config'], true);
            if (is_array($decoded)) {
                $tableConfig = $decoded;
            }
        }

        // 优先使用 table_config.pk
        if (!empty($tableConfig['pk']) && is_string($tableConfig['pk'])) {
            $this->primaryKey = $tableConfig['pk'];
            return;
        }

        // 其次尝试 indexes.PRIMARY.columns[0].name
        if (!empty($tableConfig['indexes'])
            && !empty($tableConfig['indexes']['PRIMARY'])
            && !empty($tableConfig['indexes']['PRIMARY']['columns'])
            && !empty($tableConfig['indexes']['PRIMARY']['columns'][0]['name'])) {
            $this->primaryKey = (string)$tableConfig['indexes']['PRIMARY']['columns'][0]['name'];
            return;
        }

        // 若均不存在，保持默认 id
    }

    /**
     * 自动检测唯一索引（不含主键）
     */
    protected function detectUniqueIndexes()
    {
        // 从 table_config.indexes 读取唯一索引（支持单列与多列，且不含 PRIMARY）
        $sanitizeTableName = str_replace('[]', '', $this->tableName);
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
        }

        $config = $this->getTableConfig($sanitizeTableName);
        $tableConfig = [];
        if (!empty($config['table_config'])) {
            $decoded = json_decode($config['table_config'], true);
            if (is_array($decoded)) {
                $tableConfig = $decoded;
            }
        }

        $this->uniqueIndexes = [];
        if (!empty($tableConfig['indexes']) && is_array($tableConfig['indexes'])) {
            foreach ($tableConfig['indexes'] as $indexName => $indexConfig) {
                if ($indexName === 'PRIMARY') {
                    continue; // 跳过主键
                }
                $isUnique = isset($indexConfig['is_unique']) && $indexConfig['is_unique'] === true;
                $columns = $indexConfig['columns'] ?? [];
                if ($isUnique && is_array($columns) && count($columns) >= 1) {
                    $colNames = [];
                    foreach ($columns as $col) {
                        $name = $col['name'] ?? '';
                        if ($name !== '') {
                            $colNames[] = $name;
                        }
                    }
                    if (!empty($colNames)) {
                        $this->uniqueIndexes[] = $colNames; // 支持多列唯一
                    }
                }
            }
        }
        // 若无配置，保持空数组
    }

    /**
     * 获取所有唯一索引（不含主键）
     */
    public function getUniqueIndexes(): array
    {
        if (empty($this->uniqueIndexes)) {
            $this->detectUniqueIndexes();
        }
        return $this->uniqueIndexes;
    }

    /**
     * 获取主键字段名
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    // 保留静态判定为推荐调用方式

    public function setAllowedFields(array $fields)
    {
        $this->allowedFields = $fields;
    }

    public function setForbiddenFields(array $fields)
    {
        $this->forbiddenFields = $fields;
    }

    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    public function getForbiddenFields(): array
    {
        return $this->forbiddenFields;
    }

    /**
     * 过滤写入/更新数据字段
     */
    public function filterFields(array $data): array
    {
        // 先处理白名单
        if (!empty($this->allowedFields)) {
            $data = array_intersect_key($data, array_flip($this->allowedFields));
        }
        // 再处理黑名单
        if (!empty($this->forbiddenFields)) {
            $data = array_diff_key($data, array_flip($this->forbiddenFields));
        }
        return $data;
    }

    /**
     * 过滤查询字段
     * @param array|string $columns
     * @return array|string
     */
    public function filterColumns($columns)
    {
        if (is_string($columns)) {
            // 智能分割：如果包含函数调用，不能简单按逗号分割
            if ($this->containsFunctionCalls($columns)) {
                // 包含函数调用时，跳过字段过滤，直接返回
                return $columns;
            }
            $columns = array_map('trim', explode(',', $columns));
        }
        
        if (!empty($this->allowedFields)) {
            $columns = array_intersect($columns, $this->allowedFields);
        }
        if (!empty($this->forbiddenFields)) {
            $columns = array_diff($columns, $this->forbiddenFields);
        }
        return $columns;
    }

    /**
     * 检测字符串中是否包含函数调用
     * @param string $columns
     * @return bool
     */
    private function containsFunctionCalls(string $columns): bool
    {
        // 检测常见的函数调用模式
        return preg_match('/[A-Z]+\s*\(/i', $columns);
    }

    /**
     * 根据表名获取dbServiceName，支持主从分离
     * @param string $tableName 表名
     * @param string $operation 操作类型：read/write/transaction
     * @return string 数据库服务名
     */
    protected function resolveDbServiceName($tableName, $operation = 'read')
    {
        try {
            // 统一去除数组/统计后缀，保证查配置使用规范表名
            $tableName = str_replace('[]', '', (string)$tableName);
            if (preg_match('/^(.*)_count$/', $tableName, $m)) {
                $tableName = $m[1];
            }
            // 通过表名查询 NocodeModelConfig 获取配置
            $config = $this->getTableConfig($tableName);
            
            // 根据操作类型选择数据库
            if ($operation === 'read') {
                $dbServiceName = $config['slave'] ?? $config['master'] ?? '';
            } else {
                $dbServiceName = $config['master'] ?? '';
            }
            
            // 注：为降低对运行时容器的强依赖，这里不再从 Di 读取全局 database 配置做二次校验
            // 只依赖 NocodeModelConfig 的 master/slave 配置选择 dbServiceName
            
            return $dbServiceName;
        } catch (\Exception $e) {
            // 如果查询配置失败，直接报错
            throw new ApiException(ApiException::MSG_ERROR, "解析数据库服务名失败: " . $e->getMessage());
        }
    }

    /**
     * 获取表配置信息
     * @param string $tableName
     * @return array
     */
    protected function getTableConfig(string $tableName): array
    {
        // 统一去除数组/统计后缀，保证查配置使用规范表名
        $tableName = str_replace('[]', '', (string)$tableName);
        if (preg_match('/^(.*)_count$/', $tableName, $m)) {
            $tableName = $m[1];
        }
        // 生成缓存键
        $cacheKey = $tableName . '_' . SYSTEM_ID;
        
        // 检查缓存
        if (isset(self::$tableConfigCache[$cacheKey])) {
            return self::$tableConfigCache[$cacheKey];
        }
        
        try {
            $config = NocodeModelConfig::findOneByWhere([
                ['name', '=', $tableName],
                ['system_id', '=', SYSTEM_ID]
            ]);
            
            // 调试信息：记录查询结果
            error_log("TableEntity getTableConfig - Table: {$tableName}, Config: " . json_encode($config));
            
            // 如果查询不到数据，抛出异常
            if (empty($config)) {
                throw new ApiException(ApiException::MSG_ERROR, $tableName . ' 未找到配置');
            }
            
            $result = $config;
            
            // 缓存结果
            self::$tableConfigCache[$cacheKey] = $result;
            
            return $result;
        } catch (\Exception $e) {
            error_log("TableEntity getTableConfig - Table: {$tableName}, Exception: " . $e->getMessage());
            $result = [];
            // 即使查询失败也缓存空结果，避免重复查询
            self::$tableConfigCache[$cacheKey] = $result;
            return $result;
        }
    }



    /**
     * 获取读库服务名
     * @return string
     */
    public function getReadDbServiceName(): string
    {
        // 1. 先去除 []
        $sanitizeTableName = str_replace('[]', '', $this->tableName);
        // 2. 如果是 _count 结尾，去掉 _count
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
        }
        return $this->resolveDbServiceName($sanitizeTableName, 'read');
    }

    /**
     * 获取写库服务名
     * @return string
     */
    public function getWriteDbServiceName(): string
    {
        // 1. 先去除 []
        $sanitizeTableName = str_replace('[]', '', $this->tableName);
        // 2. 如果是 _count 结尾，去掉 _count
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
        }
        return $this->resolveDbServiceName($sanitizeTableName, 'write');
    }

    /**
     * 获取事务库服务名
     * @return string
     */
    public function getTransactionDbServiceName(): string
    {
        // 1. 先去除 []
        $sanitizeTableName = str_replace('[]', '', $this->tableName);
        // 2. 如果是 _count 结尾，去掉 _count
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
        }
        return $this->resolveDbServiceName($sanitizeTableName, 'transaction');
    }

    /**
     * 是否 count 查询
     */
    public function isCountQuery(): bool
    {
        return $this->isCountQuery;
    }

    /**
     * 检查操作权限
     * @param string $method HTTP方法 (GET, POST, PUT, DELETE)
     * @return bool
     * @throws ApiException
     */
    public function checkPermission(string $method): bool
    {
        $config = $this->getTableConfig($this->tableName);
        
        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, $this->tableName . ' 未找到配置');
        }
        
        $method = strtoupper($method);
        $permissionField = 'allow_' . strtolower($method);
        
        // 检查权限字段是否存在
        if (!isset($config[$permissionField])) {
            // 如果权限字段不存在，默认不允许
            return false;
        }
        
        $allowed = (bool)$config[$permissionField];
        
        if (!$allowed) {
            throw new ApiException(ApiException::MSG_ERROR, '没有权限操作此 model: ' . $this->tableName . ' (' . $method . ')');
        }
        
        return true;
    }

    /**
     * 获取表的权限配置
     * @return array
     */
    public function getPermissionConfig(): array
    {
        $config = $this->getTableConfig($this->tableName);
        
        if (empty($config)) {
            return [];
        }
        
        return [
            'allow_get' => (bool)($config['allow_get'] ?? true),
            'allow_post' => (bool)($config['allow_post'] ?? false),
            'allow_put' => (bool)($config['allow_put'] ?? false),
            'allow_delete' => (bool)($config['allow_delete'] ?? false),
        ];
    }

    /**
     * 清除表配置缓存
     * @param string|null $tableName 如果指定，只清除该表的缓存；否则清除所有缓存
     */
    public static function clearTableConfigCache(?string $tableName = null): void
    {
        if ($tableName) {
            $cacheKey = $tableName . '_' . SYSTEM_ID;
            unset(self::$tableConfigCache[$cacheKey]);
        } else {
            self::$tableConfigCache = [];
        }
    }

    /**
     * 获取表配置缓存
     * @return array
     */
    public static function getTableConfigCache(): array
    {
        return self::$tableConfigCache;
    }

    /**
     * 判定字段是否是主键或单列唯一索引（依据 table_config）
     * @param string $fieldName
     * @param array $tableConfig
     * @return bool
     */
    public static function isPrimaryKeyOrUniqueIndexFromConfig(string $tableName, string $fieldName): bool
    {
        // 统一去除后缀
        $sanitizeTableName = str_replace('[]', '', (string)$tableName);
        if (preg_match('/^(.*)_count$/', $sanitizeTableName, $m)) {
            $sanitizeTableName = $m[1];
        }

        $config = self::getTableConfigStatic($sanitizeTableName);
        if (empty($config) || empty($config['table_config'])) {
            return false;
        }

        $tableConfig = json_decode($config['table_config'], true);
        if (!is_array($tableConfig)) {
            return false;
        }

        if (isset($tableConfig['pk']) && $tableConfig['pk'] === $fieldName) {
            return true;
        }
        if (isset($tableConfig['indexes']) && is_array($tableConfig['indexes'])) {
            foreach ($tableConfig['indexes'] as $indexConfig) {
                $isUnique = isset($indexConfig['is_unique']) && $indexConfig['is_unique'] === true;
                $columns = $indexConfig['columns'] ?? [];
                if ($isUnique && is_array($columns) && count($columns) === 1) {
                    $colName = $columns[0]['name'] ?? '';
                    if ($colName === $fieldName) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 静态方法获取表配置（复用现有的 getTableConfig 逻辑）
     * @param string $tableName
     * @return array
     */
    public static function getTableConfigStatic(string $tableName): array
    {
        // 统一去除数组/统计后缀，保证查配置使用规范表名
        $tableName = str_replace('[]', '', (string)$tableName);
        if (preg_match('/^(.*)_count$/', $tableName, $m)) {
            $tableName = $m[1];
        }
        // 生成缓存键
        $cacheKey = $tableName . '_' . SYSTEM_ID;
        
        // 检查缓存
        if (isset(self::$tableConfigCache[$cacheKey])) {
            return self::$tableConfigCache[$cacheKey];
        }
        
        try {
            $config = NocodeModelConfig::findOneByWhere([
                ['name', '=', $tableName],
                ['system_id', '=', SYSTEM_ID]
            ]);
            
            // 调试信息：记录查询结果
            error_log("TableEntity getTableConfigStatic - Table: {$tableName}, Config: " . json_encode($config));
            
            // 如果查询不到数据，返回空数组
            if (empty($config)) {
                $result = [];
            } else {
                $result = $config;
            }
            
            // 缓存结果
            self::$tableConfigCache[$cacheKey] = $result;
            
            return $result;
        } catch (\Exception $e) {
            error_log("TableEntity getTableConfigStatic - Table: {$tableName}, Exception: " . $e->getMessage());
            $result = [];
            // 即使查询失败也缓存空结果，避免重复查询
            self::$tableConfigCache[$cacheKey] = $result;
            return $result;
        }
    }
}