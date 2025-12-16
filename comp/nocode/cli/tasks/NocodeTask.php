<?php

use Imee\Comp\Nocode\Models\Cms\NocodeModelConfig;
use Imee\Comp\Nocode\Service\Logic\InitLogic;
use Imee\Service\Helper;

/**
 * 更新schema_config
 * php cli.php nocode -process sync_schema_config
 */
class NocodeTask extends CliApp
{
    public function mainAction(array $params = [])
    {
        if (!empty($params)) {
            $process = $params['process'] ?? '';

            $this->console('================== start ==================');

            if ($process == 'sync_schema_config') {
                $this->syncSchemaConfig($params);
            } elseif ($process == 'init_nocode_modules') {
                $this->initNocodeModules();
            } else {
                $this->console('error process!');
            }

            $this->console('================== end ==================');
        }
        return false;
    }

    /**
     * 更新schema_config
     */
        public function syncSchemaConfig(array $params)
    {
        $this->console('开始同步schema_config...');
        
        // 获取所有需要同步的Model类
        $modelClasses = $this->getAllModelClasses();
        
        $this->console("扫描到 " . count($modelClasses) . " 个Model类");
        
        $successCount = 0;
        $errorCount = 0;

        foreach ($modelClasses as $modelClass) {
            // 安全处理每个Model
            $this->safeProcessModel($modelClass, $successCount, $errorCount);
        }
        
        $this->console("同步完成! 成功: {$successCount}, 失败: {$errorCount}");
    }

    /**
     * 获取所有需要同步的Model类
     */
    private function getAllModelClasses(): array
    {
        $modelClasses = [];

        // 获取项目根目录
        $rootDir = ROOT;

        // 动态扫描所有可能的Model目录
        $modelDirs = $this->findModelDirectories($rootDir);
        
        $this->console("扫描到 " . count($modelDirs) . " 个Model目录");
        foreach ($modelDirs as $dir) {
            $this->console("Model目录: {$dir}");
        }

        foreach ($modelDirs as $dir) {
            if (!is_dir($dir)) {
                $this->console("目录不存在: {$dir}");
                continue;
            }

            $files = glob($dir . '/*.php');
            $this->console("目录 {$dir} 中有 " . count($files) . " 个PHP文件");
            foreach ($files as $file) {
                $className = basename($file, '.php');

                // 跳过BaseModel
                if ($className === 'BaseModel') {
                    continue;
                }

                // 直接从文件中读取namespace
                $fullClassName = $this->getFullClassNameFromFile($file, $className);
                if (!$fullClassName) {
                    continue;
                }

                // 检查是否是有问题的类
                if ($this->isProblematicClass($fullClassName)) {
                    $this->console("跳过有问题的Model类: {$fullClassName}");
                    continue;
                }

                // 检查类是否存在且继承自BaseModel
                $this->safeCheckClass($fullClassName, $modelClasses);
            }
        }

        return $modelClasses;
    }

    /**
     * 动态查找Model目录
     */
    private function findModelDirectories(string $rootDir): array
    {
        $modelDirs = [];

        // 扫描server/app/models/下的所有子目录
        $appModelsDir = $rootDir . '/app/models';
        if (is_dir($appModelsDir)) {
            $subdirs = glob($appModelsDir . '/*', GLOB_ONLYDIR);
            foreach ($subdirs as $subdir) {
                if (is_file($subdir . '/BaseModel.php')) {
                    $modelDirs[] = $subdir;
                }
            }
        }

        // 获取扩展的Model目录
        $extendDirs = $this->getExtendModelDirs($rootDir);
        $modelDirs = array_merge($modelDirs, $extendDirs);

        return $modelDirs;
    }

    /**
     * 获取扩展的Model目录
     */
    private function getExtendModelDirs(string $rootDir): array
    {
        $extendDirs = [];

        // 定义扩展的Model目录数组
        $extendModelDirs = [
            '/comp/operate/auth/models',
            // 后续可以在这里添加更多的Model目录
            // '/comp/other/module/models',
            // '/comp/another/module/models',
        ];

        // 遍历数组，检查目录是否存在且包含BaseModel.php
        foreach ($extendModelDirs as $dir) {
            $fullPath = $rootDir . $dir;
            if (is_dir($fullPath) && is_file($fullPath . '/BaseModel.php')) {
                $extendDirs[] = $fullPath;
            }
        }

        return $extendDirs;
    }


