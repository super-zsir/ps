<?php

namespace Imee\Comp\Ai\Prompt;

class PromptBuilder
{
    public function buildAnalysisPrompt(array $schema): string
    {
        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return <<<EOT
你是一个资深的后端架构师，熟悉 Phalcon 框架和 DDD (领域驱动设计)。
请分析以下业务 Schema JSON，并规划需要生成的后端代码文件结构。

Schema:
{$json}

项目结构规则:
1. 业务逻辑代码放在 `app/service/domain/service/{module}/processes/{entity}/` 目录下。
2. 列表查询命名为 `ListProcess.php`。
3. 新增逻辑命名为 `CreateProcess.php`。
4. 修改逻辑命名为 `ModifyProcess.php`。

请返回 JSON 格式的规划结果，包含：
- files: 数组，每个元素包含 path (文件路径), class (类名), type (类型), description (描述).
EOT;
    }

    public function buildCodeGeneratePrompt(array $filePlan, array $schema, string $projectContext = ''): string
    {
        return <<<EOT
你是一个 PHP 高级工程师。请根据以下规划生成具体的 PHP 代码。

任务: 生成 {$filePlan['class']}
文件路径: {$filePlan['path']}
业务描述: {$filePlan['description']}

Schema 定义:
{json_encode($schema['fields'])}

项目上下文 (Trait/BaseClass):
{$projectContext}

代码规范:
1. 严格遵守 PSR-12。
2. 命名空间需与文件路径对应 (App\Service\Domain...).
3. 如果是 ListProcess，请使用 `use ListTrait`。
4. 包含必要的注释。
5. 只输出 PHP 代码内容，不包含 ```php 标记。
EOT;
    }
}
