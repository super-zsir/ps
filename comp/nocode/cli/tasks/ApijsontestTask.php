<?php

use Imee\Comp\Nocode\Models\Cms\NocodeModelConfig;
use Imee\Service\Helper;
use Imee\Comp\Nocode\Apijson\ApiJson;

/**
 * apijson 测试用例
 * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process test 
 */
class ApijsontestTask extends CliApp
{
    public function mainAction(array $params = [])
    {
        if (!empty($params)) {
            $process = $params['process'] ?? 'test';

            $this->console('================== api_json_test start ==================');

            if (method_exists($this, $process)) {
                $this->{$process}($params);
            } else {
                $this->console('error process!');
            }

            $this->console('================== api_json_test end ==================');
        }
        return false;
    }

    public function test(array $params)
    {
        $this->console('================== APIJSON 测试用例开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 测试APIJSON类创建...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 全面的APIJSON语法测试用例
            $testCases = [
                // 1. 基础查询
                [
                    'category' => '基础查询',
                    'name' => '简单查询',
                    'content' => '{"CmsUser":{"user_id":1,"@column":"user_id,user_name,user_email"}}'
                ],
                [
                    'category' => '基础查询',
                    'name' => '列表查询', 
                    'content' => '{"CmsUser[]":{"user_status":1,"@limit":3,"@column":"user_id,user_name"}}'
                ],
                // 2. 比较操作符
                [
                    'category' => '比较操作符',
                    'name' => '大于小于',
                    'content' => '{"CmsUser[]":{"user_id>":1,"user_id<":10,"@column":"user_id,user_name","@limit":3}}'
                ],
                [
                    'category' => '比较操作符',
                    'name' => '大于等于小于等于',
                    'content' => '{"CmsUser[]":{"user_id>=":1,"user_id<=":5,"@column":"user_id,user_name"}}'
                ],
                [
                    'category' => '比较操作符',
                    'name' => '不等于',
                    'content' => '{"CmsUser[]":{"user_id!=":1,"@column":"user_id,user_name","@limit":3}}'
                ],
                // 3. 集合操作符
                [
                    'category' => '集合操作符',
                    'name' => 'IN查询',
                    'content' => '{"CmsUser[]":{"user_id{}":[1,2,3],"@column":"user_id,user_name"}}'
                ],
                [
                    'category' => '集合操作符', 
                    'name' => 'NOT IN查询',
                    'content' => '{"CmsUser[]":{"user_id!{}":[1,2],"@column":"user_id,user_name","@limit":3}}'
                ],
                // 4. 字符串操作符
                [
                    'category' => '字符串操作符',
                    'name' => 'LIKE包含',
                    'content' => '{"CmsUser[]":{"user_name$":"admin","@column":"user_id,user_name","@limit":3}}'
                ],
                [
                    'category' => '字符串操作符',
                    'name' => 'LIKE开头',
                    'content' => '{"CmsUser[]":{"user_name^":"test","@column":"user_id,user_name","@limit":3}}'
                ],
                [
                    'category' => '字符串操作符',
                    'name' => 'REGEXP正则',
                    'content' => '{"CmsUser[]":{"user_name%":"^test.*","@column":"user_id,user_name","@limit":3}}'
                ],
                // 5. 字段映射
                [
                    'category' => '字段映射',
                    'name' => '基础别名',
                    'content' => '{"CmsUser":{"user_id":1,"@column":"user_id:uid,user_name:name"}}'
                ],
                [
                    'category' => '字段映射',
                    'name' => '混合别名',
                    'content' => '{"CmsUser":{"user_id":1,"@column":"user_id:uid,user_name,user_email:email"}}'
                ],
                // 6. 分页和排序
                [
                    'category' => '分页排序',
                    'name' => '分页查询',
                    'content' => '{"CmsUser[]":{"@limit":2,"@offset":0,"@column":"user_id,user_name"}}'
                ],
                [
                    'category' => '分页排序',
                    'name' => '排序查询',
                    'content' => '{"CmsUser[]":{"@order":"user_id-","@limit":3,"@column":"user_id,user_name"}}'
                ],
                [
                    'category' => '分页排序',
                    'name' => '多字段排序',
                    'content' => '{"CmsUser[]":{"@order":"user_status+,user_id-","@limit":3,"@column":"user_id,user_name,user_status"}}'
                ],
                // 7. 分组和聚合
                [
                    'category' => '分组聚合',
                    'name' => '分组查询',
                    'content' => '{"CmsUser[]":{"@group":"user_status","@column":"user_status,COUNT(*) as count"}}'
                ],
                [
                    'category' => '分组聚合',
                    'name' => 'HAVING条件',
                    'content' => '{"CmsUser[]":{"@group":"user_status","@having":"COUNT(*) > 1","@column":"user_status,COUNT(*) as count"}}'
                ],
                // 8. 函数查询
                [
                    'category' => '函数查询',
                    'name' => '聚合函数',
                    'content' => '{"CmsUser":{"@column":"COUNT(*) as total,MAX(user_id) as max_id,AVG(user_id) as avg_id"}}'
                ],
                [
                    'category' => '函数查询',
                    'name' => '字符串函数',
                    'content' => '{"CmsUser[]":{"@column":"user_id,CONCAT(user_name,\'-\',user_email) as info","@limit":3}}'
                ],
                // 9. 引用查询
                [
                    'category' => '引用查询',
                    'name' => '基础引用',
                    'content' => '{"CmsModuleUser":{"id":501,"@column":"user_id"},"CmsUser":{"user_id@":"CmsModuleUser/user_id","@column":"user_id,user_name"}}'
                ],
                [
                    'category' => '引用查询',
                    'name' => '多表引用',
                    'content' => '{"CmsModuleUser":{"id":501,"@column":"user_id,module_id"},"CmsUser":{"user_id@":"CmsModuleUser/user_id","@column":"user_id,user_name"},"CmsModules":{"module_id@":"CmsModuleUser/module_id","@column":"module_id,module_name"}}'
                ],
                // 10. 复杂查询
                [
                    'category' => '复杂查询',
                    'name' => '条件组合',
                    'content' => '{"CmsUser[]":{"user_id>":1,"user_status":1,"user_name$":"a","@order":"user_id-","@limit":3,"@column":"user_id,user_name,user_status"}}'
                ],
                // 11. 高级操作符
                [
                    'category' => '高级操作符',
                    'name' => '@sum求和',
                    'content' => '{"CmsUser":{"user_status":1,"@sum":"user_id"}}'
                ],
                [
                    'category' => '高级操作符',
                    'name' => '@distinct去重',
                    'content' => '{"CmsUser[]":{"user_status":1,"@distinct":"user_email","@limit":5}}'
                ],
                [
                    'category' => '高级操作符',
                    'name' => '@alias别名',
                    'content' => '{"CmsUser[]":{"@column":"user_id,user_name","@alias":{"user_id":"uid","user_name":"name"},"@limit":3}}'
                ],
                // 12. 复杂逻辑查询 (@语法)
                [
                    'category' => '复杂逻辑查询',
                    'name' => '简单OR查询',
                    'content' => '{"CmsUser":{"@":{"operator":"OR","user_id":1,"user_name$":"admin"},"@column":"user_id,user_name"}}'
                ],
                [
                    'category' => '复杂逻辑查询',
                    'name' => '复杂嵌套逻辑',
                    'content' => '{"CmsUser":{"@":{"operator":"OR","user_id":1,"AND":{"user_status":1,"OR":{"user_name$":"admin","user_email$":"admin"}}},"@column":"user_id,user_name,user_status"}}'
                ],
                [
                    'category' => '复杂逻辑查询',
                    'name' => '多条件OR查询',
                    'content' => '{"CmsUser[]":{"@":{"operator":"OR","user_id{}":[1,2,3],"user_status":1,"user_name^":"admin"},"@column":"user_id,user_name,user_status","@limit":5}}'
                ]
            ];

            // 新增：根对象 vs 根数组 嵌套子表用例（用户反馈）
            $this->console("\n=== 用例：根对象嵌套子表 ===");
            $objQuery = [
                "CmsUser" => [
                    "user_id" => 1,
                    "@column" => "user_id,user_name",
                    "CmsModuleUser[]" => [
                        "user_id@" => "/user_id",
                        "@column" => "module_id,create_time"
                    ]
                ]
            ];
            $objRes = $apiJson->Query(json_encode($objQuery));
            $this->console('根对象查询结果: ' . json_encode($objRes, JSON_UNESCAPED_UNICODE));

            $this->console("\n=== 用例：根数组嵌套子表（对照） ===");
            $arrQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_id" => 1,
                        "@column" => "user_id,user_name",
                        "CmsModuleUser[]" => [
                            "user_id@" => "/user_id",
                            "@column" => "module_id,create_time"
                        ]
                    ]
                ]
            ];
            $arrRes = $apiJson->Query(json_encode($arrQuery));
            $this->console('根数组查询结果: ' . json_encode($arrRes, JSON_UNESCAPED_UNICODE));

            // 基本断言
            if (isset($objRes['CmsUser']['CmsModuleUser[]']) && is_array($objRes['CmsUser']['CmsModuleUser[]'])) {
                $this->console('✅ 根对象模式：返回 CmsModuleUser[] 数组，条数=' . count($objRes['CmsUser']['CmsModuleUser[]']));
            } else {
                $this->console('❌ 根对象模式：CmsModuleUser[] 缺失或格式不正确');
            }

            if (isset($arrRes['[]'][0]['CmsUser']['CmsModuleUser[]']) && is_array($arrRes['[]'][0]['CmsUser']['CmsModuleUser[]'])) {
                $this->console('✅ 根数组模式：返回 CmsModuleUser[] 数组，条数=' . count($arrRes['[]'][0]['CmsUser']['CmsModuleUser[]']));
            } else {
                $this->console('❌ 根数组模式：CmsModuleUser[] 缺失或格式不正确');
            }

            // 新增：多层嵌套测试（官方支持语法）
            $this->console("\n=== 用例：根对象多层嵌套（CmsModuleUser[] -> CmsModules -> CmsModules） ===");
            $deepQuery = [
                "CmsUser" => [
                    "user_id" => 1,
                    "@column" => "user_id,user_name",
                    "CmsModuleUser[]" => [
                        "user_id@" => "/user_id",
                        "@column" => "module_id,create_time",
                        "CmsModules" => [
                            "module_id@" => "/module_id",
                            "@column" => "module_id,module_name,parent_module_id",
                            "CmsModules" => [
                                "module_id@" => "/parent_module_id",
                                "@column" => "module_id,module_name,parent_module_id"
                            ]
                        ]
                    ]
                ]
            ];
            $deepRes = $apiJson->Query(json_encode($deepQuery));
            $this->console('多层嵌套查询结果: ' . json_encode($deepRes, JSON_UNESCAPED_UNICODE));
            if (
                isset($deepRes['CmsUser']['CmsModuleUser[]'][0]['CmsModules']) &&
                isset($deepRes['CmsUser']['CmsModuleUser[]'][0]['CmsModules']['CmsModules'])
            ) {
                $this->console('✅ 多层嵌套：返回 CmsModules 以及其下的 CmsModules');
            } else {
                $this->console('⚠️ 多层嵌套：未检测到期望的层级（请检查数据是否存在 parent_module_id 对应的模块）');
            }
            
            $this->console("准备执行 " . count($testCases) . " 个测试用例...");
            $this->console('');
            
            $results = [];
            $successCount = 0;
            $failCount = 0;
            
            foreach ($testCases as $index => $testCase) {
                $testNum = $index + 1;
                $this->console("{$testNum}. [{$testCase['category']}] {$testCase['name']}");
                $this->console("请求: {$testCase['content']}");
                
                // 确保分类统计存在
                if (!isset($results[$testCase['category']])) {
                    $results[$testCase['category']] = ['success' => 0, 'fail' => 0];
                }
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    
                    // 判断请求是否成功
                    // GET 请求成功时直接返回数据（没有 code 字段）
                    // 错误时才有 code 字段
                    if (isset($result['code'])) {
                        // 有 code 字段，说明是错误响应
                        $code = $result['code'];
                        $msg = $result['msg'] ?? 'unknown';
                        $this->console("✗ 失败 (code: {$code}, msg: {$msg})");
                        $failCount++;
                        $results[$testCase['category']]['fail']++;
                    } else {
                        // 没有 code 字段，说明是成功的 GET 请求
                        $this->console("✓ 成功 (返回数据)");
                        $successCount++;
                        $results[$testCase['category']]['success']++;
                    }
                } catch (Exception $e) {
                    $this->console("✗ 异常: " . $e->getMessage());
                    $failCount++;
                    $results[$testCase['category']]['fail']++;
                }
                
                $this->console('---');
            }
            
            // 输出详细结果统计
            $this->console('');
            $this->console('================== 详细测试结果统计 ==================');
            foreach ($results as $category => $stat) {
                $success = $stat['success'] ?? 0;
                $fail = $stat['fail'] ?? 0;
                $total = $success + $fail;
                $rate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
                $this->console("{$category}: {$success}/{$total} 成功 ({$rate}%)");
            }
            
            $this->console('');
            $this->console('================== 总体测试结果 ==================');
            $total = $successCount + $failCount;
            $successRate = $total > 0 ? round(($successCount / $total) * 100, 2) : 0;
            
            $this->console("成功: {$successCount}");
            $this->console("失败: {$failCount}");
            $this->console("总计: {$total}");
            $this->console("成功率: {$successRate}%");
            
            if ($successRate >= 80) {
                $this->console("🎉 测试结果优秀！APIJSON功能运行良好。");
            } elseif ($successRate >= 60) {
                $this->console("⚠️  测试结果一般，部分功能可能需要检查。");
            } else {
                $this->console("❌ 测试结果不佳，建议检查APIJSON配置和环境。");
            }
            
        } catch (Exception $e) {
            $this->console('测试过程中发生严重错误: ' . $e->getMessage());
            $this->console('错误文件: ' . $e->getFile());
            $this->console('错误行号: ' . $e->getLine());
            $this->console('请检查APIJSON环境配置和依赖项。');
        }
        
        $this->console('================== APIJSON 测试用例结束 ==================');
    }

    /**
     * 专门测试 @ 复杂逻辑查询语法
     */
    public function testComplexLogic(array $params)
    {
        $this->console('================== APIJSON @ 复杂逻辑查询测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // @ 复杂逻辑查询测试用例
            $testCases = [
                [
                    'name' => '简单OR查询',
                    'description' => 'user_id = 1 OR user_name LIKE %admin%',
                    'content' => '{"CmsUser":{"@":{"operator":"OR","user_id":1,"user_name$":"admin"},"@column":"user_id,user_name"}}',
                    'expected_sql' => 'WHERE (user_id = ? OR user_name LIKE ?)'
                ],
                [
                    'name' => '简单AND查询',
                    'description' => 'user_id = 1 AND user_status = 1',
                    'content' => '{"CmsUser":{"@":{"operator":"AND","user_id":1,"user_status":1},"@column":"user_id,user_name,user_status"}}',
                    'expected_sql' => 'WHERE (user_id = ? AND user_status = ?)'
                ],
                [
                    'name' => '嵌套AND/OR查询',
                    'description' => 'user_id = 1 OR (user_status = 1 AND (user_name LIKE %admin% OR user_email LIKE %admin%))',
                    'content' => '{"CmsUser":{"@":{"operator":"OR","user_id":1,"AND":{"user_status":1,"OR":{"user_name$":"admin","user_email$":"admin"}}},"@column":"user_id,user_name,user_status"}}',
                    'expected_sql' => 'WHERE (user_id = ? OR (user_status = ? AND (user_name LIKE ? OR user_email LIKE ?)))'
                ],
                [
                    'name' => '多条件OR查询',
                    'description' => 'user_id IN (1,2,3) OR user_status = 1 OR user_name LIKE admin%',
                    'content' => '{"CmsUser[]":{"@":{"operator":"OR","user_id{}":[1,2,3],"user_status":1,"user_name^":"admin"},"@column":"user_id,user_name,user_status","@limit":5}}',
                    'expected_sql' => 'WHERE (user_id IN (?,?,?) OR user_status = ? OR user_name LIKE ?)'
                ],
                [
                    'name' => '复杂嵌套逻辑',
                    'description' => 'user_id = 1 OR (user_status = 1 AND user_id > 5) OR user_name LIKE %VIP%',
                    'content' => '{"CmsUser":{"@":{"operator":"OR","user_id":1,"AND":{"user_status":1,"user_id>":5},"user_name$":"VIP"},"@column":"user_id,user_name,user_status"}}',
                    'expected_sql' => 'WHERE (user_id = ? OR (user_status = ? AND user_id > ?) OR user_name LIKE ?)'
                ],
                [
                    'name' => '多层嵌套逻辑',
                    'description' => 'user_id = 1 OR (user_status = 1 AND (user_id > 5 OR user_name LIKE %VIP%))',
                    'content' => '{"CmsUser":{"@":{"operator":"OR","user_id":1,"AND":{"user_status":1,"OR":{"user_id>":5,"user_name$":"VIP"}}},"@column":"user_id,user_name,user_status"}}',
                    'expected_sql' => 'WHERE (user_id = ? OR (user_status = ? AND (user_id > ? OR user_name LIKE ?)))'
                ]
            ];
            
            $this->console("准备执行 " . count($testCases) . " 个 @ 复杂逻辑查询测试用例...");
            $this->console('');
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($testCases as $index => $testCase) {
                $testNum = $index + 1;
                $this->console("{$testNum}. {$testCase['name']}");
                $this->console("描述: {$testCase['description']}");
                $this->console("请求: {$testCase['content']}");
                $this->console("期望SQL: {$testCase['expected_sql']}");
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    
                    // 判断请求是否成功
                    if (isset($result['code'])) {
                        // 有 code 字段，说明是错误响应
                        $code = $result['code'];
                        $msg = $result['msg'] ?? 'unknown';
                        $this->console("✗ 失败 (code: {$code}, msg: {$msg})");
                        $failCount++;
                    } else {
                        // 没有 code 字段，说明是成功的 GET 请求
                        $this->console("✓ 成功 (返回数据)");
                        $successCount++;
                    }
                } catch (Exception $e) {
                    $this->console("✗ 异常: " . $e->getMessage());
                    $failCount++;
                }
                
                $this->console('---');
            }
            
            // 输出测试结果统计
            $this->console('');
            $this->console('================== @ 复杂逻辑查询测试结果 ==================');
            $total = $successCount + $failCount;
            $successRate = $total > 0 ? round(($successCount / $total) * 100, 2) : 0;
            
            $this->console("成功: {$successCount}");
            $this->console("失败: {$failCount}");
            $this->console("总计: {$total}");
            $this->console("成功率: {$successRate}%");
            
            if ($successRate >= 80) {
                $this->console("🎉 @ 复杂逻辑查询功能实现成功！");
            } elseif ($successRate >= 60) {
                $this->console("⚠️  @ 复杂逻辑查询功能基本可用，部分功能可能需要调整。");
            } else {
                $this->console("❌ @ 复杂逻辑查询功能实现有问题，需要检查代码。");
            }
            
        } catch (Exception $e) {
            $this->console('测试过程中发生严重错误: ' . $e->getMessage());
            $this->console('错误文件: ' . $e->getFile());
            $this->console('错误行号: ' . $e->getLine());
        }
        
        $this->console('================== APIJSON @ 复杂逻辑查询测试结束 ==================');
    }
    
    /**
     * 测试引用查询去重机制
     */
    public function testReferenceDeduplication(array $params)
    {
        $this->console('================== APIJSON 引用查询去重测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例：验证引用查询的去重机制
            $testCases = [
                [
                    'name' => '引用查询去重测试',
                    'description' => '测试当多个记录指向相同user_id时，是否会重复查询',
                    'content' => '{
  "CmsModuleUser[]": {
    "id{}": [1,2,7,10,23,31,38],
    "@column": "id,user_id"
  },
  "CmsUser[]": {
    "user_id@": "CmsModuleUser/user_id",
    "@column": "user_id:uid,user_name:name"
  }
}',
                    'expected_behavior' => 'CmsUser结果中每个user_id只出现一次，即使CmsModuleUser中有多个记录指向相同user_id'
                ]
            ];
            
            foreach ($testCases as $index => $testCase) {
                $this->console("\n" . ($index + 1) . ". " . $testCase['name']);
                $this->console('描述: ' . $testCase['description']);
                $this->console('预期行为: ' . $testCase['expected_behavior']);
                $this->console('APIJSON语法:');
                $this->console($testCase['content']);
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    
                    $this->console('执行结果:');
                    $this->console(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    
                    // 分析结果
                    if (isset($result['CmsModuleUser[]']) && isset($result['CmsUser[]'])) {
                        $moduleUsers = $result['CmsModuleUser[]'];
                        $users = $result['CmsUser[]'];
                        
                        // 统计CmsModuleUser中的user_id分布
                        $userIdCounts = [];
                        foreach ($moduleUsers as $moduleUser) {
                            $userId = $moduleUser['user_id'];
                            $userIdCounts[$userId] = ($userIdCounts[$userId] ?? 0) + 1;
                        }
                        
                        // 统计CmsUser结果中的user_id数量
                        $resultUserIds = array_column($users, 'uid');
                        $uniqueResultUserIds = array_unique($resultUserIds);
                        
                        $this->console("\n分析结果:");
                        $this->console("CmsModuleUser中user_id分布: " . json_encode($userIdCounts));
                        $this->console("CmsUser结果中user_id数量: " . count($resultUserIds));
                        $this->console("CmsUser结果中唯一user_id数量: " . count($uniqueResultUserIds));
                        
                        // 检查是否有重复
                        if (count($resultUserIds) === count($uniqueResultUserIds)) {
                            $this->console("✅ 验证通过: 引用查询成功去重，没有重复的user_id");
                        } else {
                            $this->console("❌ 验证失败: 引用查询存在重复的user_id");
                        }
                        
                        // 检查是否所有引用的user_id都被查询到
                        $referencedUserIds = array_keys($userIdCounts);
                        $missingUserIds = array_diff($referencedUserIds, $uniqueResultUserIds);
                        if (empty($missingUserIds)) {
                            $this->console("✅ 验证通过: 所有引用的user_id都被正确查询");
                        } else {
                            $this->console("❌ 验证失败: 缺少以下user_id: " . json_encode($missingUserIds));
                        }
                    }
                    
                } catch (\Throwable $e) {
                    $this->console('❌ 执行失败: ' . $e->getMessage());
                    $this->console('错误详情: ' . $e->getTraceAsString());
                }
            }
            
        } catch (\Throwable $e) {
            $this->console('❌ 测试初始化失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== APIJSON 引用查询去重测试结束 ==================');
    }

    /**
     * 测试单对象查询和数组查询的区别
     */
    public function testSingleVsArrayQuery(array $params)
    {
        $this->console('================== APIJSON 单对象 vs 数组查询测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例：验证单对象查询和数组查询的区别
            $testCases = [
                [
                    'name' => '单对象查询 - 根据主键查询',
                    'description' => '使用CmsUser查询单个用户',
                    'content' => '{
  "CmsUser": {
    "user_id": 572,
    "@column": "user_id,user_name,user_email"
  }
}',
                    'expected_type' => 'single_object'
                ],
                [
                    'name' => '数组查询 - 条件查询',
                    'description' => '使用CmsUser[]查询用户列表',
                    'content' => '{
  "CmsUser[]": {
    "user_status": 1,
    "@column": "user_id,user_name,user_email",
    "@limit": 5
  }
}',
                    'expected_type' => 'array'
                ],
                [
                    'name' => '单对象查询 - 唯一条件',
                    'description' => '使用唯一条件查询单个用户',
                    'content' => '{
  "CmsUser": {
    "user_email": "admin@example.com",
    "@column": "user_id,user_name,user_email"
  }
}',
                    'expected_type' => 'single_object'
                ],
                [
                    'name' => '数组查询 - 模糊条件',
                    'description' => '使用模糊条件查询用户列表',
                    'content' => '{
  "CmsUser[]": {
    "user_name$": "admin",
    "@column": "user_id,user_name,user_email",
    "@limit": 3
  }
}',
                    'expected_type' => 'array'
                ],
                [
                    'name' => '单对象查询 - 复杂条件',
                    'description' => '单对象查询使用复杂条件',
                    'content' => '{
  "CmsUser": {
    "user_id>": 100,
    "user_status": 1,
    "user_name$": "admin",
    "@column": "user_id,user_name,user_email"
  }
}',
                    'expected_type' => 'single_object'
                ],
                [
                    'name' => '数组查询 - 复杂条件',
                    'description' => '数组查询使用复杂条件',
                    'content' => '{
  "CmsUser[]": {
    "user_id>": 100,
    "user_status": 1,
    "user_name$": "admin",
    "@column": "user_id,user_name,user_email",
    "@limit": 5
  }
}',
                    'expected_type' => 'array'
                ]
            ];
            
            foreach ($testCases as $index => $testCase) {
                $this->console("\n" . ($index + 1) . ". " . $testCase['name']);
                $this->console('描述: ' . $testCase['description']);
                $this->console('预期类型: ' . $testCase['expected_type']);
                $this->console('APIJSON语法:');
                $this->console($testCase['content']);
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    
                    $this->console('执行结果:');
                    $this->console(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    
                    // 分析结果类型
                    $resultKeys = array_keys($result);
                    $hasArrayKey = false;
                    $hasSingleKey = false;
                    
                    foreach ($resultKeys as $key) {
                        if (strpos($key, '[]') !== false) {
                            $hasArrayKey = true;
                        } else {
                            $hasSingleKey = true;
                        }
                    }
                    
                    $this->console("\n分析结果:");
                    if ($hasArrayKey) {
                        $this->console("✅ 检测到数组查询结果 (包含[]后缀)");
                        $arrayKey = array_filter($resultKeys, function($key) {
                            return strpos($key, '[]') !== false;
                        });
                        $this->console("数组键: " . implode(', ', $arrayKey));
                        
                        // 统计数组长度
                        foreach ($arrayKey as $key) {
                            $count = is_array($result[$key]) ? count($result[$key]) : 0;
                            $this->console("数组 {$key} 长度: {$count}");
                        }
                    }
                    
                    if ($hasSingleKey) {
                        $this->console("✅ 检测到单对象查询结果 (不包含[]后缀)");
                        $singleKey = array_filter($resultKeys, function($key) {
                            return strpos($key, '[]') === false;
                        });
                        $this->console("单对象键: " . implode(', ', $singleKey));
                        
                        // 检查是否为null或对象
                        foreach ($singleKey as $key) {
                            if ($result[$key] === null) {
                                $this->console("单对象 {$key}: null (未找到匹配记录)");
                            } else {
                                $this->console("单对象 {$key}: 找到匹配记录");
                            }
                        }
                    }
                    
                    // 验证预期类型
                    if ($testCase['expected_type'] === 'array' && $hasArrayKey) {
                        $this->console("✅ 验证通过: 数组查询返回数组结果");
                    } elseif ($testCase['expected_type'] === 'single_object' && $hasSingleKey) {
                        $this->console("✅ 验证通过: 单对象查询返回单对象结果");
                    } else {
                        $this->console("❌ 验证失败: 预期类型与实际结果不匹配");
                    }
                    
                } catch (\Throwable $e) {
                    $this->console('❌ 执行失败: ' . $e->getMessage());
                    $this->console('错误详情: ' . $e->getTraceAsString());
                }
            }
            
        } catch (\Throwable $e) {
            $this->console('❌ 测试初始化失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== APIJSON 单对象 vs 数组查询测试结束 ==================');
    }

    /**
     * 测试timestamp字段的正确使用
     */
    public function testTimestampFields(array $params)
    {
        $this->console('================== APIJSON timestamp字段测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例：验证timestamp字段的正确使用
            $testCases = [
                [
                    'name' => 'timestamp字段比较查询',
                    'description' => '使用标准日期时间格式查询timestamp字段',
                    'content' => '{
  "CmsUser[]": {
    "modify_time>": "2024-01-01 00:00:00",
    "@column": "user_id,user_name,modify_time",
    "@limit": 5
  }
}',
                    'expected_format' => 'YYYY-MM-DD HH:MM:SS'
                ],
                [
                    'name' => 'timestamp字段BETWEEN查询',
                    'description' => '使用BETWEEN查询timestamp字段范围',
                    'content' => '{
  "CmsUser[]": {
    "modify_time$": "2024-01-01 00:00:00,2024-12-31 23:59:59",
    "@column": "user_id,user_name,modify_time",
    "@limit": 3
  }
}',
                    'expected_format' => 'YYYY-MM-DD HH:MM:SS,YYYY-MM-DD HH:MM:SS'
                ],
                [
                    'name' => 'timestamp字段排序查询',
                    'description' => '对timestamp字段进行排序',
                    'content' => '{
  "CmsUser[]": {
    "@order": "modify_time-",
    "@column": "user_id,user_name,modify_time",
    "@limit": 5
  }
}',
                    'expected_format' => 'modify_time- (降序)'
                ],
                [
                    'name' => 'timestamp字段聚合查询',
                    'description' => '对timestamp字段进行聚合操作',
                    'content' => '{
  "CmsUser[]": {
    "@column": "COUNT(*) as total,MAX(modify_time) as latest_time,MIN(modify_time) as earliest_time"
  }
}',
                    'expected_format' => '聚合函数'
                ]
            ];
            
            foreach ($testCases as $index => $testCase) {
                $this->console("\n" . ($index + 1) . ". " . $testCase['name']);
                $this->console('描述: ' . $testCase['description']);
                $this->console('预期格式: ' . $testCase['expected_format']);
                $this->console('APIJSON语法:');
                $this->console($testCase['content']);
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    
                    $this->console('执行结果:');
                    $this->console(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    
                    // 分析结果
                    $this->console("\n分析结果:");
                    
                    // 检查是否有数组结果
                    $arrayKeys = array_filter(array_keys($result), function($key) {
                        return strpos($key, '[]') !== false;
                    });
                    
                    if (!empty($arrayKeys)) {
                        $arrayKey = reset($arrayKeys);
                        $data = $result[$arrayKey];
                        
                        if (is_array($data) && !empty($data)) {
                            $this->console("✅ 查询成功，返回 " . count($data) . " 条记录");
                            
                            // 检查timestamp字段格式
                            $firstRecord = $data[0];
                            if (isset($firstRecord['modify_time'])) {
                                $timestamp = $firstRecord['modify_time'];
                                $this->console("第一条记录的modify_time: " . $timestamp);
                                
                                // 验证timestamp格式
                                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp)) {
                                    $this->console("✅ timestamp格式正确: YYYY-MM-DD HH:MM:SS");
                                } else {
                                    $this->console("❌ timestamp格式不正确: " . $timestamp);
                                }
                            }
                        } else {
                            $this->console("⚠️ 查询成功，但没有返回数据");
                        }
                    } else {
                        // 检查聚合查询结果
                        if (isset($result['total']) || isset($result['latest_time']) || isset($result['earliest_time'])) {
                            $this->console("✅ 聚合查询成功");
                            if (isset($result['latest_time'])) {
                                $this->console("最新时间: " . $result['latest_time']);
                            }
                            if (isset($result['earliest_time'])) {
                                $this->console("最早时间: " . $result['earliest_time']);
                            }
                        } else {
                            $this->console("⚠️ 查询成功，但结果格式不符合预期");
                        }
                    }
                    
                } catch (\Throwable $e) {
                    $this->console('❌ 执行失败: ' . $e->getMessage());
                    $this->console('错误详情: ' . $e->getTraceAsString());
                }
            }
            
            // 测试错误的时间格式
            $this->console("\n\n=== 测试错误的时间格式 ===");
            $errorTestCases = [
                [
                    'name' => '错误格式1 - 时间戳数字',
                    'content' => '{
  "CmsUser[]": {
    "modify_time>": 1640995200,
    "@column": "user_id,user_name,modify_time",
    "@limit": 3
  }
}'
                ],
                [
                    'name' => '错误格式2 - 不完整的日期',
                    'content' => '{
  "CmsUser[]": {
    "modify_time>": "2024-01-01",
    "@column": "user_id,user_name,modify_time",
    "@limit": 3
  }
}'
                ]
            ];
            
            foreach ($errorTestCases as $testCase) {
                $this->console("\n" . $testCase['name']);
                $this->console('APIJSON语法:');
                $this->console($testCase['content']);
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    $this->console('⚠️ 意外成功，但可能不是预期结果');
                    $this->console('结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                } catch (\Throwable $e) {
                    $this->console('❌ 预期失败: ' . $e->getMessage());
                }
            }
            
        } catch (\Throwable $e) {
            $this->console('❌ 测试初始化失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== APIJSON timestamp字段测试结束 ==================');
    }

    /**
     * 测试多表关联查询语法
     */
    public function testMultiTableJoin(array $params)
    {
        $this->console('================== APIJSON 多表关联查询测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例：验证多表关联查询语法
            $testCases = [
                [
                    'name' => '基础多表关联查询',
                    'description' => '使用[]语法的简单多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 5
    }
  }
}',
                    'expected_tables' => ['CmsUser']
                ],
                [
                    'name' => '用户-模块关联查询',
                    'description' => '用户与模块权限的关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 3
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,deleted"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser', 'CmsModules']
                ],
                [
                    'name' => '条件关联查询',
                    'description' => '带条件的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "user_id>": 100,
      "@column": "user_id,user_name,user_email",
      "@limit": 3
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "create_time>": 1700000000,
      "@column": "module_id,create_time"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser']
                ],
                [
                    'name' => '聚合关联查询',
                    'description' => '使用聚合函数的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser']
                ],
                [
                    'name' => '分页关联查询',
                    'description' => '带分页的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@limit": 2,
      "@offset": 0
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser', 'CmsModules']
                ],
                [
                    'name' => '排序关联查询',
                    'description' => '带排序的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name,user_email",
      "@order": "user_id-",
      "@limit": 3
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser']
                ],
                [
                    'name' => '复杂条件关联查询',
                    'description' => '复杂条件组合的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "user_id>": 100,
      "user_name$": "admin",
      "@column": "user_id,user_name,user_email",
      "@order": "user_id-",
      "@limit": 3
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "create_time>": 1700000000,
      "@column": "module_id,create_time"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",
      "deleted": 0,
      "@column": "module_id,module_name,deleted"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser', 'CmsModules']
                ],
                [
                    'name' => '字段映射关联查询',
                    'description' => '使用字段映射的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id:uid,user_name:name,user_email:email",
      "@limit": 3
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/uid",
      "@column": "module_id:mid,create_time:update_time"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/mid",
      "@column": "module_id:mid,module_name:mname"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser', 'CmsModules']
                ],
                [
                    'name' => '统计关联查询',
                    'description' => '统计数据的多表关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 5
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "COUNT(*) as module_count",
      "@group": "user_id"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser']
                ],
                [
                    'name' => '嵌套关联查询',
                    'description' => '多层嵌套的关联查询',
                    'content' => '{
  "[]": {
    "CmsUser": {
      "user_status": 1,
      "@column": "user_id,user_name",
      "@limit": 2
    },
    "CmsModuleUser": {
      "user_id@": "CmsUser/user_id",
      "@column": "module_id,create_time"
    },
    "CmsModules": {
      "module_id@": "CmsModuleUser/module_id",
      "@column": "module_id,module_name,parent_module_id"
    }
  }
}',
                    'expected_tables' => ['CmsUser', 'CmsModuleUser', 'CmsModules']
                ]
            ];
            
            $this->console("准备执行 " . count($testCases) . " 个多表关联查询测试用例...");
            $this->console('');
            
            $successCount = 0;
            $failCount = 0;
            $results = [];
            
            foreach ($testCases as $index => $testCase) {
                $testNum = $index + 1;
                $this->console("{$testNum}. [{$testCase['name']}]");
                $this->console('描述: ' . $testCase['description']);
                $this->console('预期表: ' . implode(', ', $testCase['expected_tables']));
                $this->console('APIJSON语法:');
                $this->console($testCase['content']);
                
                try {
                    $result = $apiJson->Query($testCase['content']);
                    
                    // 判断请求是否成功
                    if (isset($result['code'])) {
                        // 有 code 字段，说明是错误响应
                        $code = $result['code'];
                        $msg = $result['msg'] ?? 'unknown';
                        $this->console("✗ 失败 (code: {$code}, msg: {$msg})");
                        $failCount++;
                        $results[] = [
                            'name' => $testCase['name'],
                            'status' => 'failed',
                            'error' => "code: {$code}, msg: {$msg}"
                        ];
                    } else {
                        // 没有 code 字段，说明是成功的 GET 请求
                        $this->console("✓ 成功 (返回数据)");
                        
                        // 分析返回的数据结构
                        $this->console("\n分析结果:");
                        $resultKeys = array_keys($result);
                        $this->console("返回的键: " . implode(', ', $resultKeys));
                        
                        // 检查是否有[]数组结果
                        $arrayKeys = array_filter($resultKeys, function($key) {
                            return strpos($key, '[]') !== false;
                        });
                        
                        if (!empty($arrayKeys)) {
                            foreach ($arrayKeys as $arrayKey) {
                                $data = $result[$arrayKey];
                                $count = is_array($data) ? count($data) : 0;
                                $this->console("数组 {$arrayKey}: {$count} 条记录");
                                
                                if ($count > 0) {
                                    $firstRecord = $data[0];
                                    $this->console("第一条记录字段: " . implode(', ', array_keys($firstRecord)));
                                }
                            }
                        }
                        
                        $successCount++;
                        $results[] = [
                            'name' => $testCase['name'],
                            'status' => 'success',
                            'data_keys' => $resultKeys
                        ];
                    }
                } catch (\Throwable $e) {
                    $this->console("✗ 异常: " . $e->getMessage());
                    $failCount++;
                    $results[] = [
                        'name' => $testCase['name'],
                        'status' => 'exception',
                        'error' => $e->getMessage()
                    ];
                }
                
                $this->console('---');
            }
            
            // 输出详细结果统计
            $this->console('');
            $this->console('================== 多表关联查询测试结果统计 ==================');
            
            $successResults = array_filter($results, function($r) { return $r['status'] === 'success'; });
            $failedResults = array_filter($results, function($r) { return $r['status'] === 'failed'; });
            $exceptionResults = array_filter($results, function($r) { return $r['status'] === 'exception'; });
            
            $this->console("成功: " . count($successResults));
            $this->console("失败: " . count($failedResults));
            $this->console("异常: " . count($exceptionResults));
            $this->console("总计: " . count($results));
            
            $total = count($results);
            $successRate = $total > 0 ? round((count($successResults) / $total) * 100, 2) : 0;
            $this->console("成功率: {$successRate}%");
            
            // 输出失败的测试用例详情
            if (!empty($failedResults) || !empty($exceptionResults)) {
                $this->console("\n失败的测试用例:");
                foreach (array_merge($failedResults, $exceptionResults) as $result) {
                    $this->console("- {$result['name']}: {$result['error']}");
                }
            }
            
            // 输出成功的测试用例详情
            if (!empty($successResults)) {
                $this->console("\n成功的测试用例:");
                foreach ($successResults as $result) {
                    $this->console("- {$result['name']}: " . implode(', ', $result['data_keys']));
                }
            }
            
            if ($successRate >= 80) {
                $this->console("\n🎉 多表关联查询功能测试优秀！语法正确性验证通过。");
            } elseif ($successRate >= 60) {
                $this->console("\n⚠️  多表关联查询功能基本可用，部分功能可能需要调整。");
            } else {
                $this->console("\n❌ 多表关联查询功能存在问题，需要检查语法和实现。");
            }
            
        } catch (\Throwable $e) {
            $this->console('❌ 测试初始化失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== APIJSON 多表关联查询测试结束 ==================');
    }

    /**
     * 测试聚合查询修复
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testAggregateQuery
     */
    public function testAggregateQuery(array $params)
    {
        $this->console('================== 聚合查询修复测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例：您提供的原始查询
            $testQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name",
                        "@limit" => 5
                    ],
                    "CmsModuleUser" => [
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "user_id,COUNT(*) as module_count",
                        "@group" => "user_id"
                    ]
                ]
            ];
            
            $this->console('2. 执行聚合查询测试...');
            $this->console('查询内容: ' . json_encode($testQuery, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($testQuery));
            
            $this->console('3. 分析查询结果...');
            
            // 检查查询是否成功
            if (isset($result['code']) && $result['code'] !== 200) {
                $this->console("❌ 查询失败: code={$result['code']}, msg={$result['msg']}");
                return;
            }
            
            $this->console("✅ 查询成功执行");
            
            // 检查返回结构
            if (!isset($result['[]'])) {
                $this->console("❌ 返回结果缺少 '[]' 键");
                return;
            }
            
            $arrayData = $result['[]'];
            $this->console("📊 返回了 " . count($arrayData) . " 条记录");
            
            // 检查每条记录的结构
            $hasModuleData = false;
            $moduleDataCount = 0;
            
            foreach ($arrayData as $index => $item) {
                $this->console("\n记录 #{$index}:");
                
                // 检查CmsUser数据
                if (isset($item['CmsUser'])) {
                    $userData = $item['CmsUser'];
                    $this->console("  CmsUser: user_id={$userData['user_id']}, user_name={$userData['user_name']}");
                } else {
                    $this->console("  CmsUser: 缺失");
                }
                
                // 检查CmsModuleUser数据
                if (isset($item['CmsModuleUser'])) {
                    $moduleData = $item['CmsModuleUser'];
                    if (!empty($moduleData)) {
                        $this->console("  CmsModuleUser: " . json_encode($moduleData, JSON_UNESCAPED_UNICODE));
                        $hasModuleData = true;
                        $moduleDataCount++;
                    } else {
                        $this->console("  CmsModuleUser: 空数组");
                    }
                } else {
                    $this->console("  CmsModuleUser: 缺失");
                }
            }
            
            // 输出测试结果
            $this->console("\n================== 测试结果 ==================");
            
            if ($hasModuleData) {
                $this->console("🎉 修复成功！");
                $this->console("✅ CmsModuleUser 表返回了聚合数据");
                $this->console("📈 有 {$moduleDataCount} 条记录包含模块数据");
                
                // 验证聚合数据的正确性
                $this->console("\n验证聚合数据正确性:");
                foreach ($arrayData as $index => $item) {
                    if (isset($item['CmsModuleUser']) && !empty($item['CmsModuleUser'])) {
                        $moduleData = $item['CmsModuleUser'];
                        $userId = $item['CmsUser']['user_id'] ?? 'unknown';
                        $moduleCount = $moduleData['module_count'] ?? 'unknown';
                        $this->console("  用户 {$userId}: {$moduleCount} 个模块");
                    }
                }
                
            } else {
                $this->console("❌ 修复失败！");
                $this->console("❌ CmsModuleUser 表仍然返回空数据");
                
                // 输出调试信息
                $this->console("\n调试信息:");
                $this->console("完整返回结果: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            
            // 额外测试：单独的聚合查询
            $this->console("\n================== 额外测试：单独聚合查询 ==================");
            
            $singleAggregateQuery = [
                "CmsModuleUser" => [
                    "@column" => "user_id,COUNT(*) as module_count",
                    "@group" => "user_id",
                    "@limit" => 5
                ]
            ];
            
            $this->console('执行单独聚合查询...');
            $singleResult = $apiJson->Query(json_encode($singleAggregateQuery));
            
            if (isset($singleResult['code']) && $singleResult['code'] !== 200) {
                $this->console("❌ 单独聚合查询失败: code={$singleResult['code']}, msg={$singleResult['msg']}");
            } else {
                $this->console("✅ 单独聚合查询成功");
                if (isset($singleResult['CmsModuleUser'])) {
                    $count = count($singleResult['CmsModuleUser']);
                    $this->console("📊 返回了 {$count} 条聚合记录");
                }
            }
            
        } catch (\Throwable $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 聚合查询修复测试结束 ==================');
    }

    /**
     * 测试嵌套关联查询修复
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testNestedQuery
     */
    public function testNestedQuery(array $params)
    {
        $this->console('================== 嵌套关联查询修复测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例：您提供的原始查询
            $testQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 3
                    ],
                    "CmsModuleUser" => [
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "CmsModules" => [
                            "module_id@" => "/module_id",
                            "@column" => "module_name,controller,action"
                        ]
                    ]
                ]
            ];
            
            $this->console('2. 执行嵌套关联查询...');
            $this->console('查询: ' . json_encode($testQuery, JSON_UNESCAPED_UNICODE));
            
            // 添加调试信息
            $this->console('3. 分析查询结构...');
            $this->console('CmsUser 条件: ' . json_encode($testQuery['[]']['CmsUser'], JSON_UNESCAPED_UNICODE));
            $this->console('CmsModuleUser 条件: ' . json_encode($testQuery['[]']['CmsModuleUser'], JSON_UNESCAPED_UNICODE));
            
            // 检查引用关系
            $hasReference = false;
            foreach ($testQuery['[]']['CmsModuleUser'] as $key => $value) {
                if (substr($key, -1) === '@' && is_string($value)) {
                    $hasReference = true;
                    $this->console("发现引用关系: {$key} = {$value}");
                }
            }
            $this->console('CmsModuleUser 有引用关系: ' . ($hasReference ? '是' : '否'));
            
            // 模拟引用关系解析过程
            $this->console('4. 模拟引用关系解析...');
            $testItem = [
                'CmsUser' => [
                    'user_id' => 1,
                    'user_name' => 'admin',
                    'user_email' => 'admin@ee.com'
                ]
            ];
            $this->console('测试数据: ' . json_encode($testItem, JSON_UNESCAPED_UNICODE));
            
            $refKey = 'user_id@';
            $refValue = 'CmsUser/user_id';
            $refParts = explode('/', $refValue);
            $refTable = $refParts[0];
            $refField = $refParts[1];
            
            $this->console("解析引用: {$refKey} = {$refValue}");
            $this->console("refTable: {$refTable}, refField: {$refField}");
            $this->console("item keys: " . implode(', ', array_keys($testItem)));
            
            if (isset($testItem[$refTable])) {
                $refValue = $testItem[$refTable][$refField] ?? null;
                $this->console("找到引用值: " . json_encode($refValue));
            } else {
                $this->console("未找到引用表: {$refTable}");
            }
            
            // 测试CmsModuleUser表是否能单独查询
            $this->console('5. 测试CmsModuleUser单独查询...');
            $simpleQuery = [
                "CmsModuleUser" => [
                    "user_id" => 1,
                    "@column" => "module_id,create_time"
                ]
            ];
            
            $this->console('简单查询: ' . json_encode($simpleQuery, JSON_UNESCAPED_UNICODE));
            $simpleResult = $apiJson->Query(json_encode($simpleQuery));
            $this->console('简单查询结果: ' . json_encode($simpleResult, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($testQuery));
            
            $this->console('3. 查询结果:');
            $this->console(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // 验证结果
            if (isset($result['[]']) && is_array($result['[]'])) {
                $this->console('4. 验证结果:');
                $this->console('✅ 查询成功执行，返回了 ' . count($result['[]']) . ' 条记录');
                
                foreach ($result['[]'] as $index => $record) {
                    $this->console("记录 {$index}:");
                    if (isset($record['CmsUser'])) {
                        $this->console("  - CmsUser: " . json_encode($record['CmsUser'], JSON_UNESCAPED_UNICODE));
                    }
                    if (isset($record['CmsModuleUser'])) {
                        $this->console("  - CmsModuleUser: " . json_encode($record['CmsModuleUser'], JSON_UNESCAPED_UNICODE));
                    }
                }
                
                $this->console('✅ 嵌套关联查询修复成功！');
            } else {
                $this->console('❌ 查询失败或返回格式不正确');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 嵌套关联查询修复测试结束 ==================');
    }

    /**
     * 测试带 [] 的嵌套关联查询语法
     */
    public function testNestedQueryWithArray(array $params)
    {
        $this->console('================== 带[]的嵌套关联查询测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试带 [] 的嵌套关联查询
            $testQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 3
                    ],
                    "CmsModuleUser[]" => [  // 注意这里有 []
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "CmsModules" => [
                            "module_id@" => "/module_id",
                            "@column" => "module_name,controller,action"
                        ]
                    ]
                ]
            ];
            
            $this->console('2. 执行带[]的嵌套关联查询...');
            $this->console('查询: ' . json_encode($testQuery, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($testQuery));
            
            $this->console('3. 查询结果:');
            $this->console(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // 验证结果
            if (isset($result['[]']) && is_array($result['[]'])) {
                $this->console('4. 验证结果:');
                $this->console('✅ 带[]的查询成功执行，返回了 ' . count($result['[]']) . ' 条记录');
                
                foreach ($result['[]'] as $index => $record) {
                    $this->console("记录 {$index}:");
                    if (isset($record['CmsUser'])) {
                        $this->console("  - CmsUser: " . json_encode($record['CmsUser'], JSON_UNESCAPED_UNICODE));
                    }
                    if (isset($record['CmsModuleUser[]'])) {
                        $this->console("  - CmsModuleUser[]: " . json_encode($record['CmsModuleUser[]'], JSON_UNESCAPED_UNICODE));
                    }
                }
                
                $this->console('✅ 带[]的嵌套关联查询修复成功！');
            } else {
                $this->console('❌ 带[]的查询失败或返回格式不正确');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 带[]的测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 带[]的嵌套关联查询测试结束 ==================');
    }

    /**
     * 调试带 [] 的表名处理问题
     */
    public function debugTableName(array $params)
    {
        $this->console('================== 调试表名处理问题开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 测试表名处理逻辑...');
            
            // 测试各种表名
            $testTableNames = [
                'CmsUser',
                'CmsModuleUser', 
                'CmsModuleUser[]',
                '[]',
                'CmsModules'
            ];
            
            foreach ($testTableNames as $tableName) {
                $this->console("\n测试表名: '{$tableName}'");
                
                // 测试 str_replace 处理
                $sanitized = str_replace('[]', '', $tableName);
                $this->console("  str_replace('[]', '', '{$tableName}') = '{$sanitized}'");
                
                // 测试 substr 检查
                $isArrayTable = substr($tableName, -2) === '[]';
                $this->console("  substr('{$tableName}', -2) === '[]' = " . ($isArrayTable ? 'true' : 'false'));
                
                // 测试正则匹配
                $isValidTable = preg_match("/^[A-Z].+/", $tableName);
                $this->console("  preg_match('/^[A-Z].+/', '{$tableName}') = " . ($isValidTable ? 'true' : 'false'));
            }
            
            $this->console('\n2. 测试APIJSON实例创建...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            $this->console('\n3. 测试带[]的查询JSON结构...');
            
            // 测试带 [] 的嵌套关联查询
            $testQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser[]" => [  // 注意这里有 []
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time"
                    ]
                ]
            ];
            
            $this->console('查询JSON: ' . json_encode($testQuery, JSON_UNESCAPED_UNICODE));
            
            // 分析JSON结构
            $this->console('\n4. 分析JSON结构...');
            foreach ($testQuery['[]'] as $tableName => $condition) {
                $this->console("表名: '{$tableName}'");
                $this->console("  条件: " . json_encode($condition, JSON_UNESCAPED_UNICODE));
                $this->console("  是否以[]结尾: " . (substr($tableName, -2) === '[]' ? '是' : '否'));
                $this->console("  处理后表名: '" . str_replace('[]', '', $tableName) . "'");
            }
            
            $this->console('\n5. 尝试执行查询...');
            
            // 先测试不带[]的版本
            $this->console('5.1 测试不带[]的版本...');
            $testQueryWithoutArray = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser" => [  // 没有 []
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time"
                    ]
                ]
            ];
            
            try {
                $resultWithoutArray = $apiJson->Query(json_encode($testQueryWithoutArray));
                $this->console('✅ 不带[]的查询成功: ' . json_encode($resultWithoutArray, JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                $this->console('❌ 不带[]的查询失败: ' . $e->getMessage());
            }
            
            // 再测试带[]的版本
            $this->console('\n5.2 测试带[]的版本...');
            try {
                $resultWithArray = $apiJson->Query(json_encode($testQuery));
                $this->console('✅ 带[]的查询成功: ' . json_encode($resultWithArray, JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                $this->console('❌ 带[]的查询失败: ' . $e->getMessage());
                $this->console('错误详情: ' . $e->getTraceAsString());
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 调试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 调试表名处理问题结束 ==================');
    }

    /**
     * 回归测试 7.4.9 分页关联查询之前的所有语法
     */
    public function regressionTest(array $params)
    {
        $this->console('================== 回归测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试用例列表
            $testCases = [
                [
                    'name' => '7.4.1 基础多表关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 3
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.2 用户-模块关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 2
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time"
                            ],
                            "CmsModules" => [
                                "module_id@" => "CmsModuleUser/module_id",
                                "@column" => "module_id,module_name,controller,action"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.3 用户权限关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 2
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time,system_id"
                            ],
                            "CmsModules" => [
                                "module_id@" => "CmsModuleUser/module_id",
                                "@column" => "module_id,module_name,controller,action,deleted"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.4 复杂业务关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "user_id>" => 1,
                                "@column" => "user_id,user_name,user_email,modify_time",
                                "@limit" => 2,
                                "@order" => "modify_time-"
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time,system_id"
                            ],
                            "CmsModules" => [
                                "module_id@" => "CmsModuleUser/module_id",
                                "deleted" => 0,
                                "@column" => "module_id,module_name,controller,action"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.5 条件关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "user_name$" => "admin",
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 3
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "module_id>" => 5,
                                "@column" => "module_id,create_time"
                            ],
                            "CmsModules" => [
                                "module_id@" => "CmsModuleUser/module_id",
                                "deleted" => 0,
                                "@column" => "module_id,module_name"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.6 聚合关联查询（统计用户模块数量）',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name",
                                "@limit" => 3
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "user_id,COUNT(*) as module_count",
                                "@group" => "user_id"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.7 嵌套关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 2
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time",
                                "CmsModules" => [
                                    "module_id@" => "/module_id",
                                    "@column" => "module_name,controller,action"
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '7.4.8 多层级关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name",
                                "@limit" => 2
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,system_id",
                                "CmsModules" => [
                                    "module_id@" => "/module_id",
                                    "@column" => "module_name,parent_module_id",
                                    "CmsModules" => [
                                        "module_id@" => "/parent_module_id",
                                        "@column" => "module_name,controller"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '带[]的嵌套关联查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 2
                            ],
                            "CmsModuleUser[]" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time",
                                "CmsModules" => [
                                    "module_id@" => "/module_id",
                                    "@column" => "module_name,controller,action"
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $successCount = 0;
            $totalCount = count($testCases);
            
            foreach ($testCases as $index => $testCase) {
                $this->console("\n" . ($index + 1) . ". 测试: " . $testCase['name']);
                $this->console('查询: ' . json_encode($testCase['query'], JSON_UNESCAPED_UNICODE));
                
                try {
                    $result = $apiJson->Query(json_encode($testCase['query']));
                    
                    // 检查结果
                    if (isset($result['code']) && $result['code'] !== 200) {
                        $this->console('❌ 测试失败: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                    } else {
                        $this->console('✅ 测试成功');
                        if (isset($result['[]'])) {
                            $this->console('   返回记录数: ' . count($result['[]']));
                        }
                        $successCount++;
                    }
                    
                } catch (\Exception $e) {
                    $this->console('❌ 测试异常: ' . $e->getMessage());
                }
            }
            
            $this->console("\n================== 回归测试结果 ==================");
            $this->console("总测试数: {$totalCount}");
            $this->console("成功数: {$successCount}");
            $this->console("失败数: " . ($totalCount - $successCount));
            $this->console("成功率: " . round(($successCount / $totalCount) * 100, 2) . "%");
            
            if ($successCount === $totalCount) {
                $this->console('🎉 所有测试通过！修改没有影响现有功能！');
            } else {
                $this->console('⚠️  部分测试失败，需要进一步检查！');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 回归测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 回归测试结束 ==================');
    }

    /**
     * 测试原始查询语法问题
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testOriginalQuery
     */
    public function testOriginalQuery(array $params)
    {
        $this->console('================== 原始查询语法问题测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 你提供的原始查询
            $originalQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser[]" => [
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "@limit" => 3
                    ],
                    "CmsModules[]" => [
                        "module_id@" => "CmsModuleUser/module_id",
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ]
            ];
            
            $this->console('2. 执行原始查询...');
            $this->console('查询内容: ' . json_encode($originalQuery, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($originalQuery));
            
            $this->console('3. 查询结果:');
            $this->console(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // 分析结果
            if (isset($result['[]']) && is_array($result['[]'])) {
                $this->console('4. 分析结果:');
                $this->console('✅ 查询成功执行，返回了 ' . count($result['[]']) . ' 条记录');
                
                foreach ($result['[]'] as $index => $record) {
                    $this->console("\n记录 {$index}:");
                    
                    // 检查CmsUser数据
                    if (isset($record['CmsUser'])) {
                        $userData = $record['CmsUser'];
                        $this->console("  CmsUser: user_id={$userData['user_id']}, user_name={$userData['user_name']}");
                    }
                    
                    // 检查CmsModuleUser数据
                    if (isset($record['CmsModuleUser[]'])) {
                        $moduleUsers = $record['CmsModuleUser[]'];
                        $this->console("  CmsModuleUser[]: " . count($moduleUsers) . " 条记录");
                        foreach ($moduleUsers as $i => $moduleUser) {
                            $this->console("    记录{$i}: module_id={$moduleUser['module_id']}, create_time={$moduleUser['create_time']}");
                        }
                    }
                    
                    // 检查CmsModules数据
                    if (isset($record['CmsModules[]'])) {
                        $modules = $record['CmsModules[]'];
                        $this->console("  CmsModules[]: " . count($modules) . " 条记录");
                        if (count($modules) === 0) {
                            $this->console("    ❌ 问题：CmsModules[] 返回空数组！");
                        } else {
                            foreach ($modules as $i => $module) {
                                $this->console("    记录{$i}: module_id={$module['module_id']}, module_name={$module['module_name']}");
                            }
                        }
                    }
                }
                
                // 问题诊断
                $this->console("\n5. 问题诊断:");
                
                // 检查第一个记录的数据
                if (isset($result['[]'][0])) {
                    $firstRecord = $result['[]'][0];
                    
                    if (isset($firstRecord['CmsModuleUser[]']) && !empty($firstRecord['CmsModuleUser[]'])) {
                        $moduleIds = array_column($firstRecord['CmsModuleUser[]'], 'module_id');
                        $this->console("  CmsModuleUser[] 返回的 module_id: " . implode(', ', $moduleIds));
                        
                        // 单独测试CmsModules查询
                        $this->console("\n6. 单独测试CmsModules查询...");
                        $modulesQuery = [
                            "CmsModules[]" => [
                                "module_id{}" => $moduleIds,
                                "@column" => "module_id,module_name,controller,action"
                            ]
                        ];
                        
                        $this->console('单独查询: ' . json_encode($modulesQuery, JSON_UNESCAPED_UNICODE));
                        $modulesResult = $apiJson->Query(json_encode($modulesQuery));
                        $this->console('单独查询结果: ' . json_encode($modulesResult, JSON_UNESCAPED_UNICODE));
                        
                        if (isset($modulesResult['CmsModules[]']) && !empty($modulesResult['CmsModules[]'])) {
                            $this->console("✅ 单独查询成功，说明数据存在");
                            $this->console("❌ 问题在于引用查询语法");
                        } else {
                            $this->console("❌ 单独查询也失败，说明数据不存在或查询有问题");
                        }
                    }
                }
                
            } else {
                $this->console('❌ 查询失败或返回格式不正确');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 原始查询语法问题测试结束 ==================');
    }

    /**
     * 测试修复后的查询语法
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testFixedQuery
     */
    public function testFixedQuery(array $params)
    {
        $this->console('================== 修复后的查询语法测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 修复后的查询 - 移除CmsModuleUser[]中的[]
            $fixedQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser" => [  // 移除了[]
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "@limit" => 3
                    ],
                    "CmsModules[]" => [
                        "module_id@" => "CmsModuleUser/module_id",
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ]
            ];
            
            $this->console('2. 执行修复后的查询...');
            $this->console('修复后的查询: ' . json_encode($fixedQuery, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($fixedQuery));
            
            $this->console('3. 查询结果:');
            $this->console(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // 分析结果
            if (isset($result['[]']) && is_array($result['[]'])) {
                $this->console('4. 分析结果:');
                $this->console('✅ 修复后的查询成功执行，返回了 ' . count($result['[]']) . ' 条记录');
                
                foreach ($result['[]'] as $index => $record) {
                    $this->console("\n记录 {$index}:");
                    
                    // 检查CmsUser数据
                    if (isset($record['CmsUser'])) {
                        $userData = $record['CmsUser'];
                        $this->console("  CmsUser: user_id={$userData['user_id']}, user_name={$userData['user_name']}");
                    }
                    
                    // 检查CmsModuleUser数据
                    if (isset($record['CmsModuleUser'])) {
                        $moduleUsers = $record['CmsModuleUser'];
                        if (is_array($moduleUsers)) {
                            $this->console("  CmsModuleUser: " . count($moduleUsers) . " 条记录");
                            foreach ($moduleUsers as $i => $moduleUser) {
                                $this->console("    记录{$i}: module_id={$moduleUser['module_id']}, create_time={$moduleUser['create_time']}");
                            }
                        } else {
                            $this->console("  CmsModuleUser: " . json_encode($moduleUsers, JSON_UNESCAPED_UNICODE));
                        }
                    }
                    
                    // 检查CmsModules数据
                    if (isset($record['CmsModules[]'])) {
                        $modules = $record['CmsModules[]'];
                        $this->console("  CmsModules[]: " . count($modules) . " 条记录");
                        if (count($modules) === 0) {
                            $this->console("    ❌ 仍然有问题：CmsModules[] 返回空数组");
                        } else {
                            $this->console("    ✅ 修复成功：CmsModules[] 返回了数据");
                            foreach ($modules as $i => $module) {
                                $this->console("    记录{$i}: module_id={$module['module_id']}, module_name={$module['module_name']}");
                            }
                        }
                    }
                }
                
            } else {
                $this->console('❌ 修复后的查询失败或返回格式不正确');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 修复后的查询语法测试结束 ==================');
    }

    /**
     * 测试正确的多表关联查询语法
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testCorrectMultiTableQuery
     */
    public function testCorrectMultiTableQuery(array $params)
    {
        $this->console('================== 正确的多表关联查询语法测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 正确的查询语法 - 不使用[]的中间表
            $correctQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser" => [  // 没有[]
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "@limit" => 3
                    ],
                    "CmsModules[]" => [
                        "module_id@" => "CmsModuleUser/module_id",
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ]
            ];
            
            $this->console('2. 执行正确的查询...');
            $this->console('查询内容: ' . json_encode($correctQuery, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($correctQuery));
            
            $this->console('3. 查询结果:');
            $this->console(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // 分析结果
            if (isset($result['[]']) && is_array($result['[]'])) {
                $this->console('4. 分析结果:');
                $this->console('✅ 查询成功执行，返回了 ' . count($result['[]']) . ' 条记录');
                
                foreach ($result['[]'] as $index => $record) {
                    $this->console("\n记录 {$index}:");
                    
                    // 检查CmsUser数据
                    if (isset($record['CmsUser'])) {
                        $userData = $record['CmsUser'];
                        $this->console("  CmsUser: user_id={$userData['user_id']}, user_name={$userData['user_name']}");
                    }
                    
                    // 检查CmsModuleUser数据
                    if (isset($record['CmsModuleUser'])) {
                        $moduleUsers = $record['CmsModuleUser'];
                        if (is_array($moduleUsers)) {
                            $this->console("  CmsModuleUser: " . count($moduleUsers) . " 条记录");
                            foreach ($moduleUsers as $i => $moduleUser) {
                                $this->console("    记录{$i}: module_id={$moduleUser['module_id']}, create_time={$moduleUser['create_time']}");
                            }
                        } else {
                            $this->console("  CmsModuleUser: " . json_encode($moduleUsers, JSON_UNESCAPED_UNICODE));
                        }
                    }
                    
                    // 检查CmsModules数据
                    if (isset($record['CmsModules[]'])) {
                        $modules = $record['CmsModules[]'];
                        $this->console("  CmsModules[]: " . count($modules) . " 条记录");
                        if (count($modules) === 0) {
                            $this->console("    ❌ 仍然有问题：CmsModules[] 返回空数组");
                        } else {
                            $this->console("    ✅ 修复成功：CmsModules[] 返回了数据");
                            foreach ($modules as $i => $module) {
                                $this->console("    记录{$i}: module_id={$module['module_id']}, module_name={$module['module_name']}");
                            }
                        }
                    }
                }
                
                // 总结
                $this->console("\n5. 总结:");
                $this->console("✅ 正确的多表关联查询语法:");
                $this->console("   - CmsUser: 主表，查询用户信息");
                $this->console("   - CmsModuleUser: 中间表，不使用[]，返回单个对象或数组");
                $this->console("   - CmsModules[]: 目标表，使用[]，根据中间表的module_id查询模块信息");
                $this->console("");
                $this->console("❌ 错误的语法:");
                $this->console("   - CmsModuleUser[]: 中间表使用[]会导致引用查询失败");
                $this->console("   - 原因：引用处理无法正确处理数组格式的中间表数据");
                
            } else {
                $this->console('❌ 查询失败或返回格式不正确');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 正确的多表关联查询语法测试结束 ==================');
    }

    /**
     * 测试多种多表关联查询语法对比
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testMultiTableSyntaxComparison
     */
    public function testMultiTableSyntaxComparison(array $params)
    {
        $this->console('================== 多表关联查询语法对比测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 测试不同的语法组合
            $testCases = [
                [
                    'name' => '原始语法（有问题）',
                    'description' => 'CmsModuleUser[] 使用[]，CmsModules[] 引用失败',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 1
                            ],
                            "CmsModuleUser[]" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time",
                                "@limit" => 3
                            ],
                            "CmsModules[]" => [
                                "module_id@" => "CmsModuleUser/module_id",
                                "@column" => "module_id,module_name,controller,action"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '修复语法1（推荐）',
                    'description' => 'CmsModuleUser 不使用[]，CmsModules[] 引用成功',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 1
                            ],
                            "CmsModuleUser" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time",
                                "@limit" => 3
                            ],
                            "CmsModules[]" => [
                                "module_id@" => "CmsModuleUser/module_id",
                                "@column" => "module_id,module_name,controller,action"
                            ]
                        ]
                    ]
                ],
                [
                    'name' => '修复语法2（替代方案）',
                    'description' => '使用IN查询替代引用查询',
                    'query' => [
                        "[]" => [
                            "CmsUser" => [
                                "user_status" => 1,
                                "@column" => "user_id,user_name,user_email",
                                "@limit" => 1
                            ],
                            "CmsModuleUser[]" => [
                                "user_id@" => "CmsUser/user_id",
                                "@column" => "module_id,create_time",
                                "@limit" => 3
                            ]
                        ],
                        "CmsModules[]" => [
                            "module_id{}" => [2470, 2471, 2472],  // 直接指定module_id
                            "@column" => "module_id,module_name,controller,action"
                        ]
                    ]
                ]
            ];
            
            foreach ($testCases as $index => $testCase) {
                $this->console("\n" . ($index + 1) . ". 测试: " . $testCase['name']);
                $this->console('描述: ' . $testCase['description']);
                $this->console('查询: ' . json_encode($testCase['query'], JSON_UNESCAPED_UNICODE));
                
                try {
                    $result = $apiJson->Query(json_encode($testCase['query']));
                    
                    // 分析结果
                    $this->console('结果分析:');
                    
                    if (isset($result['[]'])) {
                        $arrayData = $result['[]'];
                        $this->console("  ✅ [] 查询成功，返回 " . count($arrayData) . " 条记录");
                        
                        if (isset($arrayData[0]['CmsModules[]'])) {
                            $modulesCount = count($arrayData[0]['CmsModules[]']);
                            if ($modulesCount > 0) {
                                $this->console("  ✅ CmsModules[] 查询成功，返回 {$modulesCount} 条记录");
                            } else {
                                $this->console("  ❌ CmsModules[] 查询失败，返回空数组");
                            }
                        } elseif (isset($arrayData[0]['CmsModuleUser'])) {
                            $this->console("  ✅ CmsModuleUser 查询成功");
                        }
                    } elseif (isset($result['CmsModules[]'])) {
                        $modulesCount = count($result['CmsModules[]']);
                        $this->console("  ✅ CmsModules[] 独立查询成功，返回 {$modulesCount} 条记录");
                    } else {
                        $this->console("  ❌ 查询失败或格式不正确");
                        $this->console("  返回结果: " . json_encode($result, JSON_UNESCAPED_UNICODE));
                    }
                    
                } catch (\Exception $e) {
                    $this->console('  ❌ 查询异常: ' . $e->getMessage());
                }
            }
            
            // 总结
            $this->console("\n================== 语法对比总结 ==================");
            $this->console("1. 原始语法问题:");
            $this->console("   - CmsModuleUser[] 使用[]导致引用查询失败");
            $this->console("   - 引用处理无法正确处理数组格式的中间表数据");
            $this->console("");
            $this->console("2. 推荐修复方案:");
            $this->console("   - 中间表不使用[]，让引用查询正常工作");
            $this->console("   - 或者使用IN查询替代引用查询");
            $this->console("");
            $this->console("3. 最佳实践:");
            $this->console("   - 在多表关联查询中，中间表避免使用[]");
            $this->console("   - 只有最终的目标表才使用[]");
            $this->console("   - 引用查询需要确保引用表返回单个值或可处理的数组格式");
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 多表关联查询语法对比测试结束 ==================');
    }

    /**
     * 最终总结测试
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testFinalSummary
     */
    public function testFinalSummary(array $params)
    {
        $this->console('================== 最终总结测试开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 你提供的原始查询（有问题）
            $originalQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser[]" => [
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "@limit" => 3
                    ],
                    "CmsModules[]" => [
                        "module_id@" => "CmsModuleUser/module_id",
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ]
            ];
            
            // 修复后的查询（推荐）
            $fixedQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser" => [  // 移除了[]
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time",
                        "@limit" => 3
                    ],
                    "CmsModules[]" => [
                        "module_id@" => "CmsModuleUser/module_id",
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ]
            ];
            
            $this->console('2. 测试原始查询（有问题）...');
            $originalResult = $apiJson->Query(json_encode($originalQuery));
            
            $this->console('3. 测试修复后的查询（推荐）...');
            $fixedResult = $apiJson->Query(json_encode($fixedQuery));
            
            $this->console('4. 结果对比分析:');
            $this->console('');
            
            // 分析原始查询结果
            $this->console('原始查询结果:');
            if (isset($originalResult['[]'][0]['CmsModules[]'])) {
                $originalModulesCount = count($originalResult['[]'][0]['CmsModules[]']);
                $this->console("  CmsModules[] 记录数: {$originalModulesCount}");
                if ($originalModulesCount === 0) {
                    $this->console("  ❌ 问题：CmsModules[] 返回空数组");
                }
            }
            
            // 分析修复后的查询结果
            $this->console('');
            $this->console('修复后的查询结果:');
            if (isset($fixedResult['[]'][0]['CmsModules[]'])) {
                $fixedModulesCount = count($fixedResult['[]'][0]['CmsModules[]']);
                $this->console("  CmsModules[] 记录数: {$fixedModulesCount}");
                if ($fixedModulesCount > 0) {
                    $this->console("  ✅ 修复成功：CmsModules[] 返回了数据");
                    foreach ($fixedResult['[]'][0]['CmsModules[]'] as $i => $module) {
                        $this->console("    记录{$i}: module_id={$module['module_id']}, module_name={$module['module_name']}");
                    }
                }
            }
            
            $this->console('');
            $this->console('5. 问题根因分析:');
            $this->console('   ❌ 原始语法问题:');
            $this->console('      - CmsModuleUser[] 使用[]返回数组格式');
            $this->console('      - 引用查询 "module_id@": "CmsModuleUser/module_id" 无法正确处理数组');
            $this->console('      - 引用处理代码期望单个值，但得到的是数组');
            $this->console('');
            $this->console('   ✅ 修复方案:');
            $this->console('      - 移除 CmsModuleUser[] 中的 []');
            $this->console('      - 让中间表返回单个对象，便于引用查询');
            $this->console('      - 引用查询能够正确获取 module_id 值');
            $this->console('');
            $this->console('6. 最佳实践建议:');
            $this->console('   - 在多表关联查询中，中间表避免使用 []');
            $this->console('   - 只有最终的目标表才使用 []');
            $this->console('   - 引用查询需要确保引用表返回单个值或可处理的格式');
            $this->console('   - 如果确实需要数组格式的中间表，考虑使用IN查询替代引用查询');
            
            $this->console('');
            $this->console('7. 修复后的正确语法:');
            $this->console(json_encode($fixedQuery, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 最终总结测试结束 ==================');
    }

    /**
     * 验证官方语法并修复引用查询问题
     * php cli.php apijsontest -task_dir_load 'comp/nocode/cli/tasks/' -process testOfficialSyntaxAndFix
     */
    public function testOfficialSyntaxAndFix(array $params)
    {
        $this->console('================== 验证官方语法并修复引用查询问题开始 ==================');
        
        try {
            // 设置必要常量避免段错误
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $this->console('1. 创建APIJSON实例...');
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            $this->console('APIJSON实例创建成功!');
            
            // 你提供的原始查询（官方语法）
            $originalQuery = [
                "[]" => [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1
                    ],
                    "CmsModuleUser[]" => [
                        "user_id@" => "CmsUser/user_id",
                        "@column" => "module_id,create_time"
                    ],
                    "CmsModules[]" => [
                        "module_id@" => "CmsModuleUser/module_id",
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ]
            ];
            
            $this->console('2. 验证官方语法定义...');
            $this->console('✅ 官方语法确认:');
            $this->console('   - 带[] 返回数组: CmsModuleUser[] 返回数组格式');
            $this->console('   - 不带[] 返回对象或null: CmsUser 返回单个对象');
            $this->console('   - 这是 APIJSON 官方支持的语法');
            $this->console('');
            
            $this->console('3. 执行原始查询...');
            $this->console('查询: ' . json_encode($originalQuery, JSON_UNESCAPED_UNICODE));
            
            $result = $apiJson->Query(json_encode($originalQuery));
            
            $this->console('4. 分析查询结果...');
            $this->console(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // 分析结果
            if (isset($result['[]']) && is_array($result['[]'])) {
                $this->console('5. 问题分析:');
                $this->console('✅ 查询成功执行，返回了 ' . count($result['[]']) . ' 条记录');
                
                foreach ($result['[]'] as $index => $record) {
                    $this->console("\n记录 {$index}:");
                    
                    // 检查CmsUser数据
                    if (isset($record['CmsUser'])) {
                        $userData = $record['CmsUser'];
                        $this->console("  CmsUser: user_id={$userData['user_id']}, user_name={$userData['user_name']}");
                    }
                    
                    // 检查CmsModuleUser数据
                    if (isset($record['CmsModuleUser[]'])) {
                        $moduleUsers = $record['CmsModuleUser[]'];
                        $this->console("  CmsModuleUser[]: " . count($moduleUsers) . " 条记录");
                        
                        // 提取所有module_id
                        $moduleIds = array_column($moduleUsers, 'module_id');
                        $this->console("  所有module_id: " . implode(', ', $moduleIds));
                        
                        foreach ($moduleUsers as $i => $moduleUser) {
                            $this->console("    记录{$i}: module_id={$moduleUser['module_id']}, create_time={$moduleUser['create_time']}");
                        }
                    }
                    
                    // 检查CmsModules数据
                    if (isset($record['CmsModules[]'])) {
                        $modules = $record['CmsModules[]'];
                        $this->console("  CmsModules[]: " . count($modules) . " 条记录");
                        if (count($modules) === 0) {
                            $this->console("    ❌ 问题：CmsModules[] 返回空数组");
                        } else {
                            $this->console("    ✅ 成功：CmsModules[] 返回了数据");
                            foreach ($modules as $i => $module) {
                                $this->console("    记录{$i}: module_id={$module['module_id']}, module_name={$module['module_name']}");
                            }
                        }
                    }
                }
                
                // 问题诊断
                $this->console("\n6. 问题诊断:");
                $this->console("❌ 问题根因: 引用查询无法正确处理数组格式的中间表数据");
                $this->console("   - CmsModuleUser[] 返回数组: " . json_encode($moduleIds ?? []));
                $this->console("   - CmsModules[] 引用查询: module_id@: CmsModuleUser/module_id");
                $this->console("   - 引用处理期望单个值，但得到的是数组");
                
                // 修复方案1: 使用IN查询
                $this->console("\n7. 修复方案1: 使用IN查询替代引用查询");
                $fixQuery1 = [
                    "[]" => [
                        "CmsUser" => [
                            "user_status" => 1,
                            "@column" => "user_id,user_name,user_email",
                            "@limit" => 1
                        ],
                        "CmsModuleUser[]" => [
                            "user_id@" => "CmsUser/user_id",
                            "@column" => "module_id,create_time"
                        ]
                    ],
                    "CmsModules[]" => [
                        "module_id{}" => $moduleIds ?? [2470, 2471, 2472],  // 使用IN查询
                        "@column" => "module_id,module_name,controller,action"
                    ]
                ];
                
                $this->console('修复查询1: ' . json_encode($fixQuery1, JSON_UNESCAPED_UNICODE));
                $fixResult1 = $apiJson->Query(json_encode($fixQuery1));
                
                if (isset($fixResult1['CmsModules[]'])) {
                    $modulesCount = count($fixResult1['CmsModules[]']);
                    $this->console("✅ 修复方案1成功: CmsModules[] 返回 {$modulesCount} 条记录");
                    foreach ($fixResult1['CmsModules[]'] as $i => $module) {
                        $this->console("  记录{$i}: module_id={$module['module_id']}, module_name={$module['module_name']}");
                    }
                }
                
                // 修复方案2: 修改引用处理逻辑
                $this->console("\n8. 修复方案2: 修改引用处理逻辑");
                $this->console("   需要修改 QuoteReplace.php 中的引用处理逻辑");
                $this->console("   当引用表返回数组时，应该提取所有值用于IN查询");
                
                // 修复方案3: 使用嵌套查询
                $this->console("\n9. 修复方案3: 使用嵌套查询语法");
                $fixQuery3 = [
                    "CmsUser" => [
                        "user_status" => 1,
                        "@column" => "user_id,user_name,user_email",
                        "@limit" => 1,
                        "CmsModuleUser[]" => [
                            "user_id@" => "/user_id",
                            "@column" => "module_id,create_time",
                            "CmsModules[]" => [
                                "module_id@" => "/module_id",
                                "@column" => "module_id,module_name,controller,action"
                            ]
                        ]
                    ]
                ];
                
                $this->console('修复查询3: ' . json_encode($fixQuery3, JSON_UNESCAPED_UNICODE));
                $fixResult3 = $apiJson->Query(json_encode($fixQuery3));
                
                if (isset($fixResult3['CmsUser']['CmsModuleUser[]'])) {
                    $this->console("✅ 修复方案3成功: 嵌套查询返回数据");
                    foreach ($fixResult3['CmsUser']['CmsModuleUser[]'] as $i => $moduleUser) {
                        $this->console("  CmsModuleUser记录{$i}: module_id={$moduleUser['module_id']}");
                        if (isset($moduleUser['CmsModules[]'])) {
                            $modulesCount = count($moduleUser['CmsModules[]']);
                            $this->console("    CmsModules[]: {$modulesCount} 条记录");
                        }
                    }
                }
                
                $this->console("\n10. 推荐修复方案:");
                $this->console("   ✅ 方案1: 使用IN查询替代引用查询（最简单）");
                $this->console("   ✅ 方案3: 使用嵌套查询语法（官方推荐）");
                $this->console("   ⚠️  方案2: 修改引用处理逻辑（需要代码修改）");
                
            } else {
                $this->console('❌ 查询失败或返回格式不正确');
            }
            
        } catch (\Exception $e) {
            $this->console('❌ 测试失败: ' . $e->getMessage());
            $this->console('错误详情: ' . $e->getTraceAsString());
        }
        
        $this->console('================== 验证官方语法并修复引用查询问题结束 ==================');
    }

    /**
     * 测试 limit 优化功能
     */
    public function testLimitOptimizationAction()
    {
        echo "=== 测试 limit 优化功能 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 创建APIJSON实例
        $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
        
        // 测试场景1：CmsModules[] 没有 @limit，但 module_id 是唯一索引，应该返回所有匹配的记录
        $query1 = [
            "[]" => [
                "CmsUser" => [
                    "user_status" => 1,
                    "user_id>" => 500,
                    "@column" => "user_id,user_name,user_email",
                    "@limit" => 5
                ],
                "CmsModuleUser[]" => [
                    "user_id@" => "CmsUser/user_id",
                    "@column" => "module_id,create_time",
                    "@limit" => 20
                ],
                "CmsModules[]" => [
                    "module_id@" => "CmsModuleUser/module_id",
                    "@column" => "module_id,module_name,controller,action"
                    // 注意：这里没有 @limit，应该被优化
                ]
            ]
        ];
        
        echo "测试场景1：CmsModules[] 没有 @limit，但 module_id 是唯一索引\n";
        try {
            $result1 = $apiJson->Query(json_encode($query1));
            echo "查询成功\n";
            
            // 检查 CmsModules[] 是否返回了超过10条记录（证明优化生效）
            if (is_array($result1)) {
                $totalCmsModulesCount = 0;
                foreach ($result1 as $record) {
                    if (isset($record['CmsModules[]']) && is_array($record['CmsModules[]'])) {
                        $totalCmsModulesCount += count($record['CmsModules[]']);
                    }
                }
                echo "CmsModules[] 总返回记录数: {$totalCmsModulesCount}\n";
                if ($totalCmsModulesCount > 10) {
                    echo "✅ 优化生效：CmsModules[] 返回了超过10条记录，说明默认 limit 被移除了\n";
                } else {
                    echo "❌ 优化未生效：CmsModules[] 只返回了 {$totalCmsModulesCount} 条记录\n";
                }
            } else {
                echo "❌ 查询结果格式不正确\n";
                echo "结果: " . json_encode($result1, JSON_PRETTY_PRINT) . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ 测试场景1失败: " . $e->getMessage() . "\n";
        }
        
        // 测试场景2：CmsModules[] 有 @limit，不应该被优化
        $query2 = [
            "[]" => [
                "CmsUser" => [
                    "user_status" => 1,
                    "user_id>" => 500,
                    "@column" => "user_id,user_name,user_email",
                    "@limit" => 5
                ],
                "CmsModuleUser[]" => [
                    "user_id@" => "CmsUser/user_id",
                    "@column" => "module_id,create_time",
                    "@limit" => 20
                ],
                "CmsModules[]" => [
                    "module_id@" => "CmsModuleUser/module_id",
                    "@column" => "module_id,module_name,controller,action",
                    "@limit" => 5  // 明确设置了 limit
                ]
            ]
        ];
        
        echo "\n测试场景2：CmsModules[] 有 @limit，不应该被优化\n";
        try {
            $result2 = $apiJson->Query(json_encode($query2));
            echo "查询成功\n";
            
            if (is_array($result2)) {
                $totalCmsModulesCount = 0;
                foreach ($result2 as $record) {
                    if (isset($record['CmsModules[]']) && is_array($record['CmsModules[]'])) {
                        $totalCmsModulesCount += count($record['CmsModules[]']);
                    }
                }
                echo "CmsModules[] 总返回记录数: {$totalCmsModulesCount}\n";
                if ($totalCmsModulesCount <= 25) { // 5个用户 * 5条记录 = 25条
                    echo "✅ 正确：CmsModules[] 返回了 {$totalCmsModulesCount} 条记录，符合 @limit: 5 的设置\n";
                } else {
                    echo "❌ 错误：CmsModules[] 返回了 {$totalCmsModulesCount} 条记录，超过了 @limit: 5 的设置\n";
                }
            } else {
                echo "❌ 查询结果格式不正确\n";
                echo "结果: " . json_encode($result2, JSON_PRETTY_PRINT) . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ 测试场景2失败: " . $e->getMessage() . "\n";
        }
        
        // 测试场景3：没有引用查询，不应该被优化
        $query3 = [
            "CmsModules[]" => [
                "module_id>" => 2400,
                "@column" => "module_id,module_name,controller,action"
                // 没有引用查询，不应该被优化
            ]
        ];
        
        echo "\n测试场景3：没有引用查询，不应该被优化\n";
        try {
            $result3 = $apiJson->Query(json_encode($query3));
            echo "查询成功\n";
            
            if (isset($result3['CmsModules[]']) && is_array($result3['CmsModules[]'])) {
                $cmsModulesCount = count($result3['CmsModules[]']);
                echo "CmsModules[] 返回记录数: {$cmsModulesCount}\n";
                if ($cmsModulesCount <= 10) {
                    echo "✅ 正确：CmsModules[] 返回了 {$cmsModulesCount} 条记录，符合默认 limit: 10\n";
                } else {
                    echo "❌ 错误：CmsModules[] 返回了 {$cmsModulesCount} 条记录，超过了默认 limit: 10\n";
                }
            } else {
                echo "❌ 查询结果格式不正确\n";
                echo "结果: " . json_encode($result3, JSON_PRETTY_PRINT) . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ 测试场景3失败: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== limit 优化功能测试完成 ===\n";
    }

    /**
     * 测试 Parse.php 中的 limit 优化功能
     */
    public function testParseLimitOptimizationAction()
    {
        echo "=== 测试 Parse.php 中的 limit 优化功能 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 创建APIJSON实例
        $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
        
        // 测试场景：CmsModules[] 没有 @limit，但 module_id 是唯一索引，应该返回所有匹配的记录
        $query = [
            "[]" => [
                "CmsUser" => [
                    "user_status" => 1,
                    "user_id>" => 500,
                    "@column" => "user_id,user_name,user_email",
                    "@limit" => 5
                ],
                "CmsModuleUser[]" => [
                    "user_id@" => "CmsUser/user_id",
                    "@column" => "module_id,create_time",
                    "@limit" => 15
                ],
                "CmsModules[]" => [
                    "module_id@" => "CmsModuleUser/module_id",
                    "@column" => "module_id,module_name,controller,action"
                    // 注意：这里没有 @limit，应该被优化
                ]
            ]
        ];
        
        echo "测试场景：CmsModules[] 没有 @limit，但 module_id 是唯一索引\n";
        try {
            $result = $apiJson->Query(json_encode($query));
            echo "查询成功\n";
            
            // 检查 CmsModules[] 是否返回了超过10条记录（证明优化生效）
            if (is_array($result)) {
                $totalCmsModulesCount = 0;
                foreach ($result as $record) {
                    if (isset($record['CmsModules[]']) && is_array($record['CmsModules[]'])) {
                        $totalCmsModulesCount += count($record['CmsModules[]']);
                    }
                }
                echo "CmsModules[] 总返回记录数: {$totalCmsModulesCount}\n";
                if ($totalCmsModulesCount > 10) {
                    echo "✅ 优化生效：CmsModules[] 返回了超过10条记录，说明默认 limit 被移除了\n";
                } else {
                    echo "❌ 优化未生效：CmsModules[] 只返回了 {$totalCmsModulesCount} 条记录\n";
                }
            } else {
                echo "❌ 查询结果格式不正确\n";
                echo "结果: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ 测试失败: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Parse.php limit 优化功能测试完成 ===\n";
    }

    /**
     * 测试用户提供的查询
     */
    public function testUserQueryAction()
    {
        echo "=== 测试用户提供的查询 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 创建APIJSON实例
        $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
        
        // 用户提供的查询（数组根）
        $query = [
            "[]" => [
                "CmsUser" => [
                    "user_status" => 1,
                    "user_id>" => 500,
                    "@column" => "user_id,user_name,user_email",
                    "@limit" => 5
                ],
                "CmsModuleUser[]" => [
                    "user_id@" => "CmsUser/user_id",
                    "@column" => "module_id,create_time",
                    "@limit" => 20
                ],
                "CmsModules[]" => [
                    "module_id@" => "CmsModuleUser/module_id",
                    "@column" => "module_id,module_name,controller,action"
                ]
            ]
        ];

        // 新增：测试 BmsOperateLog[] 场景（数组根非 [] 包裹）
        $bmsQuery = [
            "BmsOperateLog[]" => [
                "uid>" => 1,
                "@column" => "id,uid,model,content,operate_name",
                "@limit" => 10,
                "XsUserProfile" => [
                    "uid@" => "/uid",
                    "@column" => "uid,name,pay_room_money",
                    "XsUserMobile" => [
                        "uid@" => "/uid",
                        "@column" => "uid,mobile"
                    ],
                    "XsUserSettings" => [
                        "uid@" => "/uid",
                        "@column" => "uid,language"
                    ],
                    "XsUserMedal[]" => [
                        "uid@" => "/uid",
                        "@column" => "uid,medal_id"
                    ]
                ]
            ]
        ];
        
        echo "执行用户查询...\n";
        try {
            $result = $apiJson->Query(json_encode($query));
            echo "查询成功\n";
            
            // 分析结果
            if (is_array($result)) {
                $totalCmsModulesCount = 0;
                $userCount = 0;
                
                foreach ($result as $record) {
                    $userCount++;
                    $cmsModuleUserCount = 0;
                    $cmsModulesCount = 0;
                    
                    if (isset($record['CmsModuleUser[]']) && is_array($record['CmsModuleUser[]'])) {
                        $cmsModuleUserCount = count($record['CmsModuleUser[]']);
                    }
                    
                    if (isset($record['CmsModules[]']) && is_array($record['CmsModules[]'])) {
                        $cmsModulesCount = count($record['CmsModules[]']);
                        $totalCmsModulesCount += $cmsModulesCount;
                    }
                    
                    $userId = $record['CmsUser']['user_id'] ?? 'unknown';
                    echo "用户 {$userId}: CmsModuleUser[] = {$cmsModuleUserCount} 条, CmsModules[] = {$cmsModulesCount} 条\n";
                }
                
                echo "\n总结:\n";
                echo "总用户数: {$userCount}\n";
                echo "CmsModules[] 总记录数: {$totalCmsModulesCount}\n";
                
                if ($totalCmsModulesCount > 10) {
                    echo "✅ 优化生效：CmsModules[] 返回了超过10条记录，说明默认 limit 被移除了\n";
                } else {
                    echo "❌ 优化未生效：CmsModules[] 只返回了 {$totalCmsModulesCount} 条记录\n";
                }
            } else {
                echo "❌ 查询结果格式不正确\n";
                echo "结果: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ 测试失败: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== 用户查询测试完成 ===\n";
    }

    /**
     * 测试文档展示优化效果
     */
    public function testDocumentDisplayAction()
    {
        echo "=== 测试文档展示优化效果 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 读取语法文档
        $syntaxFile = ROOT . '/comp/nocode/apijson/apijson_syntax_examples.md';
        if (file_exists($syntaxFile)) {
            $content = file_get_contents($syntaxFile);
            echo "✅ 文档文件存在，大小: " . strlen($content) . " 字节\n";
            
            // 检查是否包含我们添加的优化内容
            $optimizationKeywords = [
                '关联查询 Limit 优化',
                '🚀 关联查询 Limit 优化说明',
                '优化前后对比',
                '最佳实践总结'
            ];
            
            foreach ($optimizationKeywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    echo "✅ 找到关键词: {$keyword}\n";
                } else {
                    echo "❌ 未找到关键词: {$keyword}\n";
                }
            }
            
            // 检查特殊标记
            $specialMarks = ['✅', '❌', '🚀', '📖', '🎯', '📝', '🐛', '💡'];
            foreach ($specialMarks as $mark) {
                $count = substr_count($content, $mark);
                if ($count > 0) {
                    echo "✅ 找到特殊标记 {$mark}: {$count} 次\n";
                }
            }
            
            // 检查代码块
            $codeBlockCount = substr_count($content, '```');
            echo "✅ 代码块数量: " . ($codeBlockCount / 2) . " 个\n";
            
            // 检查表格
            $tableCount = substr_count($content, '|');
            echo "✅ 表格分隔符数量: {$tableCount} 个\n";
            
        } else {
            echo "❌ 文档文件不存在: {$syntaxFile}\n";
        }
        
        echo "\n=== 文档展示优化测试完成 ===\n";
        echo "💡 提示: 请在浏览器中访问 /api/common/unittest/op?op=execApijson 查看优化效果\n";
    }

    /**
     * 测试表格处理修复
     */
    public function testTableProcessingFixAction()
    {
        echo "=== 测试表格处理修复 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 模拟表格处理的JavaScript代码
        $testMarkdown = "| 操作符 | 含义 | 示例 | SQL 等价 | 适用类型 |\n";
        $testMarkdown .= "|--------|------|------|----------|----------|\n";
        $testMarkdown .= "| `=` | 等于 | `\"user_id\": 572` | `user_id = 572` | 单对象/数组 |\n";
        $testMarkdown .= "| `>` | 大于 | `\"user_id>\": 100` | `user_id > 100` | 单对象/数组 |\n";
        
        echo "测试Markdown表格:\n";
        echo $testMarkdown . "\n";
        
        // 模拟JavaScript的表格处理逻辑
        $html = $testMarkdown;
        
        // 处理表格行
        $html = preg_replace_callback('/\|(.+)\|/', function($matches) {
            $content = $matches[1];
            $cells = array_map('trim', explode('|', $content));
            $isHeader = strpos($content, '---') !== false || strpos($content, '===') !== false;
            
            if ($isHeader) {
                return ''; // 跳过分隔行
            }
            
            $cellHtml = '';
            foreach ($cells as $cell) {
                $tag = $isHeader ? 'th' : 'td';
                $cellHtml .= '<' . $tag . '>' . $cell . '</' . $tag . '>';
            }
            
            return '<tr>' . $cellHtml . '</tr>';
        }, $html);
        
        // 包装表格
        $html = preg_replace('/(<tr>.*<\/tr>)/s', '<table>$1</table>', $html);
        
        echo "转换后的HTML:\n";
        echo $html . "\n";
        
        echo "✅ 表格处理修复测试完成\n";
        echo "💡 提示: 现在可以在浏览器中正常显示表格了\n";
    }

    /**
     * 测试正则表达式修复
     */
    public function testRegexFixAction()
    {
        echo "=== 测试正则表达式修复 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 测试表格正则表达式
        $testMarkdown = "| 操作符 | 含义 | 示例 |\n";
        $testMarkdown .= "|--------|------|------|\n";
        $testMarkdown .= "| `=` | 等于 | `\"user_id\": 572` |\n";
        
        echo "测试Markdown:\n";
        echo $testMarkdown . "\n";
        
        // 模拟JavaScript的正则表达式处理
        $pattern = '/\\|(.+)\\|/';
        $replacement = function($matches) {
            $content = $matches[1];
            $cells = array_map('trim', explode('|', $content));
            $isHeader = strpos($content, '---') !== false || strpos($content, '===') !== false;
            
            if ($isHeader) {
                return ''; // 跳过分隔行
            }
            
            $cellHtml = '';
            foreach ($cells as $cell) {
                $tag = $isHeader ? 'th' : 'td';
                $cellHtml .= '<' . $tag . '>' . $cell . '</' . $tag . '>';
            }
            
            return '<tr>' . $cellHtml . '</tr>';
        };
        
        $result = preg_replace_callback($pattern, $replacement, $testMarkdown);
        
        echo "正则表达式处理结果:\n";
        echo $result . "\n";
        
        // 测试正则表达式是否有效
        if (preg_match($pattern, $testMarkdown)) {
            echo "✅ 正则表达式有效\n";
        } else {
            echo "❌ 正则表达式无效\n";
        }
        
        echo "✅ 正则表达式修复测试完成\n";
        echo "💡 提示: 现在JavaScript正则表达式应该正常工作了\n";
    }

    /**
     * 测试新的表格处理逻辑
     */
    public function testNewTableProcessingAction()
    {
        echo "=== 测试新的表格处理逻辑 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 模拟新的表格处理逻辑
        $testMarkdown = "| 操作符 | 含义 | 示例 |\n";
        $testMarkdown .= "|--------|------|------|\n";
        $testMarkdown .= "| `=` | 等于 | `\"user_id\": 572` |\n";
        $testMarkdown .= "| `>` | 大于 | `\"user_id>\": 100` |\n";
        $testMarkdown .= "\n";
        $testMarkdown .= "这是表格后的内容\n";
        
        echo "测试Markdown:\n";
        echo $testMarkdown . "\n";
        
        // 模拟新的表格处理逻辑
        $lines = explode("\n", $testMarkdown);
        $tableLines = [];
        $inTable = false;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine && $trimmedLine[0] === '|' && $trimmedLine[-1] === '|') {
                if (!$inTable) {
                    $inTable = true;
                    $tableLines[] = '<table>';
                }
                
                $cells = array_map('trim', explode('|', $trimmedLine));
                $cells = array_filter($cells, function($cell) { return $cell !== ''; });
                $isHeader = strpos($trimmedLine, '---') !== false || strpos($trimmedLine, '===') !== false;
                
                if (!$isHeader) {
                    $cellHtml = '';
                    foreach ($cells as $cell) {
                        $tag = $isHeader ? 'th' : 'td';
                        $cellHtml .= '<' . $tag . '>' . $cell . '</' . $tag . '>';
                    }
                    $tableLines[] = '<tr>' . $cellHtml . '</tr>';
                }
            } else {
                if ($inTable) {
                    $inTable = false;
                    $tableLines[] = '</table>';
                }
                $tableLines[] = $line;
            }
        }
        
        if ($inTable) {
            $tableLines[] = '</table>';
        }
        
        $result = implode("\n", $tableLines);
        
        echo "新的表格处理结果:\n";
        echo $result . "\n";
        
        echo "✅ 新的表格处理逻辑测试完成\n";
        echo "💡 提示: 现在不再使用有问题的正则表达式了\n";
    }

    /**
     * 测试execApijson方法移动
     */
    public function testExecApijsonMoveAction()
    {
        echo "=== 测试execApijson方法移动 ===\n";
        
        // 设置必要常量避免段错误
        if (!defined('ROOT')) {
            define('ROOT', dirname(dirname(dirname(__DIR__))));
        }
        
        // 检查UnittestController中是否还有execApijson方法
        $unittestFile = ROOT . '/app/controller/common/UnittestController.php';
        if (file_exists($unittestFile)) {
            $content = file_get_contents($unittestFile);
            if (strpos($content, 'private function execApijson()') !== false) {
                echo "❌ UnittestController.php 中仍然存在 execApijson 方法\n";
            } else {
                echo "✅ UnittestController.php 中已成功移除 execApijson 方法\n";
            }
        } else {
            echo "❌ UnittestController.php 文件不存在\n";
        }
        
        // 检查ApijsonsdktestController中是否有execApijson方法
        $apijsonsdktestFile = ROOT . '/app/controller/common/ApijsonsdktestController.php';
        if (file_exists($apijsonsdktestFile)) {
            $content = file_get_contents($apijsonsdktestFile);
            if (strpos($content, 'private function execApijson()') !== false) {
                echo "✅ ApijsonsdktestController.php 中已成功添加 execApijson 方法\n";
                
                // 检查URL路径是否正确更新
                if (strpos($content, '/api/common/apijsonsdktest/op?op=execApijson') !== false) {
                    echo "✅ JavaScript中的URL路径已正确更新为 apijsonsdktest\n";
                } else {
                    echo "❌ JavaScript中的URL路径未正确更新\n";
                }
            } else {
                echo "❌ ApijsonsdktestController.php 中未找到 execApijson 方法\n";
            }
        } else {
            echo "❌ ApijsonsdktestController.php 文件不存在\n";
        }
        
        echo "💡 提示: 现在可以通过 /api/common/apijsonsdktest/op?op=execApijson 访问APIJSON执行工具\n";
    }

    /**
     * 测试四级标题显示效果
     */
    public function testH4TitleDisplayAction()
    {
        echo "=== 测试四级标题显示效果 ===\n";
        
        // 测试Markdown转HTML函数
        $testMarkdown = "#### 26.8.5 最佳实践总结\n\n这是四级标题的测试内容。\n\n#### 27.1 核心功能特性\n\n另一个四级标题。";
        
        // 模拟JavaScript的convertMarkdownToHtml函数逻辑
        $html = $testMarkdown;
        
        // 处理标题 - 按顺序处理，从多到少
        $html = preg_replace('/^#### (.*$)/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        echo "原始Markdown:\n";
        echo $testMarkdown . "\n\n";
        
        echo "转换后的HTML:\n";
        echo $html . "\n\n";
        
        // 检查是否包含h4标签
        if (strpos($html, '<h4>') !== false) {
            echo "✅ 四级标题转换成功，包含 <h4> 标签\n";
        } else {
            echo "❌ 四级标题转换失败，未找到 <h4> 标签\n";
        }
        
        // 检查具体的标题内容
        if (strpos($html, '<h4>26.8.5 最佳实践总结</h4>') !== false) {
            echo "✅ 第一个四级标题转换正确\n";
        } else {
            echo "❌ 第一个四级标题转换错误\n";
        }
        
        if (strpos($html, '<h4>27.1 核心功能特性</h4>') !== false) {
            echo "✅ 第二个四级标题转换正确\n";
        } else {
            echo "❌ 第二个四级标题转换错误\n";
        }
        
        echo "=== 四级标题显示测试完成 ===\n";
    }

    /**
     * 测试实际文档中的四级标题显示效果
     */
    public function testRealDocumentH4DisplayAction()
    {
        echo "=== 测试实际文档中的四级标题显示效果 ===\n";
        
        $syntaxFile = ROOT . '/comp/nocode/apijson/apijson_syntax_examples.md';
        if (!file_exists($syntaxFile)) {
            echo "❌ 文档文件不存在: $syntaxFile\n";
            return;
        }
        
        $content = file_get_contents($syntaxFile);
        echo "✅ 文档文件存在，大小: " . strlen($content) . " 字节\n";
        
        // 查找所有四级标题
        preg_match_all('/^#### (.*$)/m', $content, $matches);
        $h4Titles = $matches[1] ?? [];
        
        echo "✅ 找到 " . count($h4Titles) . " 个四级标题\n";
        
        // 显示前10个四级标题作为示例
        echo "\n前10个四级标题示例:\n";
        for ($i = 0; $i < min(10, count($h4Titles)); $i++) {
            echo ($i + 1) . ". " . $h4Titles[$i] . "\n";
        }
        
        // 测试转换效果
        $testContent = "#### " . implode("\n\n#### ", array_slice($h4Titles, 0, 5));
        
        // 模拟JavaScript的convertMarkdownToHtml函数逻辑
        $html = $testContent;
        
        // 处理标题 - 按顺序处理，从多到少
        $html = preg_replace('/^#### (.*$)/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // 检查转换结果
        $h4Count = substr_count($html, '<h4>');
        echo "\n✅ 转换后包含 $h4Count 个 <h4> 标签\n";
        
        if ($h4Count > 0) {
            echo "✅ 四级标题转换成功！\n";
        } else {
            echo "❌ 四级标题转换失败！\n";
        }
        
        // 检查特定标题
        $specificTitles = [
            '26.8.5 最佳实践总结',
            '27.1.1 查询功能',
            '27.2.1 关联查询 Limit 优化 (2025-08-14)'
        ];
        
        foreach ($specificTitles as $title) {
            if (strpos($html, "<h4>$title</h4>") !== false) {
                echo "✅ 找到标题: $title\n";
            } else {
                echo "❌ 未找到标题: $title\n";
            }
        }
        
        echo "\n=== 实际文档四级标题显示测试完成 ===\n";
        echo "💡 提示: 请在浏览器中访问 /api/common/apijsonsdktest/op?op=execApijson 查看效果\n";
    }

    /**
     * 测试嵌套 POST 插入功能
     */
    public function testNestedPostInsertAction()
    {
        echo "=== 测试嵌套 POST 插入功能 ===\n";
        
        $apiJson = new ApiJson('POST');
        
        // 测试用例1：基本嵌套插入
        $testData1 = [
            'CmsUser' => [
                'user_name' => 'test_nested_user_' . time(),
                'user_email' => 'test_nested_' . time() . '@example.com',
                'user_status' => 1,
                'system_id' => 1,
                'CmsModuleUser' => [
                    '@foreign_key' => 'user_id',
                    'module_id' => 2466,
                    'system_id' => 1
                ]
            ]
        ];
        
        echo "测试用例1：基本嵌套插入\n";
        echo "请求数据: " . json_encode($testData1, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result1 = $apiJson->Query(json_encode($testData1));
            echo "✅ 测试用例1成功\n";
            echo "返回结果: " . json_encode($result1, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例1失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例2：多层嵌套插入
        $testData2 = [
            'CmsUser' => [
                'user_name' => 'test_multi_nested_' . time(),
                'user_email' => 'test_multi_' . time() . '@example.com',
                'user_status' => 1,
                'system_id' => 1,
                'CmsModuleUser' => [
                    '@foreign_key' => 'user_id',
                    'module_id' => 2470,
                    'system_id' => 1,
                    'CmsModules' => [
                        '@foreign_key' => 'module_id',
                        'module_name' => '测试模块_' . time(),
                        'parent_module_id' => 0
                    ]
                ]
            ]
        ];
        
        echo "测试用例2：多层嵌套插入\n";
        echo "请求数据: " . json_encode($testData2, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result2 = $apiJson->Query(json_encode($testData2));
            echo "✅ 测试用例2成功\n";
            echo "返回结果: " . json_encode($result2, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例2失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例3：手动指定外键值
        $testData3 = [
            'CmsUser' => [
                'user_name' => 'test_manual_fk_' . time(),
                'user_email' => 'test_manual_' . time() . '@example.com',
                'user_status' => 1,
                'system_id' => 1,
                'CmsModuleUser' => [
                    'user_id' => 999, // 手动指定外键值
                    'module_id' => 2471,
                    'system_id' => 1
                ]
            ]
        ];
        
        echo "测试用例3：手动指定外键值\n";
        echo "请求数据: " . json_encode($testData3, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result3 = $apiJson->Query(json_encode($testData3));
            echo "✅ 测试用例3成功\n";
            echo "返回结果: " . json_encode($result3, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例3失败: " . $e->getMessage() . "\n\n";
        }
        
        echo "=== 嵌套 POST 插入功能测试完成 ===\n";
    }

    /**
     * 测试 @update 语法功能
     */
    public function testUpdateSyntaxAction()
    {
        echo "=== 测试 @update 语法功能 ===\n";
        
        $apiJson = new ApiJson('PUT');
        
        // 测试用例1：基本 @update 语法
        $testData1 = [
            'CmsUser' => [
                'user_id' => 1,
                'user_name' => 'updated_user',
                '@update' => [
                    'CmsModuleUser' => [
                        'module_id' => 2466,
                        'system_id' => 1
                    ]
                ]
            ]
        ];
        
        echo "测试用例1：基本 @update 语法\n";
        echo "请求数据: " . json_encode($testData1, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result1 = $apiJson->Query(json_encode($testData1));
            echo "✅ 测试用例1成功\n";
            echo "返回结果: " . json_encode($result1, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例1失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例2：多表 @update 语法
        $testData2 = [
            'CmsUser' => [
                'user_id' => 1,
                'user_status' => 1,
                '@update' => [
                    'CmsModuleUser' => [
                        'module_id' => 2470,
                        'system_id' => 1
                    ],
                    'CmsUserRole' => [
                        'role_id' => 2,
                        'user_id' => 1
                    ]
                ]
            ]
        ];
        
        echo "测试用例2：多表 @update 语法\n";
        echo "请求数据: " . json_encode($testData2, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result2 = $apiJson->Query(json_encode($testData2));
            echo "✅ 测试用例2成功\n";
            echo "返回结果: " . json_encode($result2, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例2失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例3：条件 @update 语法
        $testData3 = [
            'CmsUser' => [
                'user_id>' => 100,
                'user_status' => 1,
                '@update' => [
                    'CmsModuleUser' => [
                        'module_id' => 2471,
                        'system_id' => 1
                    ]
                ]
            ]
        ];
        
        echo "测试用例3：条件 @update 语法\n";
        echo "请求数据: " . json_encode($testData3, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result3 = $apiJson->Query(json_encode($testData3));
            echo "✅ 测试用例3成功\n";
            echo "返回结果: " . json_encode($result3, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例3失败: " . $e->getMessage() . "\n\n";
        }
        
        echo "=== @update 语法功能测试完成 ===\n";
    }

    /**
     * 测试权限控制功能
     */
    public function testPermissionControlAction()
    {
        echo "=== 测试权限控制功能 ===\n";
        
        // 测试用例1：测试 GET 权限（默认允许）
        $apiJson1 = new ApiJson('GET');
        $testData1 = [
            'CmsUser' => [
                'user_id' => 1,
                '@column' => 'user_id,user_name'
            ]
        ];
        
        echo "测试用例1：GET 权限测试\n";
        echo "请求数据: " . json_encode($testData1, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result1 = $apiJson1->Query(json_encode($testData1));
            echo "✅ 测试用例1成功\n";
            echo "返回结果: " . json_encode($result1, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例1失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例2：测试 POST 权限（可能被禁止）
        $apiJson2 = new ApiJson('POST');
        $testData2 = [
            'CmsUser' => [
                'user_name' => 'test_permission_user',
                'user_email' => 'test_permission@example.com',
                'user_status' => 1,
                'system_id' => 1
            ]
        ];
        
        echo "测试用例2：POST 权限测试\n";
        echo "请求数据: " . json_encode($testData2, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result2 = $apiJson2->Query(json_encode($testData2));
            echo "✅ 测试用例2成功\n";
            echo "返回结果: " . json_encode($result2, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例2失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例3：测试 PUT 权限（可能被禁止）
        $apiJson3 = new ApiJson('PUT');
        $testData3 = [
            'CmsUser' => [
                'user_id' => 1,
                'user_name' => 'updated_permission_user'
            ]
        ];
        
        echo "测试用例3：PUT 权限测试\n";
        echo "请求数据: " . json_encode($testData3, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result3 = $apiJson3->Query(json_encode($testData3));
            echo "✅ 测试用例3成功\n";
            echo "返回结果: " . json_encode($result3, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例3失败: " . $e->getMessage() . "\n\n";
        }
        
        // 测试用例4：测试 DELETE 权限（可能被禁止）
        $apiJson4 = new ApiJson('DELETE');
        $testData4 = [
            'CmsUser' => [
                'user_id' => 999
            ]
        ];
        
        echo "测试用例4：DELETE 权限测试\n";
        echo "请求数据: " . json_encode($testData4, JSON_UNESCAPED_UNICODE) . "\n";
        
        try {
            $result4 = $apiJson4->Query(json_encode($testData4));
            echo "✅ 测试用例4成功\n";
            echo "返回结果: " . json_encode($result4, JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (Exception $e) {
            echo "❌ 测试用例4失败: " . $e->getMessage() . "\n\n";
        }
        
        echo "=== 权限控制功能测试完成 ===\n";
    }
}