    /**
     * 安全检查类是否存在且继承自BaseModel
     */
    private function safeCheckClass(string $fullClassName, array &$modelClasses): void
    {
        // 使用函数来隔离可能的致命错误
        $result = $this->executeInIsolation(function () use ($fullClassName) {
            if (!class_exists($fullClassName)) {
                return false;
            }

            $reflection = new \ReflectionClass($fullClassName);
            return $reflection->isSubclassOf('Imee\Comp\Common\Orm\BaseModel');
        });

        if ($result === true) {
            $modelClasses[] = $fullClassName;
        } elseif ($result === false) {
            // 类存在但不是BaseModel的子类，静默跳过
        } else {
            // result为null表示有错误
            $this->console("跳过有问题的Model类: {$fullClassName}");
        }
    }

    /**
     * 安全处理单个Model
     */
    private function safeProcessModel(string $modelClass, int &$successCount, int &$errorCount): void
    {
        try {
            // 获取Model信息
            $modelInfo = $this->getModelInfo($modelClass);
            if (!$modelInfo) {
                $this->console("跳过Model: {$modelClass} - 无法获取信息");
                $errorCount++;
                return;
            }

            // 检查是否已存在
            $existingRecord = $this->checkExistingRecord($modelInfo['name'], $modelInfo['system_id']);

            if ($existingRecord) {
                // 检查master是否一致
                if ($existingRecord['master'] === $modelInfo['master']) {
                    // 直接更新
                    $this->updateRecord($existingRecord['id'], $modelInfo);
                } else {
                    // master不一致，检查带master后缀的记录是否存在
                    $newName = $this->generateUniqueNameWithMaster($modelInfo['name'], $modelInfo['system_id'], $modelInfo['master']);
                    $existingRecordWithMaster = NocodeModelConfig::findFirstByWhere([
                        ['name', '=', $newName],
                        ['system_id', '=', $modelInfo['system_id']]
                    ]);
                    
                    if ($existingRecordWithMaster) {
                        // 如果存在，直接更新
                        $this->updateRecord($existingRecordWithMaster->id, $modelInfo);
                        $this->console("更新记录: {$newName} (master不一致，但记录已存在)");
                    } else {

                        // 如果不存在，添加新记录
                        $modelInfo['name'] = $newName;
                        $this->addRecord($modelInfo);
                        $this->console("添加新记录: {$newName} (master不一致: {$existingRecord['master']} vs {$modelInfo['master']})");
                    }
                }
            } else {
                // 直接添加
                $this->addRecord($modelInfo);
            }

            $successCount++;

        } catch (\Error $e) {
            $this->console("处理Model {$modelClass} 时出现致命错误: " . $e->getMessage());
            $errorCount++;
        } catch (\Exception $e) {
            $this->console("处理Model {$modelClass} 时出错: " . $e->getMessage());
            $errorCount++;
        }
    }

    /**
     * 检查是否是有问题的类
     */
    private function isProblematicClass(string $modelClass): bool
    {
        $problematicClasses = [
            'Imee\Models\Xss\XsChatSession', // 方法签名不兼容
            // 可以在这里添加其他有问题的类
        ];

        return in_array($modelClass, $problematicClasses);
    }

