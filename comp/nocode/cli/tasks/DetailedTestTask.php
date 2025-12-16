<?php

use Imee\Comp\Nocode\Models\Cms\NocodeModelConfig;
use Imee\Service\Helper;

/**
 * 详细验证测试
 * php cli.php detailedtest -task_dir_load 'comp/nocode/cli/tasks/' -process test 
 */
class DetailedtestTask extends CliApp
{
    public function mainAction(array $params = [])
    {
        if (!empty($params)) {
            $process = $params['process'] ?? 'test';

            $this->console('================== detailed_test start ==================');

            if (method_exists($this, $process)) {
                $this->{$process}($params);
            } else {
                $this->console('error process!');
            }

            $this->console('================== detailed_test end ==================');
        }
        return false;
    }

    public function test(array $params)
    {
        $this->console('================== 详细验证测试结果 ==================');
        
        try {
            if (!defined('ROOT')) {
                define('ROOT', dirname(dirname(dirname(__DIR__))));
            }
            
            $apiJson = new \Imee\Comp\Nocode\Apijson\ApiJson('GET');
            
            // 测试1: CONCAT函数是否正确返回
            $this->console('1. 测试 CONCAT 函数');
            $queryStr = '{"CmsUser[]":{"@column":"user_id,CONCAT(user_name,\"-\",user_email) as info","@limit":2}}';
            $this->console('请求JSON: ' . $queryStr);
            
            $result1 = $apiJson->Query($queryStr);
            $this->console('返回: ' . json_encode($result1, JSON_UNESCAPED_UNICODE));
            
            // 测试简单函数
            $this->console('测试简单聚合函数:');
            $simpleResult = $apiJson->Query('{"CmsUser[]":{"@column":"user_id,COUNT(*) as cnt","@group":"user_id","@limit":2}}');
            $this->console('简单函数返回: ' . json_encode($simpleResult, JSON_UNESCAPED_UNICODE));
            
            // 检查是否包含info字段
            if (isset($result1['CmsUser[]']) && is_array($result1['CmsUser[]'])) {
                $hasInfo = false;
                foreach ($result1['CmsUser[]'] as $row) {
                    if (isset($row['info'])) {
                        $hasInfo = true;
                        break;
                    }
                }
                $this->console($hasInfo ? '✓ CONCAT函数正常工作' : '✗ CONCAT函数未生成info字段');
            } else {
                $this->console('✗ 返回格式异常');
            }
            
            $this->console('');
            
            // 测试2: @alias是否正确应用
            $this->console('2. 测试 @alias 别名');
            $result2 = $apiJson->Query('{"CmsUser[]":{"@column":"user_id,user_name","@alias":{"user_id":"uid","user_name":"name"},"@limit":2}}');
            $this->console('请求: @alias测试');
            $this->console('返回: ' . json_encode($result2, JSON_UNESCAPED_UNICODE));
            
            // 检查别名是否正确应用
            if (isset($result2['CmsUser[]']) && is_array($result2['CmsUser[]'])) {
                $hasAlias = false;
                foreach ($result2['CmsUser[]'] as $row) {
                    if (isset($row['uid']) && isset($row['name'])) {
                        $hasAlias = true;
                        break;
                    }
                }
                $this->console($hasAlias ? '✓ @alias 别名正常工作' : '✗ @alias 别名未正确应用');
            } else {
                $this->console('✗ 返回格式异常');
            }
            
            $this->console('');
            
            // 测试3: 聚合函数
            $this->console('3. 测试聚合函数');
            $result3 = $apiJson->Query('{"CmsUser":{"@column":"COUNT(*) as total,MAX(user_id) as max_id"}}');
            $this->console('请求: 聚合函数测试');
            $this->console('返回: ' . json_encode($result3, JSON_UNESCAPED_UNICODE));
            
            // 检查聚合函数结果
            if (isset($result3['CmsUser']) && is_array($result3['CmsUser'])) {
                $hasAgg = isset($result3['CmsUser']['total']) && isset($result3['CmsUser']['max_id']);
                $this->console($hasAgg ? '✓ 聚合函数正常工作' : '✗ 聚合函数未正确执行');
            } else {
                $this->console('✗ 返回格式异常');
            }
            
            $this->console('');
            
            // 测试4: @having 条件
            $this->console('4. 测试 @having 条件');
            $result4 = $apiJson->Query('{"CmsUser[]":{"@group":"user_status","@having":"COUNT(*) > 1","@column":"user_status,COUNT(*) as count"}}');
            $this->console('请求: @having测试');
            $this->console('返回: ' . json_encode($result4, JSON_UNESCAPED_UNICODE));
            
            // 检查having结果
            if (isset($result4['CmsUser[]']) && is_array($result4['CmsUser[]'])) {
                $validHaving = true;
                foreach ($result4['CmsUser[]'] as $row) {
                    if (isset($row['count']) && $row['count'] <= 1) {
                        $validHaving = false;
                        break;
                    }
                }
                $this->console($validHaving ? '✓ @having 条件正常工作' : '✗ @having 条件未正确过滤');
            } else {
                $this->console('✗ 返回格式异常');
            }
            
        } catch (\Exception $e) {
            $this->console('测试过程中发生错误: ' . $e->getMessage());
            $this->console('错误文件: ' . $e->getFile());
            $this->console('错误行号: ' . $e->getLine());
        }
        
        $this->console('================== 详细验证完成 ==================');
    }
}