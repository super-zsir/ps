<?php

namespace Imee\Comp\Ai\Service;

use Imee\Comp\Ai\Contract\AiClientInterface;
use Imee\Comp\Ai\Prompt\PromptBuilder;
use Imee\Comp\Ai\CodeGenerator;

class WorkflowEngine
{
    private $parser;
    private $aiClient;
    private $promptBuilder;
    private $generator;

    public function __construct(AiClientInterface $aiClient)
    {
        $this->parser = new SchemaParser();
        $this->aiClient = $aiClient;
        $this->promptBuilder = new PromptBuilder();
        $this->generator = new CodeGenerator($aiClient);
    }

    /**
     * 执行全流程：Schema -> 分析 -> 代码生成 -> 写入
     */
    public function run(string $schemaJson, string $module = 'nocode')
    {
        // 1. 解析 Schema
        echo "[1/4] 解析 Schema JSON...\n";
        $tasks = $this->parser->parse($schemaJson);
        if (empty($tasks)) {
            throw new \Exception("未从 Schema 中解析出有效任务");
        }
        echo "解析到 " . count($tasks) . " 个后端任务。\n";

        // 2. AI 规划分析 (Batch处理，或逐个处理)
        echo "[2/4] AI 架构分析...\n";
        $plans = [];
        foreach ($tasks as $task) {
            $plan = $this->analyzeTaskWithAi($task, $module);
            if ($plan) {
                $plans[] = $plan;
            }
        }

        // 3. 代码生成 & 写入
        echo "[3/4] 代码生成与写入...\n";
        foreach ($plans as $plan) {
            $this->generateAndSave($plan);
        }

        echo "[4/4] 流程完成。\n";
        return $plans;
    }

    private function analyzeTaskWithAi($task, $module)
    {
        // 构建提示词让 AI 决定文件路径和类名
        $prompt = <<<EOT
请根据以下需求规划 PHP 类文件信息：
需求: {$task['desc']}
Model: {$task['model']}
Type: {$task['type']}
Module: {$module}

请返回 JSON 格式:
{
    "class_name": "类名 (如 ListProcess)",
    "namespace": "命名空间全路径",
    "file_path": "相对项目根目录的文件路径",
    "business_name": "业务名称 (小写，如 user)"
}
EOT;

        // 调用 AI (这里假设 MockAiClient 会返回特定的 JSON 结构)
        $response = $this->aiClient->chat("你是一个架构师", $prompt);
        
        // 解析 AI 响应 (简单模拟解析 JSON)
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $plan = json_decode($matches[0], true);
            $plan['task_info'] = $task;
            return $plan;
        }
        
        return null;
    }

    private function generateAndSave($plan)
    {
        echo "  - 正在生成 {$plan['class_name']} ...\n";
        
        // 利用现有的 CodeGenerator 生成逻辑
        // 这里稍微适配一下参数
        $task = $plan['task_info'];
        $requirement = $task['desc'];
        if (!empty($task['fields'])) {
            $requirement .= "\n涉及字段: " . implode(',', $task['fields']);
        }
        
        // 实际上 CodeGenerator 是针对 ListProcess 优化的，这里我们扩展一下或直接利用其底层逻辑
        // 为了演示，我们复用 generateListProcess 的逻辑结构，但扩展它以支持任意代码
        
        $code = $this->generator->generateListProcess(
            basename(dirname(dirname($plan['namespace']))), // 尝试从 namespace 提取 module
            $plan['business_name'],
            $requirement,
            false // 先不自动保存，手动控制保存路径
        );

        // 如果生成器返回的是 ListProcess 模板，但我们需要 Create/Modify，这里需要更强的 Prompt 策略
        // 为简化演示，我们假设 generator 内部已经根据 requirement 智能调整了 (在真实 AI 场景下是这样的)
        
        // 写入文件
        $fullPath = ROOT . '/' . $plan['file_path'];
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, $code);
        echo "    已写入: {$plan['file_path']}\n";
    }
}
