<?php

namespace Imee\Service\Domain\Service\Csms\Traits;

use Imee\Comp\Common\Sdk\SdkSlack;

trait CsmswarningTrait
{
    public $notice = true;

    /**
     * 发送推荐组给客服提醒
     * @param string $wechatMsg
     * @return void
     * @throws \Exception
     */
    public function sendRecommend(string $wechatMsg)
    {
        if (!$this->notice) {
            return;
        }
        $url = ENV == 'dev' ? SLACK_KEFU_WEBHOOK : SLACK_RECOMMEND_WEBHOOK;
        /** @var SdkSlack $obj */
        $obj = factory_single_obj(SdkSlack::class);
        $obj->sendMsg($url, 'text', $wechatMsg);
    }

    /**
     * 发送审核组客服提醒
     * @param string $wechatMsg
     * @return void
     * @throws \Exception
     */
    public function sendCsms(string $wechatMsg)
    {
        if (!$this->notice) {
            return;
        }
        $url = SLACK_KEFU_WEBHOOK;
        /** @var SdkSlack $obj */
        $obj = factory_single_obj(SdkSlack::class);
        $obj->sendMsg($url, 'text', $wechatMsg);
    }

    public function sendSystem(string $wechatMsg)
    {
        if (!$this->notice) {
            return;
        }
        $url = SLACK_SYSTEM_WEBHOOK;
        /** @var SdkSlack $obj */
        $obj = factory_single_obj(SdkSlack::class);
        $obj->sendMsg($url, 'text', $wechatMsg);
    }
}