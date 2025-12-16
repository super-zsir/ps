<?php

namespace Imee\Service\Rpc;

use Imee\Models\Rpc\OaNewRpc;

class OaNewService
{
    const OPEN_KEY = 'ee8a1072f52ff2cc1bdba2c31621a369';
    const OPEN_SECRET = '731dadc74ae77b0d2698e1945f1861bbcaa884d1';

    /** @var OaNewRpc $oaRpc */
    private $oaRpc;

    /** @var  OaNewService $service */
    private static $service;

    public function __construct()
    {
        $this->oaRpc = new OaNewRpc();
    }

    public function login(): array
    {
        $params = [];

        $params[] = ['name' => 'client_id', 'contents' => self::OPEN_KEY];
        $params[] = ['name' => 'client_secret', 'contents' => self::OPEN_SECRET];

        list($result, $code) = $this->oaRpc->call(OaNewRpc::LOGIN, ['multipart' => $params]);
        if ($code != 200 || !$result['success']) {
            return [];
        }
        return $result['data'] ?? [];
    }

    public function templateParams($formCodeId): array
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }

        $params = $this->sign(['formCodeId' => $formCodeId], $token);

        $_params = [];
        foreach ($params as $k => $v) {
            $_params[] = ['name' => $k, 'contents' => $v];
        }

        list($result, $code) = $this->oaRpc->call($this->oaRpc::TEMPLATE_PARAMS, ['multipart' => $_params]);

        if ($code != 200) {
            return [false, 'oa获取模板元素失败：' . json_encode(compact('result'))];
        }
        return [true, $result['data']];
    }

    public function createOrder(array $data): array
    {
        $token = $this->login();
        $token = $token['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }
        if (isset($data['widgetValue']) && is_array($data['widgetValue'])) {
            $data['widgetValue'] = json_encode($data['widgetValue']);
        }
        $data = $this->sign($data, $token);
        list($result, $code) = $this->oaRpc->call($this->oaRpc::CREATE_ORDER, ['multipart' => $this->_format($data)]);

        if ($code != 200 || !$result['success']) {
            return [false, "oa提交申请单失败：msg:{$result['msg']} code: {$result['code']}"];
        }

        return [true, $result['data'] ?? []];
    }

    public function getOrderInfo(string $orderNo): array
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }

        $data = ['flowInstId' => $orderNo];
        $data = $this->sign($data, $token);

        list($result, $code) = $this->oaRpc->call($this->oaRpc::ORDER_INFO, ['multipart' => $this->_format($data)]);

        if ($code != 200) {
            return [false, 'oa获取审批单详情失败：' . json_encode($result)];
        }

        return [true, $result];
    }

    public function getOrderInst(string $formCodeId, string $orderNo): array
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }

        $data = ['formInstId' => $orderNo,'formCodeId' => $formCodeId];
        $data = $this->sign($data, $token);

        list($result, $code) = $this->oaRpc->call($this->oaRpc::CREATE_ORDER_INST, ['multipart' => $this->_format($data)]);

        if ($code != 200) {
            return [false, 'oa获取审批单流程实例失败：' . json_encode(compact('result'))];
        }

        return [true, $result['data'] ?? []];
    }

    public function uploadFile($files)
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($files) || empty($token)) {
            return [false, "获取token失败"];
        }
        $params = $this->sign([], $token);
        $params['file'] = $files;
        $params = $this->_format($params);
        list($result, $code) = $this->oaRpc->call($this->oaRpc::UPLOAD_FILE, ['multipart' => $params]);
        if ($code != 200 || !$result['success']) {
            return [false, "上传文件失败"];
        }
        return [true, $result['data'] ?? []];
    }

    /**
     * 签名
     * @param $params
     * @return array
     */
    private function sign($params, $token)
    {
        $nonce = mt_rand(100000, 999999);
        $timestamp = time() * 1000;
        $params['timestamp'] = $timestamp;
        $params['nonce'] = $nonce;
        $params['access_token'] = $token;
        ksort($params);
        $str = implode('', $params);
        $signature = sha1($str);
        $params['signature'] = $signature;
        return $params;
    }

    private function _format($params)
    {
        $return = [];
        foreach ($params as $key => $v) {
            if ($key == 'file') {
                $return[] = ['name' => 'file', 'contents' => file_get_contents($v), 'filename' => basename($v)];
            } else {
                $return[] = ['name' => $key, 'contents' => $v];
            }

        }
        return $return;
    }

    /**
     * @return OaNewService
     */
    public static function getInstance(): OaNewService
    {
        if (is_null(self::$service)) {
            self::$service = new OaNewService();
        }
        return self::$service;
    }

}