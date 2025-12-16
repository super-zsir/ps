<?php

namespace Imee\Service\Domain\Service\Csms\Task;

use Imee\Comp\Common\Rpc\BaseRpcx;

class CsmsRpcReviewService extends BaseRpcx
{
    /**
     * 处理审核结果外显
     * @param $data
     * @param $config
     * @return array
     */
    public function handle($data, $config): array
    {
        $gateway = $config['gateway'] ?? '';
        $servName = $config['servName'] ?? '';
        $method = $config['method'] ?? '';

        if (!$gateway) {
            $gateway = Serv_Rpc_Gateway_Domain . "/rpc";
            $this->host = Serv_Rpc_Gateway_Host;
        }

        $this->gateway = $gateway;

        try {
            $res = $this->request($servName, $method, $data);
            if ($res) {
                if (isset($res['state']) && is_numeric($res['state'])) {
                    $res['state'] = $res['state'] == 1;
                    return $res;
                } else {
                    return [
                        'state'   => false,
                        'message' => $res['message'] ?? 'rpc审核回调接口错误',
                        'retry'   => $res['retry'] ?? 1,
                        'result'  => $res
                    ];
                }
            } else {
                return [
                    'state'   => false,
                    'message' => 'rpc接审核回调接口未返回任何结果'
                ];
            }
        } catch (\Exception $e) {
            return [
                'state'   => false,
                'message' => 'rpc异常错误：' . $e->getMessage()
            ];
        }
    }
}