    /**
     * 在隔离环境中执行代码
     */
    private function executeInIsolation(callable $callback)
    {
        try {
            return $callback();
        } catch (\Error $e) {
            $this->console("执行过程中出现致命错误: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->console("执行过程中出现异常: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 从文件中读取完整的类名
     */
    private function getFullClassNameFromFile(string $filePath, string $className): ?string
    {
        try {
            $content = file_get_contents($filePath);
            if (!$content) {
                $this->console("无法读取文件: {$filePath}");
                return null;
            }

            // 查找namespace声明
            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                $namespace = trim($matches[1]);
                return $namespace . '\\' . $className;
            }

            $this->console("无法从文件中找到namespace: {$filePath}");
            return null;
        } catch (\Exception $e) {
            $this->console("读取文件失败: {$filePath} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取Model信息
     */
    private function getModelInfo(string $modelClass): ?array
    {
        return $this->executeInIsolation(function () use ($modelClass) {
            $reflection = new \ReflectionClass($modelClass);

            // 获取类名作为name
            $name = $reflection->getShortName();

            // 获取表名
            $table = $this->getTableName($modelClass);
            if (!$table) {
                return null;
            }

            // 获取model完整路径
            $model = $modelClass;

            // 获取master和slave
            $master = $reflection->getConstant('SCHEMA') ?? '';
            $slave = $reflection->getConstant('SCHEMA_READ') ?? '';

            if (empty($master)) {
                $this->console("跳过Model: {$modelClass} - 无法获取SCHEMA常量");
                return null;
            }

            // 获取table_config
            $tableConfig = $this->getTableConfig($table, $master);

            // 获取表注释
            $comment = $this->getTableComment($table, $master);

            // 只在获取失败时打印信息

            return [
                'name'         => $name,
                'table'        => $table,
                'model'        => $model,
                'master'       => $master,
                'slave'        => $slave,
                'table_config' => $tableConfig,
                'comment'      => $comment,
                'system_id'    => SYSTEM_ID,
            ];
        });
    }

    /**
     * 获取表名
     */
    private function getTableName(string $modelClass): ?string
    {
        try {
            if (method_exists($modelClass, 'getTableName')) {
                $tableName = $modelClass::getTableName();
                return $tableName;
            }

            // 尝试实例化Model获取表名
            $model = new $modelClass();
            if (method_exists($model, 'getSource')) {
                $tableName = $model->getSource();
                return $tableName;
            }

            return null;
        } catch (\Error $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取表配置信息
     */
    private function getTableConfig(string $tableName, string $schema): string
    {
        try {
            // 直接使用schema作为数据库名
            $dbName = $schema;

            // 首先检查表是否存在
            $checkTableSql = "SHOW TABLES LIKE '{$tableName}'";
            $tableExists = Helper::fetchOne($checkTableSql, null, $schema);
            if (!$tableExists) {
                $this->console("表不存在: {$tableName} (数据库: {$schema})");
                // 尝试不区分大小写查找，使用分页查询
                $offset = 0;
                $limit = 100;
                $foundTable = false;
                
                while (true) {
                    $allTablesSql = "SHOW TABLES LIMIT {$limit} OFFSET {$offset}";
                    $tables = Helper::fetch($allTablesSql, null, $schema);
                    
                    if (empty($tables)) {
                        break; // 没有更多表了
                    }
                    
                    $tablesLower = array_map('strtolower', array_column($tables, 'Tables_in_' . $dbName));
                    $tableNameLower = strtolower($tableName);
                    
                    if (in_array($tableNameLower, $tablesLower)) {
                        $correctTableName = array_values(array_filter(array_column($tables, 'Tables_in_' . $dbName), function ($t) use ($tableNameLower) {
                            return strtolower($t) === $tableNameLower;
                        }))[0];
                        $tableName = $correctTableName;
                        $this->console("使用正确的表名: {$tableName}");
                        $foundTable = true;
                        break;
                    }
                    
                    $offset += $limit;
                    
                    // 限制最大查询数量，避免无限循环
                    if ($offset >= 10000) {
                        $this->console("表不存在或超出查询限制: {$tableName} (数据库: {$schema})");
                        return json_encode(['fields' => [], 'pk' => '', 'comment' => '', 'indexes' => []]);
                    }
                    
                    // 添加间隔，避免对数据库造成压力
                    usleep(10000); // 10ms
                }
                
                if (!$foundTable) {
                    $this->console("表不存在: {$tableName} (数据库: {$schema})");
                    return json_encode(['fields' => [], 'pk' => '', 'comment' => '', 'indexes' => []]);
                }
            }

            // 使用DESCRIBE命令获取表结构
            $describeSql = "DESCRIBE {$tableName}";
            $columns = Helper::fetch($describeSql, null, $schema);

            if (empty($columns)) {
                // 尝试使用information_schema
                $sql = "SELECT * FROM information_schema.columns WHERE table_schema = '{$dbName}' AND table_name = '{$tableName}' ORDER BY ordinal_position";
                $columns = Helper::fetch($sql, null, $schema);

                if (empty($columns)) {
                    $this->console("无法获取表结构: {$tableName} (数据库: {$schema})");
                    return json_encode(['fields' => [], 'pk' => '', 'comment' => '', 'indexes' => []]);
                }
            }

            $fields = [];
            $pk = '';

            foreach ($columns as $column) {
                // 处理DESCRIBE和information_schema的不同字段名
                if (isset($column['Field'])) {
                    // DESCRIBE格式
                    $fieldName = $column['Field'];
                    $columnType = $column['Type'];
                    $key = $column['Key'];
                    $default = $column['Default'];

                    // 解析数据类型
                    $dataType = strtolower(preg_replace('/\([^)]*\)/', '', $columnType));
                    $length = null;
                    if (preg_match('/\((\d+)\)/', $columnType, $matches)) {
                        $length = $matches[1];
                    }

                    $isPrimary = $key === 'PRI';
                    $comment = ''; // DESCRIBE不包含注释
                } else {
                    // information_schema格式
                    $fieldName = $column['COLUMN_NAME'];
                    $dataType = strtolower($column['DATA_TYPE']);
                    $columnType = strtolower($column['COLUMN_TYPE']);
                    $length = $column['CHARACTER_MAXIMUM_LENGTH'] ?: $column['NUMERIC_PRECISION'];
                    $comment = $column['COLUMN_COMMENT'] ?? '';
                    $isPrimary = strtolower($column['COLUMN_KEY']) === 'pri';
                    $default = $column['COLUMN_DEFAULT'];
                }

                // 确定组件类型
                $component = $this->getComponentByType($dataType);

                // 构建字段配置
                $fieldConfig = [
                    'type'      => $columnType,
                    'length'    => $length,
                    'default'   => $default,
                    'component' => $component,
                    'comment'   => $comment
                ];

                $fields[$fieldName] = $fieldConfig;

                if ($isPrimary) {
                    $pk = $fieldName;
                }
            }

            // 获取表注释
            $tableComment = '';
            try {
                $tableSql = "SELECT table_comment FROM information_schema.tables WHERE table_schema = '{$dbName}' AND table_name = '{$tableName}'";
                $tableInfo = Helper::fetchOne($tableSql, null, $schema);
                $tableComment = $tableInfo['table_comment'] ?? '';
            } catch (\Exception $e) {
                // 静默处理表注释获取失败
            }

            // 获取索引配置信息
            $indexes = [];
            try {
                $indexSql = "SELECT 
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    SEQ_IN_INDEX,
                    CARDINALITY,
                    SUB_PART,
                    PACKED,
                    NULLABLE,
                    INDEX_TYPE,
                    COMMENT
                FROM information_schema.statistics 
                WHERE table_schema = '{$dbName}' 
                AND table_name = '{$tableName}' 
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";
                
                $indexData = Helper::fetch($indexSql, null, $schema);
                
                if (!empty($indexData)) {
                    foreach ($indexData as $index) {
                        $indexName = $index['INDEX_NAME'];
                        $columnName = $index['COLUMN_NAME'];
                        $isUnique = $index['NON_UNIQUE'] == 0;
                        $seqInIndex = $index['SEQ_IN_INDEX'];
                        $cardinality = $index['CARDINALITY'];
                        $subPart = $index['SUB_PART'];
                        $indexType = $index['INDEX_TYPE'];
                        $comment = $index['COMMENT'] ?? '';
                        
                        if (!isset($indexes[$indexName])) {
                            $indexes[$indexName] = [
                                'name' => $indexName,
                                'columns' => [],
                                'is_unique' => $isUnique,
                                'type' => $indexType,
                                'comment' => $comment
                            ];
                        }
                        
                        $indexes[$indexName]['columns'][] = [
                            'name' => $columnName,
                            'seq_in_index' => $seqInIndex,
                            'cardinality' => $cardinality,
                            'sub_part' => $subPart
                        ];
                    }
                }
            } catch (\Exception $e) {
                // 静默处理索引获取失败
                $this->console("获取索引信息失败: {$tableName} (数据库: {$schema}) - " . $e->getMessage());
            }

            // 只在获取失败时打印信息
            if (empty($fields)) {
                $this->console("获取表配置失败: {$tableName} (数据库: {$schema})");
            }

            return json_encode([
                'fields'  => $fields,
                'pk'      => $pk,
                'visible' => [],
                'disable' => [],
                'comment' => $tableComment,
                'indexes' => $indexes
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            $this->console("获取表配置失败: {$tableName} (数据库: {$schema}) - " . $e->getMessage());
            return json_encode(['fields' => [], 'pk' => '', 'comment' => '', 'indexes' => []]);
        }
    }

    /**
     * 获取表注释
     */
    private function getTableComment(string $tableName, string $schema): string
    {
        try {
            // 直接使用schema作为数据库名
            $dbName = $schema;

            // 查询表注释
            $tableSql = "SELECT table_comment FROM information_schema.tables WHERE table_schema = '{$dbName}' AND table_name = '{$tableName}'";
            $tableInfo = Helper::fetchOne($tableSql, null, $schema);
            
            if ($tableInfo && !empty($tableInfo['table_comment'])) {
                return $tableInfo['table_comment'];
            }
            
            return '';
        } catch (\Exception $e) {
            $this->console("获取表注释失败: {$tableName} (数据库: {$schema}) - " . $e->getMessage());
            return '';
        }
    }

    /**
     * 根据数据类型确定组件类型
     */
    private function getComponentByType(string $dataType): string
    {
        $componentMap = [
            'int'        => 'NumberPicker',
            'bigint'     => 'NumberPicker',
            'tinyint'    => 'Select',
            'smallint'   => 'NumberPicker',
            'mediumint'  => 'NumberPicker',
            'varchar'    => 'Input',
            'char'       => 'Input',
            'text'       => 'TextArea',
            'longtext'   => 'TextArea',
            'mediumtext' => 'TextArea',
            'tinytext'   => 'TextArea',
            'datetime'   => 'DatePicker',
            'timestamp'  => 'DatePicker',
            'date'       => 'DatePicker',
            'time'       => 'TimePicker',
            'decimal'    => 'NumberPicker',
            'float'      => 'NumberPicker',
            'double'     => 'NumberPicker',
            'json'       => 'TextArea'
        ];

        return $componentMap[$dataType] ?? 'Input';
    }

    /**
     * 检查是否已存在记录
     */
    private function checkExistingRecord(string $name, int $systemId): ?array
    {
        try {
            // 使用MysqlCollectionTrait方法
            $result = NocodeModelConfig::findFirstByWhere([
                ['name', '=', $name],
                ['system_id', '=', $systemId]
            ]);

            if ($result) {
                return $result->toArray();
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $this->console("检查现有记录失败: {$name} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * 生成唯一名称
     */
    private function generateUniqueName(string $baseName, int $systemId): string
    {
        try {
            $counter = 2;
            $newName = $baseName . $counter;

            while ($this->checkExistingRecord($newName, $systemId)) {
                $counter++;
                $newName = $baseName . $counter;
            }

            return $newName;
        } catch (\Exception $e) {
            $this->console("生成唯一名称失败: {$baseName} - " . $e->getMessage());
            return $baseName . '2';
        }
    }

        /**
     * 生成带master后缀的名称
     */
    private function generateUniqueNameWithMaster(string $baseName, int $systemId, string $master): string
    {
        // 将master首字母大写
        $masterSuffix = ucfirst($master);
        return $baseName . $masterSuffix;
    }

    /**
     * 添加记录
     */
    private function addRecord(array $modelInfo): void
    {
        try {
            // 使用MysqlCollectionTrait的add方法
            $data = [
                'name'         => $modelInfo['name'],
                'table'        => $modelInfo['table'],
                'model'        => $modelInfo['model'],
                'master'       => $modelInfo['master'],
                'slave'        => $modelInfo['slave'],
                'table_config' => $modelInfo['table_config'],
                'comment'      => $modelInfo['comment'],
                'system_id'    => $modelInfo['system_id'],
            ];

            $result = NocodeModelConfig::add($data);

            if (!$result[0]) {
                $this->console("添加记录失败: {$modelInfo['name']} - " . $result[1]);
            }
        } catch (\Exception $e) {
            $this->console("添加记录失败: {$modelInfo['name']} - " . $e->getMessage());
        }
    }

    /**
     * 更新记录
     */
    private function updateRecord(int $id, array $modelInfo): void
    {
        try {
            // 使用MysqlCollectionTrait的edit方法
            $data = [
                'table'        => $modelInfo['table'],
                'model'        => $modelInfo['model'],
                'master'       => $modelInfo['master'],
                'slave'        => $modelInfo['slave'],
                'table_config' => $modelInfo['table_config'],
            ];

            $result = NocodeModelConfig::edit($id, $data);

            if (!$result[0]) {
                $this->console("更新记录失败: {$modelInfo['name']} - " . $result[1]);
            }
        } catch (\Exception $e) {
            $this->console("更新记录失败: {$modelInfo['name']} (ID: {$id}) - " . $e->getMessage());
        }
    }

    /**
     * 初始化零代码模块
     */
    private function initNocodeModules(): void
    {
        try {
            InitLogic::getInstance()->moduleInit();
            $this->console("初始化零代码模块成功");
        } catch (\Exception $e) {
            $this->console("初始化零代码模块失败: " . $e->getMessage());
        }
    }
}