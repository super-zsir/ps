<?php

namespace Imee\Comp\Common\Sdk;

class SdkAudio extends SdkBase
{
    private $domain = SERV_LEARN_ASR;

    // 成功code
    const SUCCESS_CODE = 200;

    public function __construct($format = self::FORMAT_JSON, $timeout = 10, $waning = 0.03)
    {
        parent::__construct($format, $timeout, $waning);
    }

    /**
     * 获取图片地址
     * @return string
     */
    private function getAudioUrl($data = [])
    {
        $filename = '';
        // url类型的
        if ($data['type'] == 'url') {
            $audio = $data['content'];
            $audio = str_replace('http:', 'needhttp', $audio);
            $audio = str_replace('https:', 'withhttps', $audio);
            $length = strrpos($audio, ":");
            $filename = $length ? substr($audio, 0, $length) : $audio;
            $filename = str_replace('needhttp', 'http:', $filename);
            $filename = str_replace('withhttps', 'https:', $filename);
        }
        return $filename;
    }

    public function query($data = [])
    {
        if (ENV == 'dev') {
            return [
                'text' => 'hello world'
            ];
        }
        $url = $this->domain . '/api/asr';
        // 去掉音频时长
        $audioUrl = $this->getAudioUrl($data);
        $postData = [
            'audio_url' => $audioUrl,
            'app_id'    => (int)($data['app_id'] ?? APP_ID),
            'scene'     => (string)($data['choice'] ?? '')
        ];
        [$res, $code] = $this->call($url, 'POST', ['json' => $postData]);
        $this->sendWarning($url, $audioUrl, $res, $code);
        return $res ? json_decode($res, true) : [];
    }

    private function sendWarning($url, $audioUrl, $res, $code)
    {
        /** @var SdkSlack $slack */
        $slack = factory_single_obj(SdkSlack::class);

        // 接口失败预警
        if ($code != self::SUCCESS_CODE) {
            $content = <<<STR
推荐组音频获取检测结果失败
> APIURL: {url}
> CODE: {code}
> ERROR: {error}
> 音频地址: {audio_url}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{audio_url}', '{create_time}'],
                [$url, $code, $this->_lastError, $audioUrl, date('Y-m-d H:i:s')],
                $content
            );
            $slack->sendMsg(SLACK_RECOMMEND_WEBHOOK, 'text', $wechatMsg);
        }

        // CODE码预警
        if (isset($res['code']) && $res['code'] != self::SUCCESS_CODE) {
            $content = <<<STR
推荐组音频获取检测结果CODE失败
> APIURL: {url}
> CODE: {code}
> 返回结果: {res}
> 音频地址: {audio_url}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{res}', '{audio_url}', '{create_time}'],
                [$url, $res['code'], json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $audioUrl, date('Y-m-d H:i:s')],
                $content
            );
            $slack->sendMsg(SLACK_RECOMMEND_WEBHOOK, 'text', $wechatMsg);
        }
    }
}