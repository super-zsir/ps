<?php

namespace Imee\Comp\Common\Sdk;

// Spam文本识别文档
// https://docs.qq.com/doc/DVm15clJDbkh5elNU 海外
// https://docs.qq.com/doc/DZEx4a2lPcXd4SWRz 国内
class SdkSpam extends SdkBase
{
    private $_domain = 'http://' . Serv_Spam_Text_Name;

    public function __construct($format = self::FORMAT_JSON, $timeout = 10, $waning = 0.03)
    {
        parent::__construct($format, $timeout, $waning);
        $this->setContentType('application/json');
    }

    public $language = ['zh_cn', 'zh_tw', 'th', 'id'];

    /**
     * 单条检测
     * @param $text
     * @param string $language
     * @param array $params
     * @param array $logParam
     * @return array|mixed
     * @throws \Exception
     */
    public function checkParmText($text, string $language = 'zh_tw', array $params = [], array $logParam = [], $data = [])
    {
//        if (!in_array($language, $this->language)) {
//            return [];
//        }
        $list = [[$text, $language]];

        // 区分业务场景
        $extra = [
            'app_id' => $data['app_id'] ?? APP_ID,
            'moduleTable' => $data['choice'] ?? ''
        ];

        [$result, $code] = $this->predictsSpam($list, $extra);

        if (ENV != 'dev' && ($code != 200 || !$result)) {
            $msgContent = <<<STR
推荐组SPAM检测服务异常
> CODE: {code}
> TASKID: {id}
> 内容: {text}
> 返回: {res}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{code}', '{id}', '{text}', '{res}', '{create_time}'],
                [$code, ($logParam['taskid'] ?? ''), $text, $result, date('Y-m-d H:i:s')],
                $msgContent
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $webhook = ENV == 'dev' ? SLACK_KEFU_WEBHOOK : SLACK_RECOMMEND_WEBHOOK;
            $obj->sendMsg($webhook, 'text', $wechatMsg);
        }
        //返回空不处理
        if (!$result) {
            return [];
        }
        $result = json_decode($result, true);
        $check = $result['data'] ?? [];
        $checkData = array_column($check, null, 'text');
        return $checkData[$text] ?? [];
    }

    /**
     * spam 多条检测接口
     * @param array $text
     * @param array $params
     * @return array
     */
    public function predictsSpam(array $text = [], array $params = [])
    {
        if (ENV == 'dev') {
            return $this->devSpam($text);
        }
        if (empty($text)) {
            return [false, 200];
        }
        $jsonData = array(
            'appid'      => isset($params['app_id']) ? (string)$params['app_id'] : (string)APP_ID,
            'user_texts' => $text
        );
        if ($params) {
            unset($params['app_id']);
            $jsonData = array_merge($jsonData, $params);
        }
        $url = $this->_domain . '/api/oversea_predictions';
        return $this->call($url, 'POST', ['json' => $jsonData]);
    }

    public function devSpam($text): array
    {
        $return = ['code' => 200, 'data' => []];
        if (!$text) {
            return $return;
        }
        $spam = [-1, 0, 1];
        foreach ($text as $value) {
            $return['data'][] = [
                'text'      => $value[0],
                'language'  => $value[1],
                'is_spam'   => $spam[array_rand($spam, 1)],
                'spam_rate' => mt_rand(0, 1),
                'model_ver' => 20211001
            ];
        }
        return $return;
    }

    // 文本垃圾识别
    // checkParmTextBanban('敏感词1')
    public function checkParmTextBanban($text = '', $params = [], $logParam = [])
    {
        if (empty($text)) {
            return false;
        }

        $jsonData = array(
            'user_text' => $text
        );

        if ($params) {
            $jsonData = array_merge($jsonData, $params);
        }

        $url = $this->_domain . '/api/predict_spam';

        if (ENV == 'dev') {
            $spam_dev = [-1, 0, 1];
            $rand_dev = rand(0, 2);
            $res = [
                'text'      => $text,
                'is_spam'   => $spam_dev[$rand_dev],
                'spam_rate' => round(rand(0, 1), 2),
                'model_ver' => '20210803'
            ];
        } else {
            $res = $this->request($url, true, json_encode($jsonData));
        }

        if (ENV != 'dev' && !in_array($this->_lastCode, $this->_allowCode)) {
            $content = <<<STR
推荐组SPAM检测服务异常
> URL: {url}
> CODE: {code}
> ERROR: {error}
> CSMSID: {id}
> 内容: {text}
> 参数: {params}
> 返回: {res}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{id}', '{text}', '{params}', '{res}', '{create_time}'],
                [$url, $this->_lastCode, $this->_lastError, ($logParam['id'] ?? ''), $text, json_encode($jsonData), json_encode($res), date('Y-m-d H:i:s')],
                $content
            );

            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        return $res;
    }

    /**
     * spam 多条检测接口
     * @param array $text
     * @param array $params
     * @return array|bool|mixed|string|null
     * @throws \Exception
     */
    public function predictsSpamBanban($text = [], $params = [])
    {
        if (empty($text)) {
            return false;
        }
        $jsonData = array(
            'user_texts' => $text
        );
        if ($params) {
            $jsonData = array_merge($jsonData, $params);
        }
        $url = $this->_domain . '/api/predicts_spam';
        $res = $this->request($url, true, json_encode($jsonData));

        if (!in_array($this->_lastCode, $this->_allowCode)) {
            $content = <<<STR
推荐组SPAM检测服务异常
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 内容: {text}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{text}', '{create_time}'],
                [$url, $this->_lastCode, $this->_lastError, json_encode($text), date('Y-m-d H:i:s')],
                $content
            );

            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        return $res;
    }
}
