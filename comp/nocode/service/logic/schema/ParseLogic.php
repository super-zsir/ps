<?php

namespace Imee\Comp\Nocode\Service\Logic\Schema;

use Imee\Comp\Nocode\Service\Context\Schema\SchemaJsonContext;

/**
 * Schema解析逻辑类
 */
class ParseLogic
{
    /**
     * @var SchemaJsonContext $context
     */
    private $context;

    /**
     * schema_json 解析数组
     * @var array
     */
    private $schemaJson;

    /**
     * @param SchemaJsonContext $context
     */
    public function __construct(SchemaJsonContext $context) {
        $this->context = $context;
        $this->schemaJson = json_decode($this->context->schemaJson, true);
    }

    /**
     * 处理schema解析
     * 门面模式的入口方法，隐藏内部复杂性
     * @return array
     */
    public function handle(): array
    {
        return $this->parse();
    }

    /**
     * 解析对应所需内容
     * 模板方法：定义了解析的骨架流程
     * @return array
     */
    private function parse(): array
    {
        return [
            'title' => $this->parseTitle(),
        ];
    }

    /**
     * 解析功能名称
     * @return string
     */
    private function parseTitle(): string
    {
        return $this->schemaJson['root']['props']['title'] ?? '';
    }
}