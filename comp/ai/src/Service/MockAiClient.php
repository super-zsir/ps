<?php

namespace Imee\Comp\Ai\Service;

use Imee\Comp\Ai\Contract\AiClientInterface;

/**
 * 模拟 AI 客户端 (在没有真实 Key 的情况下使用)
 */
class MockAiClient implements AiClientInterface
{
    public function chat(string $systemPrompt, string $userPrompt): string
    {
        // 简单模拟 AI 的分析和生成能力
        // 实际场景中这里会调用 OpenAI/Anthropic API

        if (strpos($userPrompt, '分析架构') !== false) {
            return $this->mockAnalysisResponse($userPrompt);
        }

        if (strpos($userPrompt, '生成代码') !== false) {
            return $this->mockCodeResponse($userPrompt);
        }

        return "AI 思考中...";
    }

    private function mockAnalysisResponse($prompt)
    {
        // 模拟 AI 返回的 JSON 计划
        // 假设 prompt 里包含了 JSON schema
        preg_match('/\{.*\}/s', $prompt, $matches);
        $json = $matches[0] ?? '{}';
        $data = json_decode($json, true);
        
        $module = ucfirst($data['module'] ?? 'Demo');
        $entity = ucfirst($data['entity'] ?? 'Test');
        
        $files = [];
        foreach (($data['features'] ?? []) as $feature) {
            if ($feature === 'list') {
                $files[] = [
                    'type' => 'process',
                    'class' => 'ListProcess',
                    'path' => "app/service/domain/service/" . lcfirst($module) . "/processes/" . lcfirst($entity) . "/ListProcess.php",
                    'description' => '列表查询逻辑，包含分页和筛选'
                ];
            } elseif ($feature === 'create') {
                $files[] = [
                    'type' => 'process',
                    'class' => 'CreateProcess',
                    'path' => "app/service/domain/service/" . lcfirst($module) . "/processes/" . lcfirst($entity) . "/CreateProcess.php",
                    'description' => '创建逻辑，包含数据验证'
                ];
            }
        }
        
        return json_encode([
            'summary' => "根据需求，需要创建 {$entity} 相关的业务处理类。",
            'files' => $files
        ], JSON_UNESCAPED_UNICODE);
    }

    private function mockCodeResponse($prompt)
    {
        // 模拟生成 PHP 代码
        // 简单的提取类名
        preg_match('/class_name:\s*(\w+)/', $prompt, $matches);
        $className = $matches[1] ?? 'UnknownProcess';
        
        preg_match('/namespace:\s*([\w\\\]+)/', $prompt, $matches);
        $namespace = $matches[1] ?? 'Imee\Service';

        return <<<php
<?php

namespace {$namespace};

use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use Imee\Service\Lesscode\Traits\Curd\CreateTrait;

class {$className}
{
    // AI Generated Logic for {$className}
    // 基于输入的 Schema 自动注入字段处理逻辑
    
    public function handle(\$params)
    {
        // TODO: Implement logic
        return ['status' => 'success'];
    }
}
php;
    }
}
