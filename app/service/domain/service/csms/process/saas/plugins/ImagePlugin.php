<?php

namespace Imee\Service\Domain\Service\Csms\Process\Saas\Plugins;

use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Audit\RiskService;

class ImagePlugin extends AbstractPlugin
{
    protected function init()
    {
        $servicerMap = $this->getServicerMap();

        // 默认通过
        $this->result['machine'] = CsmsAudit::MACHINE_UNKNOWN;

        if($servicerMap){
            foreach ($servicerMap as $v) {
                $scan = $this->riskService->image([
                    'choice' => $this->initContext->choice,
                    'mode' => $v['mark'],
                    'scenes' => $v['scenes'],
                    'path' => $this->context->after,
                    'pk_value' => $this->initContext->pkValue,
                    'data_id' => $this->initContext->taskid
                ]);

                $result = $scan->getResult();
                $this->result = $result;


                // 严重违规的，直接返回
                if($result['machine'] == CsmsAudit::MACHINE_DANGER){
                    $this->downloadimage($this->context->after);
                    return false;
                }
                // 一般违规的，已返回
                if($result['machine'] == CsmsAudit::MACHINE_REFUSE){
                    return false;
                }
            }
            // 全部检测完成，未违规的，按通过处理
            $this->result['machine'] = CsmsAudit::MACHINE_PASS;
            return true;

        }
        // 没有配置风控规则的，按未识别处理
        $this->result['machine'] = CsmsAudit::MACHINE_UNKNOWN;
    }

    /**
     * 保存严重违规图片到本地
     * @param array $paths
     */
    private function downloadimage($paths = [])
    {
        return false;
    }
}
