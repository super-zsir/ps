<?php

/**
 * 发送数据到slack群
 */

namespace Imee\Comp\Common\Sdk;

use Imee\Exception\ApiException;

class SdkSlack extends SdkBase
{
    private $ClIENTID = "efa2c6d00b9d1ae7e8c688b0165a913b";
    private $CLIENTSECRET = "0848e957c416b852f36accacdd644960c2cdfacd";

    public function packMsgContent($msgType, $content): array
    {
        $content = '【' . SYSTEM_FLAG . '-' . ENV . '】' . PHP_EOL . $content;
        switch ($msgType) {
            case 'text':
                $postData = [
                    'text' => $content
                ];
                break;
            case 'markdown':
                //<https://google.com|this is a link>
                $pattern = '/\[([^\[]+)\]\((HTTPS?:[^\)]+)\)/i';
                $replacement = '<$2|$1>';
                $content = '```' . preg_replace($pattern, $replacement, $content) . '```';
                $postData = [
                    'blocks' => [
                        ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => $content]]
                    ]
                ];
                break;
            default:
                $postData = [];
        }

        return $postData;
    }

    /**
     * 群发信息
     * @param $webhookUrl
     * @param $msgType
     * @param $content
     * @return array
     */
    public function sendMsg($webhookUrl, $msgType, $content): array
    {
        $postData = $this->packMsgContent($msgType, $content);

        return $this->call($webhookUrl, 'POST', ['json' => $postData]);
    }

    /**
     * 通过channel私发信息
     * @param $botsToken
     * @param $channel
     * @param $msgType
     * @param $content
     * @return array
     */
    public function sendMsgByChannel($botsToken, $channel, $msgType, $content): array
    {
        $api = 'https://slack.com/api/chat.postMessage';
        $postData = $this->packMsgContent($msgType, $content);
        $postData['channel'] = $channel;

        return $this->call($api, 'POST', [
            'json'    => $postData,
            'headers' => ['Authorization' => 'Bearer ' . $botsToken]
        ]);
    }

    /**
     * 通过员工号私发信息
     * @param $appName
     * @param $jobNumArr
     * @param $content
     * @param string $msgType
     * @return array
     * @throws ApiException
     */
    public function sendMsgByJobNumArr($appName, $jobNumArr, $content, string $msgType = "text"): array
    {
        $token = $this->getAccessToken();

        $content = '【' . SYSTEM_FLAG . '-' . ENV . '】' . PHP_EOL . $content;
        if ($msgType == 'markdown') {
            $pattern = '/\[([^\[]+)\]\((HTTPS?:[^\)]+)\)/i';
            $replacement = '<$2|$1>';
            $content = '```' . preg_replace($pattern, $replacement, $content) . '```';
        }

        $postData = array(
            'access_token' => $token,
            'company_code' => 'ap',
            'type'         => 'slack',
            'msg_type'     => $msgType,
            'to_users'     => json_encode($jobNumArr),
            'app_name'     => $appName,
            'content'      => $content,
            'nonce'        => (string)mt_rand(100000, 999999),
            'timestamp'    => (string)(int)round(microtime(true) * 1000)
        );
        ksort($postData);
        $str = implode('', $postData);
        $signature = sha1($str);
        $postData['signature'] = $signature;
        $api = $this->getDomain() . '/ucapi/external/v1/sendMsgApi/sendAppMessage';
        return $this->call($api, 'POST', ['form_params' => $postData]);
    }

    /**
     * 获取token
     * @return string
     * @throws ApiException
     */
    public function getAccessToken(): string
    {
        //彩虹gaia申请工程效率相关开发者账号，可共用
        $postData = array(
            'client_id'     => $this->ClIENTID,
            'client_secret' => $this->CLIENTSECRET
        );
        $api = $this->getDomain() . "/ucapi/external/v1/authApi/getToken?client_id=" . $this->ClIENTID . "&client_secret=" . $this->CLIENTSECRET;
        [$resp, $code] = $this->call($api, 'POST', ['form_params' => $postData]);
        if ($code != 200) {
            throw new ApiException(ApiException::MSG_ERROR, '通知token获取错误1');
        }
        $retJsonArray = json_decode($resp, true);
        if ($retJsonArray === null) {
            throw new ApiException(ApiException::MSG_ERROR, '通知token获取错误2');
        }
        if (!array_key_exists("data", $retJsonArray) || !array_key_exists("access_token", $retJsonArray["data"])) {
            throw new ApiException(ApiException::MSG_ERROR, '通知token获取错误3');
        }
        return $retJsonArray["data"]["access_token"] ?? '';
    }

    private function getDomain(): string
    {
        if (defined('UC_DOMAIN')) {
            return UC_DOMAIN;
        }
        return 'https://uc.aopacloud.net';
    }
}
