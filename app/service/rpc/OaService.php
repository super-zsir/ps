<?php

namespace Imee\Service\Rpc;


use Imee\Models\Rpc\OaRpc;

class OaService
{
    const OPEN_KEY = '455946170b14713f7cd18f65b89849cc';
    const OPEN_SECRET = '281d69987e7960f588678848070bd2582f0e3f30';

    // 活动发布审核
    const TEMPLATE_ID_ACTIVITY = 748159;

    // 0 审核中
    // 1 已通过
    // 2 已驳回
    // 3 已撤销
    // 4 离职交接中
    // 5 离职交接完成
    const PROCESS_STATUS_AUDITING = 0;
    const PROCESS_STATUS_PASS = 1;
    const PROCESS_STATUS_REJECT = 2;
    const PROCESS_STATUS_CANCEL = 3;
    const PROCESS_STATUS_TRANSFER = 4;
    const PROCESS_STATUS_TRANSFER_COMPLETE = 5;

    // OA 审批状态
    public static $processStatus = [
        self::PROCESS_STATUS_AUDITING          => '审核中',
        self::PROCESS_STATUS_PASS              => '已通过',
        self::PROCESS_STATUS_REJECT            => '已驳回',
        self::PROCESS_STATUS_CANCEL            => '已撤销',
        self::PROCESS_STATUS_TRANSFER          => '离职交接中',
        self::PROCESS_STATUS_TRANSFER_COMPLETE => '离职交接完成',
    ];

    /** @var OaRpc $oaRpc */
    private $oaRpc;

    /** @var  OaService $service */
    private static $service;

    public function __construct()
    {
        $this->oaRpc = new OaRpc();
    }

    /**
     * 获取token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Comp\Common\Rpc\InvalidApiNameException
     */
    public function login(): array
    {
        $params = [];

        $params[] = ['name' => 'client_id', 'contents' => self::OPEN_KEY];
        $params[] = ['name' => 'client_secret', 'contents' => self::OPEN_SECRET];

        list($result, $code) = $this->oaRpc->call(OaRpc::LOGIN, ['multipart' => $params]);
        if ($result['code'] != 1) {
            return [];
        }
        return $result['data'] ?? [];
    }

    /**
     * 获取模版配置信息
     * @param int $templateId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Comp\Common\Rpc\InvalidApiNameException
     */
    public function templateDetail(int $templateId): array
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }

        $params = $this->sign(['template_id' => $templateId], $token);

        $_params = [];
        foreach ($params as $k => $v) {
            $_params[] = ['name' => $k, 'contents' => $v];
        }

        list($result, $code) = $this->oaRpc->call($this->oaRpc::TEMPLATE_DETAIL, ['multipart' => $_params]);
        if ($result['code'] != 1) {
            return [false, 'oa获取模板元素失败：' . json_encode(compact('result'))];
        }
        return [true, $result['data']];
    }

    /**
     * 创建审批单
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Comp\Common\Rpc\InvalidApiNameException
     */
    public function createOrder(array $data): array
    {
        $token = $this->login();
        $token = $token['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }
        $data = $this->sign($data, $token);
        list($result, $code) = $this->oaRpc->call($this->oaRpc::CREATE_ORDER, ['form_params' => $data]);
        if ($result['code'] != 1) {
            return [false, "oa提交申请单失败：msg:{$result['msg']} code: {$result['code']}"];
        }

        return [true, $result['data'] ?? []];
    }

    /**
     * 获取审批详情
     * @param string $orderNo
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Comp\Common\Rpc\InvalidApiNameException
     */
    public function getOrderInfo(string $orderNo): array
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($token)) {
            return [false, '获取token失败'];
        }

        $data = ['sid' => $orderNo];
        $data = $this->sign($data, $token);

        list($result, $code) = $this->oaRpc->call($this->oaRpc::ORDER_INFO, ['form_params' => $data]);
        if ($result['code'] != 1 || empty($result['data']['process_info'])) {
            return [false, 'oa获取审批单详情失败：' . json_encode($result)];
        }

        $status = $result['data']['process_info']['status'] ?? '';

        return [true, $status];
    }
    /**
     * 上传文件
     * @param $files
     * @param $jobNum
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Comp\Common\Rpc\InvalidApiNameException
     */
    public function uploadFile($files, $jobNum)
    {
        $data = $this->login();
        $token = $data['access_token'] ?? '';
        if (empty($files) || empty($token)) {
            return [false, "获取token失败"];
        }
        $data = ['type' => pathinfo($files, PATHINFO_EXTENSION), 'job_num' => (string) $jobNum];
        $data = $this->sign($data, $token);
        // 文件不参与签名
        $data['media'] = $files;
        list($result, $code) = $this->oaRpc->call($this->oaRpc::UPLOAD_FILE, ['multipart' => $this->_format($data)]);
        if ($result['code'] != 1) {
            return [false, "上传文件失败" . json_encode($result)];
        }
        return [true, $result['data'] ?? []];
    }

    /**
     * 签名
     * @param $params
     * @param $token
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
            if ($key == 'media') {
                $return[] = ['name' => 'media', 'contents' => file_get_contents($v), 'filename' => basename($v)];
            } else {
                $return[] = ['name' => $key, 'contents' => $v];
            }
        }
        return $return;
    }

    /**
     * @return OaService
     */
    public static function getInstance(): OaService
    {
        if (is_null(self::$service)) {
            self::$service = new OaService();
        }
        return self::$service;
    }

}