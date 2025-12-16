<?php

namespace Imee\Service\Domain\Service\Csms\Common;

use Imee\Comp\Common\Sdk\SdkQyWechat;
use Imee\Service\Domain\Service\Csms\Common\Context\Csmscommon\ShowMemoryContext;
use Imee\Service\Helper;

class CsmsCommonService
{
    /**
     * 企业微信通知进程内存情况
     * @param array $params
     * @throws \Exception
     */
    public function showMemory($params = [])
    {
        $content = new ShowMemoryContext($params);
        $process = $content->process ?? '内容安全管理系统未知进程';
        $content = <<<STR
【Csms】进程内存情况
> PROCESS: {process}
> MEMORY: {memory}
STR;
        $wechatMsg = str_replace(
            ['{process}', '{memory}'],
            [$process, Helper::getMemoryUse()],
            $content
        );

        /** @var SdkQyWechat $obj */
        $obj = factory_single_obj(SdkQyWechat::class);
        $obj->sendMsg(WECHAT_KEFU_WEBHOOK, 'markdown', $wechatMsg);
    }
}