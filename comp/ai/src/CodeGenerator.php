<?php

namespace Imee\Comp\Ai;

class CodeGenerator
{
    private $scanner;
    private $llmClient;

    public function __construct($llmClient = null)
    {
        $this->scanner = new ModelScanner();
        $this->llmClient = $llmClient;
    }

    public function generateListProcess($module, $business, $requirement, $save = false)
    {
        $modelInfo = $this->scanner->findModelByKeyword($business);
        if (!$modelInfo) {
            throw new \Exception("未找到相关模型: {$business}");
        }

        $targetNamespace = sprintf("Imee\\Service\\Domain\\Service\\%s\\Processes\\%s", ucfirst($module), ucfirst($business));
        $targetClass = "ListProcess";
        
        $prompt = $this->buildPrompt($requirement, $modelInfo, $targetNamespace, $targetClass);

        $code = "";
        if ($this->llmClient) {
            $code = $this->llmClient->complete($prompt);
        } else {
            $code = $this->mockGenerate($modelInfo, $targetNamespace, $targetClass);
        }

        if ($save) {
            $this->saveFile($module, $business, $targetClass, $code);
        }

        return $code;
    }

    private function buildPrompt($requirement, $modelInfo, $namespace, $class)
    {
        $fields = implode(',', array_slice($modelInfo['fields'], 0, 20));
        
        return <<<EOT
你是一个 PHP Phalcon 框架专家。请生成一个 Domain Service Process 类。

【上下文】
模型类: {$modelInfo['namespace']}\\{$modelInfo['class']}
模型字段: {$fields}
目标类名: {$namespace}\\{$class}
需求: {$requirement}

【代码模板参考】
namespace {$namespace};

use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use {$modelInfo['namespace']}\\{$modelInfo['class']};

class {$class}
{
    use ListTrait;

    private \$model = {$modelInfo['class']}::class;

    public function onGetFilter(&\$filter)
    {
        // 根据需求实现过滤
    }

    public function onAfterList(\$list): array
    {
        // 根据需求实现数据格式化
        return \$list;
    }
}

【要求】
1. 只输出 PHP 代码，不包含 Markdown 标记。
2. 严格使用参考模板结构。
3. 逻辑必须符合 Phalcon 规范。
EOT;
    }

    private function mockGenerate($modelInfo, $namespace, $class)
    {
        $code = <<<PHP
<?php

namespace {$namespace};

use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use {$modelInfo['namespace']}\\{$modelInfo['class']};

class {$class}
{
    use ListTrait;

    private \$model = {$modelInfo['class']}::class;

    public function onGetFilter(&\$filter)
    {
        if (isset(\$filter['id'])) {
            \$filter['uid'] = \$filter['id'];
            unset(\$filter['id']);
        }
    }

    public function onAfterList(\$list): array
    {
        foreach (\$list as &\$item) {
            \$item['create_time'] = date('Y-m-d H:i:s', \$item['dateline'] ?? time());
        }
        return \$list;
    }
}
PHP;
        return $code;
    }

    private function saveFile($module, $business, $class, $code)
    {
        $path = ROOT . "/app/service/domain/service/" . lcfirst($module) . "/processes/" . lcfirst($business);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($path . "/" . $class . ".php", $code);
    }
}
