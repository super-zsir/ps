<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Sensitive;

use Imee\Comp\Common\Sdk\SdkFilter;
use Imee\Service\Domain\Context\Audit\Sensitive\RemoveContext;
use Imee\Exception\Audit\SensitiveException;
use Imee\Service\Helper;

/**
 * 敏感词配置
 */
class RemoveProcess
{
    private $context;

    public function __construct(RemoveContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $filter = new SdkFilter();
        $res = $filter->deleteDirtys(array($this->context->text), APP_ID);
        if (empty($res) || $res['err_code'] != 0) {
            Helper::debugger()->error(__CLASS__ .' 请求敏感词删除失败: ' . is_array($res) ? json_encode($res) : '');

            SensitiveException::throwException(SensitiveException::REMOVE_DATA_FAILED);
        }
    }
}
