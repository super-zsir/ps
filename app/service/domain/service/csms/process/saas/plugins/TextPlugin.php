<?php

namespace Imee\Service\Domain\Service\Csms\Process\Saas\Plugins;

use Imee\Models\Xss\CsmsAudit;

class TextPlugin extends AbstractPlugin
{
    protected function init()
    {
        // 获取服务配置
        $servicerMap = $this->getServicerMap();
        // 视频检测
        // 默认通过
        $this->result['machine'] = CsmsAudit::MACHINE_UNKNOWN;
        if($servicerMap){
            // 多服务处理
            foreach ($servicerMap as $v) {
                $scan = $this->riskService->text([
                    'choice' => $this->initContext->choice, // 审核项
                    'mode' => $v['mark'], // 检测服务
                    'scenes' => $v['scenes'], // 检测场景
                    'path' => $this->context->after, // 审核内容数组
                    'pk_value' => $this->initContext->pkValue,
                    'data_id' => $this->initContext->taskid,
                    'uid' => $this->initContext->uid,
                ]);

                $result = $scan->getResult();
                if (isset($result['machine'])) {
                    $this->result['machine'] = $result['machine'];
                }

                if (isset($result['tags'])) {
                    $this->result['tags'] = $result['tags'];
                }

                if (isset($result['reason'])) {
                    $this->result['reason'] = $result['reason'];
                }
            }
            return true;
        }
    }
}
