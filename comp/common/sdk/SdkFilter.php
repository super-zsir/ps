<?php

namespace Imee\Comp\Common\Sdk;

/**
 * 新版敏感词
 * Class SdkFilter
 */
class SdkFilter extends SdkBase
{
    private static $filterUrl = '';

    public function __construct($format = self::FORMAT_JSON, $timeout = 10, $waning = 0.03)
    {
        parent::__construct($format, $timeout, $waning);
        $this->setContentType('application/json');

        self::$filterUrl = (ENV == 'dev') ? 'http://47.114.166.11:6080' : 'http://' . Serv_Im_Proxy_Name;
    }

    /**
     * 敏感词基础分类维护
     * @return bool|mixed|string|null
     */
    public function dirtySearchCond()
    {
        $url = self::$filterUrl . '/filter/dirtySearchCond';
        return $this->request($url);
    }

    // 获取敏感词
    public function getDirtys($where = [], $offset = 1, $limit = 100)
    {
        $params = array_merge($where, [
            'pos'   => $offset,
            'limit' => $limit
        ]);
        $url = self::$filterUrl . '/filter/dirty?' . http_build_query($params);
        return $this->request($url);
    }

    // 批量添加敏感词
    // eg:
    // addDirtys(array(
    // 	array("type" => "normal", "text" => "liusw", "reason" => "test", "danger" => 1, "vague" => 1),
    // 	array("type" => "normal", "text" => "敏感词2", "reason" => "不知道", "danger" => 1, "vague" => 1),
    // ))
    public function addDirtys($dirtys = array())
    {
        if (empty($dirtys)) {
            return false;
        }

        $jsonData = array(
            'data' => $dirtys
        );

        $url = self::$filterUrl . '/filter/dirty';
        return $this->request($url, true, json_encode($jsonData));
    }


    /**
     * 修改敏感词
     * @param array $dirtys
     * @return bool|mixed|string|null
     */
    public function modDirtys(array $dirtys = [])
    {
        if (empty($dirtys)) {
            return false;
        }
        $jsonData = [
            'data' => $dirtys
        ];
        $url = self::$filterUrl . '/filter/dirty';
        return $this->httpRequest($url, true, json_encode($jsonData), null, null, 'PUT');
    }


    // 批量删除敏感词
    // eg:
    // deleteDirtys(array('敏感词1', '敏感词2'))
    public function deleteDirtys($dirtyTexts = array(), $app = 1)
    {
        if (empty($dirtyTexts)) {
            return false;
        }

        $jsonData = array(
            'data' => $dirtyTexts,
            'app'  => $app
        );

        $url = self::$filterUrl . '/filter/dirty';

        return $this->httpRequest($url, true, json_encode($jsonData), null, null, 'DELETE');
    }

    // 检查敏感词
    // eg:
    // checkDirty('敏感词1', 1))
    /**
     * @throws \Exception
     */
    public function checkDirty($text = '', $type = 0, $condition = 'input,chat,room', $dangerOp = 0, $danger = 0, $appId = APP_ID)
    {
        if (empty($text)) {
            return false;
        }

        $jsonData = array(
            'msg'       => $text,
            'type'      => $type,
            'condition' => $condition,
            'danger_op' => $dangerOp,
            'app'       => $appId
        );
        // 危险等级
        if ($danger) {
            $jsonData['danger'] = $danger;
        }
        $url = self::$filterUrl . '/filter/checkdirty';
        $res = $this->request($url, true, json_encode($jsonData));
        if (!in_array($this->_lastCode, $this->_allowCode)) {
            $wechatContent = <<<STR
新敏感词检测服务异常
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 内容: {data}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{error}', '{data}', '{create_time}'],
                [$url, $this->_lastCode, $this->_lastError, json_encode($jsonData), date('Y-m-d H:i:s')],
                $wechatContent
            );

            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }

        return $res;
    }
}
