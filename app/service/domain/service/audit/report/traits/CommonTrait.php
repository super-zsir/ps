<?php

namespace Imee\Service\Domain\Service\Audit\Report\Traits;

use Imee\Comp\Common\Beanstalkd\Client;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;

trait CommonTrait
{
    /**
     * @param array $condition
     * @return array
     */
    public static function commonFilter(array $condition)
    {
        return array_filter($condition, function ($item) {
            if ($item === '' || $item === null || $item === ['']) {
                return false;
            }
            return true;
        });
    }

    /**
     * 发消息给用户
     * @param $to
     * @param $message
     * @param $extra
     * @return void
     */
    public static function sendSystemMessage($to, $message, $extra = null)
    {
        self::sendCmdOrigin('live.message', array(
            'from' => 10000000,
            'to' => $to,
            'message' => $message,
            'extra' => $extra,
        ));
    }

    /**
     * 发送消息
     * @param $cmd
     * @param $data
     * @param $delay
     * @param $priority
     * @param $tube
     * @return void
     */
    private static function sendCmdOrigin($cmd, $data, $delay = 0, $priority = 1024, $tube = NsqConstant::TOPIC_XS_CMD)
    {
        $message = array(
            'cmd' => $cmd,
            'data' => $data
        );
        if(in_array($tube, array(NsqConstant::TOPIC_XS_DEMO, NsqConstant::TOPIC_XS_ADMIN, NsqConstant::TOPIC_XS_CMD, NsqConstant::TOPIC_XS_BETA_CRON, NsqConstant::TOPIC_FMS_BETA_CRON))){
            NsqClient::publish($tube, $message, $delay);
        }else{
            $client = new Client();
            $client->choose($tube);
            $client->set($message, $priority, $delay);
            $client->close();
        }
    }

    /**
     * @param $url
     * @return mixed|string
     */
    public static function _getNewHeadUrl($url)
    {
        if (!$url) return "";
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }
        if (preg_match('/public\/static\/icon\/.*/is', $url)) {
            return CDN_IMG_DOMAIN . $url;
        }
        //		return 'http://partying.oss-ap-southeast-1.aliyuncs.com/' . $url . "!head375";
        return CDN_IMG_DOMAIN . $url;
    }
}