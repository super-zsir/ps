<?php

namespace Imee\Service\Domain\Service\Pretty;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Service\Domain\Context\Pretty\Style\ListContext;
use Imee\Service\Domain\Service\Pretty\Processes\Style\ListProcess;

use Imee\Service\Domain\Context\Pretty\Style\CreateContext;
use Imee\Service\Domain\Context\Pretty\Style\ModifyContext;
use Imee\Service\Domain\Service\Pretty\Processes\Style\CreateOrModifyProcess;
use Imee\Service\StatusService;

class PrettystyleService
{
    public function getList($params)
    {
        $context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }
    
    public function create($params)
    {
        $context = new CreateContext($params);
        
        return $this->modify($context->toArray());
    }

    public function modify($params)
    {
        $context = new ModifyContext($params);
        $process = new CreateOrModifyProcess($context);
        return $process->handle();
    }

    public function disable(array $params)
    {
        $id = $params['id'] ?? 0;
        $disabled = $params['disabled'] ?? -1;
        if (empty($id) || $id < 1 || !in_array($disabled, array_keys(XsCustomizePrettyStyle::$disabledMap))) {
            throw new ApiException(ApiException::MSG_ERROR, '提交有误');
        }

        $info = XsCustomizePrettyStyle::findOne($id);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '记录不存在');
        }

        if ($info['disabled'] == $disabled) {
            return [];
        }

        $data = [
            'disabled' => $disabled,
        ];
        [$success, $msg] = XsCustomizePrettyStyle::edit($id, $data);
        if (!$success) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'before_json' => $info, 'after_json' => $data];
    }

    /**
     * 获取靓号支持格式
     * @return array
     */
    public function getStyleTypeMap(): array
    {
        return StatusService::formatMap(XsCustomizePrettyStyle::$styleTypeMap);
    }

    public function getDisabledMap(): array
    {
        return StatusService::formatMap(XsCustomizePrettyStyle::$disabledMap);
    }
}
