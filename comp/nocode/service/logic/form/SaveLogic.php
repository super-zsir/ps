<?php

namespace Imee\Comp\Nocode\Service\Logic\Form;

use Imee\Comp\Nocode\Service\Context\Form\SaveContext;
use Imee\Comp\Nocode\Service\Exception\FormException;
use Imee\Comp\Nocode\Models\Cms\NocodeSchemaConfig;
use Imee\Comp\Nocode\Service\Logic\InitLogic;
use Imee\Comp\Nocode\Service\Logic\FormLogic;
use Imee\Comp\Nocode\Service\Logic\SchemaJsonLogic;
/**
 * 保存表单
 */
class SaveLogic
{
    /**
     * @var SaveContext $context
     */
    private $context;

    /**
     * @var NocodeSchemaConfig $model
     */
    private $model = NocodeSchemaConfig::class;

    /**
     * @var bool 是否更新
     */
    private $isUpdate = false;

    /**
     * 构造函数
     * @param SaveContext $context
     */
    public function __construct(SaveContext $context)
    {
        $this->context = $context;
    }

    /**
     * 处理逻辑
     * @return bool
     */
    public function handle(): bool
    {
        $this->verify();
        // 验证是否为更新操作
        $this->checkIsUpdate();
        // 解析schema_json
        $this->parseSchemaJson();

        if (!$this->isUpdate) {
            $this->pointInit();
        };
        $this->model::saveConfig($this->context->ncid, $this->context->schemaJson);
        return true;
    }

    /**
     * 验证参数
     * @return void
     */
    private function verify(): void
    {
        if (empty($this->context->ncid)) {
            [$code, $msg] = FormException::NCID_NOT_FOUND;
            throw new FormException($msg, $code);
        }


        // 正则校验 ncid 必须为英文字母小写
        if (!preg_match('/^[a-z]+$/', $this->context->ncid)) {
            [$code, $msg] = FormException::NCID_INVALID;
            throw new FormException($msg, $code);
        }

        if (empty($this->context->schemaJson)) {
            [$code, $msg] = FormException::SCHEMA_JSON_NOT_FOUND;
            throw new FormException($msg, $code);
        }
    }

    /**
     * 检查是否为更新操作
     * @return void
     */
    private function checkIsUpdate(): void
    {
        $info = FormLogic::getInstance()->info([
            'ncid' => $this->context->ncid
        ]);
        $info && $this->isUpdate = true;
    }

    /**
     * 解析schema_json
     * @return void
     */
    private function parseSchemaJson(): void
    {
        $schemaJson = SchemaJsonLogic::getInstance()->parse([
            'schemaJson' => $this->context->schemaJson
        ]);
        
        $this->context->setParams([
            'module_name' => $schemaJson['title']
        ]);
    }

    /**
     * 节点初始化
     * @return void
     */
    private function pointInit(): void
    {
        InitLogic::getInstance()->pointInit([
            'module_Id'   => $this->context->moduleId,
            'controller'  => $this->context->ncid,
            'module_name' => $this->context->moduleName,
        ]);
    }
}