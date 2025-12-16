<?php

namespace Imee\Service\Operate\Play\Tarot;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Operate\Play\GetKvConstant;
use Imee\Service\Rpc\PsService;

class ParamsService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    private $query;

    public function __construct()
    {
        $this->rpcService = new PsService();
        $this->query = [
            'key'           => GetKvConstant::KEY_TAROT_PARAMETERS,
            'business_type' => GetKvConstant::BUSINESS_TYPE_TAROT,
        ];
    }

    private function getRpcSwitchList()
    {
        list($res, $msg, $data) = $this->rpcService->getKv($this->query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $list = json_decode($data['data'][0]['value'] ?? '', true) ?? [];
        if (empty($list)) {
            throw new ApiException(ApiException::MSG_ERROR, '获取大区配置失败');
        }

        return $list;
    }

    public function getList(array $params): array
    {
        $list = $this->getRpcSwitchList();
        $data = [];
        foreach (GetKvConstant::TAROT_PARAMS_ID as $key => $val) {
            $weight = $list[$key] ?? 0;
            $data[] = [
                'id'      => $val,
                'name'    => $key,
                'cn_name' => GetKvConstant::TAROT_PARAMS_NAME[$key],
                'number'  => $key == 'after_percent' ? $weight . '%' : $weight,
                'weight'  => $weight,
            ];
        }
        $logs = BmsOperateLog::getFirstLogList('tarotparams', Helper::arrayFilter($data, 'id'));
        foreach ($data as &$v) {
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return ['data' => $data, 'total' => count($data)];
    }

    public function modify(array $params): array
    {
        $this->validation($params);
        // 需要把json整体查出来，替换掉修改的部分，在传给服务端
        $list = $this->getRpcSwitchList();
        if ($params['name'] == 'robot_switch') {
            $list[$params['name']] = round($params['weight'], 2);
        } else {
            $list[$params['name']] = $params['name'] == 'after_percent' ? (double) $params['weight'] : (int) $params['weight'];
        }
        list($res, $msg) = (new PsService())->setKv(array_merge($this->query, ['value' => $list]));

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $list];
    }

    public function validation(array $params)
    {
        if ($params['name'] == 'robot_switch') {
            if ($params['weight'] < 0 || $params['weight'] > 1){
                throw new ApiException(ApiException::MSG_ERROR, '修改AI时，数值必须为0-1之间的数');
            }
        } else if ($params['name'] == 'after_percent') {
            if (!is_numeric($params['weight'])) {
                throw new ApiException(ApiException::MSG_ERROR, '修改after时，数值必须为数字');
            }
        } else {
            if (filter_var($params['weight'], FILTER_VALIDATE_INT) === false) {
                throw new ApiException(ApiException::MSG_ERROR, '数值必须为整数');
            }

            if ($params['name'] == 'hours' && $params['weight'] > 1000) {
                throw new ApiException(ApiException::MSG_ERROR, '数值为0-1000内数字');
            }

            if ($params['name'] == 'limit_loss_money' && $params['weight'] >= 0) {
                throw new ApiException(ApiException::MSG_ERROR, '修改loss时，数值必须为负数且为整数');
            }
        }
    }
}