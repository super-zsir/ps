<?php

/**
 * 发送数据到企业微信群
 */

namespace Imee\Comp\Common\Sdk;

class SdkQyWechat extends SdkBase
{
    public function getTextContent($content, $extra = []): array
    {
        if (!is_string($content)) {
            $content = print_r($content, true);
        }
        $msg = [
            'msgtype' => 'text',
            'agentid' => 1,
            'text'    => [
                'content' => $content
            ]
        ];
        if($extra){
            $msg['text'] = array_merge($msg['text'], $extra);
        }
        return $msg;
    }

    private function getMarkdownContent($content): array
    {
        return [
            'msgtype'  => 'markdown',
            'agentid'  => 1,
            'markdown' => [
                'content' => $content,
            ],
        ];
    }

    /**
     * @param $webhookUrl
     * @param $msgType
     * @param $content
     * @return array
     */
    public function sendMsg($webhookUrl, $msgType, $content): array
    {
        $content = '【' . SYSTEM_FLAG . '-' . ENV . '】' . $content;
        switch ($msgType) {
            case 'markdown':
                $postData = $this->getMarkdownContent($content);
                break;
            case 'text':
                $postData = $this->getTextContent($content);
                break;
            default:
                $postData = [];
        }

        return $this->call($webhookUrl, 'POST', ['json' => $postData]);
    }



    /**
     * @param $webhookUrl
     * @param $msgType
     * @param $content
     * @return array
     */
    public function sendMsgNew($webhookUrl, $content, $msgType = 'text', $extra = []): array
    {
        $content = '【' . SYSTEM_FLAG . '-' . ENV . '】' . $content;
        switch ($msgType) {
            case 'markdown':
                $postData = $this->getMarkdownContent($content);
                break;
            case 'text':
                $postData = $this->getTextContent($content, $extra);
                break;
            default:
                $postData = [];
        }
        return $this->call($webhookUrl, 'POST', ['json' => $postData]);
    }

    /**
     * @param $webhookUrl
     * @param $content
     * @return array
     */
    public function sendTextMsg($webhookUrl, $content): array
    {
        $postData = $this->getTextContent('【' . SYSTEM_FLAG . '-' . ENV . '】' . $content);

        return $this->call($webhookUrl, 'POST', ['json' => $postData]);
    }

    /**
     * @param $webhookUrl
     * @param $content
     * @return array
     */
    public function sendMarkdownMsg($webhookUrl, $content): array
    {
        $postData = $this->getMarkdownContent('【' . SYSTEM_FLAG . '-' . ENV . '】' . $content);

        return $this->call($webhookUrl, 'POST', ['json' => $postData]);
    }
}
