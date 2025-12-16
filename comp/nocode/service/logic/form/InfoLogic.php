<?php

namespace Imee\Comp\Nocode\Service\Logic\Form;

use Imee\Comp\Nocode\Service\Context\Form\InfoContext;
use Imee\Comp\Nocode\Models\Cms\NocodeSchemaConfig;
use Imee\Comp\Nocode\Service\Exception\FormException;

/**
 * 表单详情
 */
class InfoLogic
{
    /**
     * @var InfoContext $context
     */
    private $context;

    /**
     * @var NocodeSchemaConfig $model
     */
    private $model = NocodeSchemaConfig::class;

    /**
     * 构造函数
     * @param InfoContext $context
     */
    public function __construct(InfoContext $context)
    {
        $this->context = $context;
    }

    /**
     * 处理逻辑
     * @return array
     */
    public function handle(): array
    {
        $this->verify();

        return $this->model::findOneByWhere([
            ['ncid', '=', $this->context->ncid],
            ['system_id', '=', SYSTEM_ID]
        ], '*');
    }

    /**
     * 验证参数
     * @return void
     */
    private function verify()
    {
        if (empty($this->context->ncid)) {
            [$code, $msg] = FormException::NCID_NOT_FOUND;
            throw new FormException($msg, $code);
        }
    }
}