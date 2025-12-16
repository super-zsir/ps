<?php

namespace Imee\Service\Rpc;

use Imee\Models\Rpc\PtAdminRpc;

class PtAdminService
{
    /** @var PtAdminRpc $ptAdminRpc */
    private $ptAdminRpc;

    public function __construct()
    {
        $this->ptAdminRpc = new PtAdminRpc();
    }

    public function pushIndex($params): array
    {

        list($result, $code) = $this->ptAdminRpc->call(PtAdminRpc::API_PUSH_INDEX, ['json' => $params]);

        if ($code != 200 || !$result['success']) {
            return [false, ['msg' => $result['msg'] ?? '接口异常']];
        }

        return [true, $result];
    }

    public function search($params): array
    {
        list($result, $code) = $this->ptAdminRpc->call(PtAdminRpc::API_SEARCH, ['json' => $params]);

        if ($code != 200 || !$result['success']) {
            return [false, ['msg' => $result['msg'] ?? '接口异常']];
        }

        return [true, $result];
    }

    public function audit($params): array
    {
        list($result, $code) = $this->ptAdminRpc->call(PtAdminRpc::API_AUDIT, ['json' => $params]);

        if ($code != 200 || !$result['success']) {
            return [false, ['msg' => $result['msg'] ?? '接口异常']];
        }

        return [true, $result];
    }


    public function pushMultIndex($params): array
    {
        list($result, $code) = $this->ptAdminRpc->call(PtAdminRpc::API_PUSH_MULT_INDEX, ['json' => $params]);
        if ($code != 200 || !$result['success']) {
            return [false, ['msg' => $result['msg'] ?? '接口异常']];
        }
        return [true, $result];
    }


    /**
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Libs\Rpc\InvalidApiNameException
     */
    public function push($params)
    {
        list($result, $code) = $this->ptAdminRpc->call(PtAdminRpc::API_CSMS_PUSH, ['json' => $params]);

        if ($code == 200) {
            if(isset($result['success']) && $result['success']){
                return [true, $result];
            }
        }
        return [false, ['code' => $code, 'result' => json_encode($result)]];

    }


}