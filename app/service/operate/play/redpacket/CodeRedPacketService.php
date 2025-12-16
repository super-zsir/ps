<?php

namespace Imee\Service\Operate\Play\Redpacket;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Operate\RedPacketService;
use Imee\Service\Rpc\PsService;

class CodeRedPacketService extends BaseService
{
    public function getBigAreaList(array $params): array
    {
        $list = XsBigarea::getListAndTotal([], 'id,name, code_red_packet_config', 'id asc',$params['page'] ?? 1, $params['limit'] ?? 15);
        $bigAreaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('coderedpacketswitch', $bigAreaIds);
        foreach ($list['data'] as &$v) {
            $v['name'] = XsBigarea::getBigAreaCnName($v['name']);
            $config = json_decode($v['code_red_packet_config'], true);
            $v['switch'] = (string)($config['switch'] ?? 0);
            $v['code_rule'] = (string)($config['code_rule'] ?? 0);
            $v['copy_switch'] = (string)($config['copy_switch'] ?? 0);
            $v['update_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['update_time'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $list;
    }

    public function modifyBigAreaSwitch(array $params)
    {
        $data = [
            'big_area_id' => (int) $params['id'],
            'code_config' => [
                'switch'      => (int) $params['switch'],
                'copy_switch' => (int) $params['copy_switch'],
                'code_rule'   => (int) $params['code_rule'],
            ],
            'op'          => self::OP_SWITCH,
            'modify_type' => self::MODIFY_TYPE_CODE
        ];
        $this->modify($data);
    }

    public function getCountList(array $params): array
    {
        $conditions = $this->getNumConditions($params);
        $list = XsBigarea::getListAndTotal($conditions, 'id, code_red_packet_config', 1, 15);
        $bigAreaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('coderedpacketnum', $bigAreaIds);
        foreach ($list['data'] as &$v) {
            $v['big_area'] = (string) $v['id'];
            $detail = json_decode($v['code_red_packet_config'], true)['detail'] ?? [];
            $this->formatConfig($v, $detail);
            $v['update_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['update_time'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $list;
    }

    public function modifyNumber(array $params)
    {
        $detail = $this->formatData($params);
        $data = [
            'big_area_id' => $params['id'],
            'code_config' => [
                'detail' => $detail,
            ],
            'op' => self::OP_CONFIG,
            'modify_type' => self::MODIFY_TYPE_CODE
        ];
        list($res, $msg) = (new PsService())->setRedPacketConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }
}