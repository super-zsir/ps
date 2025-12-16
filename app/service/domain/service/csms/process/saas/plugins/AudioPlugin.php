<?php


namespace Imee\Service\Domain\Service\Csms\Process\Saas\Plugins;


use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\AudioService;

class AudioPlugin extends AbstractPlugin
{


	/**
	 * 音频的解析步骤 - 不能第一时间出结果
	 * 1、异步上传文件
	 * 2、异步检测文件
	 */
	protected function init1()
	{
		// after是数组

	}

    protected function init()
    {
        // 获取服务配置
        $servicerMap = $this->getServicerMap();
        // 默认通过
        $this->result['machine'] = CsmsAudit::MACHINE_UNKNOWN;
        if($servicerMap){
            foreach ($servicerMap as $v) {
                $scan = $this->riskService->audio([
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