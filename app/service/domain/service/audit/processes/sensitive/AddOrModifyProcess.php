<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Sensitive;

use Imee\Comp\Common\Sdk\SdkFilter;
use Imee\Service\Domain\Context\Audit\Sensitive\ModifyContext;
use Imee\Exception\Audit\SensitiveException;
use Imee\Service\Helper;

/**
 * 敏感词配置
 */
class AddOrModifyProcess
{
    private $context;

    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $filter = new SdkFilter();
        if (empty($this->context->text)) {
            SensitiveException::throwException(SensitiveException::REMOVE_DATA_FAILED);
        }

        foreach ($this->context->text as $line) {
            $params[] = [
                'app' => APP_ID,
                'language' =>  $this->context->language,
                'text' => trim($line),
                'type' => $this->context->type,
                'sub_type' => !empty($this->context->subType) ? $this->context->subType : '',
                'reason' => !empty($this->context->reason) ? $this->context->reason : '',
                'vague' => (int)$this->context->vague,
                'cond' => implode(',', $this->context->cond),
                'danger' => (int)$this->context->danger,
                'accurate' => (int)$this->context->accurate,
            ];
        }
        if ($this->context->id) {
            foreach ($params as &$param) {
                $param['delete'] = (int)$this->context->deleted;
            }
            
            $res = $filter->modDirtys($params);
            $log = '请求敏感词修改失败';
            $exception = SensitiveException::MODIFY_DATA_FAILED;
        } else {
            $res = $filter->addDirtys($params);
            $log = '请求敏感词新增失败';
            $exception = SensitiveException::CREATE_DATA_FAILED;
        }
    
        if (empty($res) || $res['err_code'] != 0) {
            $result = is_array($res) ? json_encode($res, JSON_UNESCAPED_UNICODE) : '';
            Helper::debugger()->error(__CLASS__ .' '. $log . ': params is '. json_encode($params) .
                ', return is '. $result);
                
            SensitiveException::throwException($exception);
        }
    }
}
