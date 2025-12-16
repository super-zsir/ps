<?php

namespace Imee\Comp\Common\Sdk;

/**
 * 数美音频sdk
 */
class SdkShumei extends SdkBase
{
    // key
    const AccessKey = "fLTKAZfq5wfGvSRSmuTo";

    // 上传地址
    const ServerUrl = "http://api-audio-sh.fengkongcloud.com/v2/saas/anti_fraud/audio";

    // 查询地址
    const QueryUrl = "http://api-audio-sh.fengkongcloud.com/v2/saas/anti_fraud/query_audio";

    // 成功code
    const SUCCESS_CODE = 1100;

    public function __construct($format = self::FORMAT_JSON, $timeout = 5, $waning = 0.1)
    {
        parent::__construct($format, $timeout, $waning);
        $this->setContentType('application/json');
    }

    /**
     * 上传音频文件检测
     * 注意 注意  注意
     * 注意 注意  注意
     *
     * ****** type 花钱的，省着点花 ******
     *
     * 注意 注意  注意
     * 注意 注意  注意
     * @throws \Exception
     */
    public function uploadUrlAudio($btid, $url, $type = 'AD_PORN_POLITICAL_MOAN')
    {
        $request = [
            'type' => $type,
            'btId' => $btid,
            'data' => array(
                'url'        => $this->getCdnVoiceUrl($url),
                'formatInfo' => array(
//					'format' => strtolower(pathinfo($url)['extension'])
                    'format' => 'wav'
                )
            )
        ];
        $wechatParam = $request;
        // accesskey 不能发送到预警里
        $request['accessKey'] = self::AccessKey;

        $res = $this->request(self::ServerUrl, true, json_encode($request));

        // 接口预警
        if (!in_array($this->_lastCode, $this->_allowCode)) {
            $content = <<<STR
数美URL音频文件上传失败
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 图片URL: {path}
> 请求参数: {request}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{path}', '{request}', '{create_time}'],
                [self::ServerUrl, $this->_lastCode, $this->_lastError, $url, json_encode($wechatParam), date('Y-m-d H:i:s')],
                $content
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        // CODE码预警
        if (isset($res['code']) && $res['code'] != self::SUCCESS_CODE) {
            $wecontent = <<<STR
数美URL音频文件上传CODE失败
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 图片URL: {path}
> 请求参数: {request}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{path}', '{request}', '{create_time}'],
                [self::ServerUrl, $res['code'], $res['message'], $url, json_encode($wechatParam), date('Y-m-d H:i:s')],
                $wecontent
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        return $res;
    }

    /**
     * 结果查询接口
     * @param $btid
     * @return bool|mixed|string|null
     * @throws \Exception
     */
    public function queryAudio($btid)
    {
        $res = $this->request(self::QueryUrl, true, json_encode(
            array(
                'accessKey' => self::AccessKey,
                'btId'      => $btid
            )
        ));

        // 接口预警
        if (!in_array($this->_lastCode, $this->_allowCode)) {
            $content = <<<STR
数美音频获取检测结果失败
> URL: {url}
> CODE: {code}
> ERROR: {error}
> BTID: {btid}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{btid}', '{create_time}'],
                [self::ServerUrl, $this->_lastCode, $this->_lastError, $btid, date('Y-m-d H:i:s')],
                $content
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        // CODE码预警
        if (isset($res['code']) && $res['code'] != self::SUCCESS_CODE) {
            $content = <<<STR
数美音频获取检测结果CODE失败
> URL: {url}
> CODE: {code}
> ERROR: {error}
> BTID: {btid}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{btid}', '{create_time}'],
                [self::ServerUrl, $res['code'], $res['message'], $btid, date('Y-m-d H:i:s')],
                $content
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        return $res;
    }

    /**
     * 获取cdn URL
     * @param $url
     * @return string
     */
    public function getCdnVoiceUrl($url): string
    {
        if (!$url) {
            return '';
        }
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }
        return CDN_VOICE_DOMAIN . ltrim($url, '/');
    }

    /**
     * 获取图片地址
     * @return string
     */
    public function getAudioUrl($data = [])
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
            $filename = str_replace('withhttps', 'https', $filename);
        }
        return $filename;
    }
}