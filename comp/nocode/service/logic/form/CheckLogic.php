<?php

namespace Imee\Comp\Nocode\Service\Logic\Form;

use Imee\Comp\Nocode\Service\Context\Form\InfoContext;
use Imee\Comp\Nocode\Service\Exception\FormException;
use Imee\Comp\Nocode\Models\Cms\NocodeSchemaConfig;
/**
 * 表单校验
 */
class CheckLogic
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
     * @return bool
     */
    public function handle(): bool
    {
        $this->verify();

        $data = $this->model::getInfoByNcid($this->context->ncid);

        if (!empty($data)) {
            [$code, $msg] = FormException::NCID_EXSITS_ERROR;
            throw new FormException($msg, $code);
        }

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
    }


}