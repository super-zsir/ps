<?php

namespace Imee\Service\Domain\Service\Csms\Process\Saas\Plugins;

use Imee\Models\Xss\CsmsAudit;

class VideoPlugin extends AbstractPlugin
{

    protected function init()
    {
        // 获取视频服务配置
        $servicerMap = $this->getServicerMap();
        // 视频检测
        // 默认通过
        $this->result['machine'] = CsmsAudit::MACHINE_UNKNOWN;
        if($servicerMap){
            foreach ($servicerMap as $v) {
                $scan = $this->riskService->video([
                    'choice' => $this->initContext->choice, // 审核项
                    'mode' => $v['mark'], // 检测服务
                    'scenes' => $v['scenes'], // 检测场景
                    'path' => $this->context->after, // 审核内容数组
                    'pk_value' => $this->initContext->pkValue,
                    'data_id' => $this->initContext->taskid
                ]);

                $result = $scan->getResult();
                $this->result = $result;
            }
            return true;
        }
    }
}
