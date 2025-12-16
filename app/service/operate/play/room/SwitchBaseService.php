<?php

namespace Imee\Service\Operate\Play\Room;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class SwitchBaseService
{
    protected $type;
    protected $guid;
    protected $field;

    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, teenpatti_config', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList($this->guid, $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['name'] = XsBigarea::getBigAreaCnName($v['name']);
            $config = json_decode($v['teenpatti_config'], true);
            $v['coin_switch'] = intval($config[$this->field[0]] ?? 0);
            $v['diamond_switch'] = intval($config[$this->field[1]] ?? 0);
            $v['coin_switch'] = strval($v['coin_switch']);
            $v['diamond_switch'] = strval($v['diamond_switch']);
            $v['update_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['update_time'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $res;
    }

    public function modify(array $params): void
    {
        $data = [
            'bigarea_id'    => (int)$params['id'],
            'switch'         => (int)$params['coin_switch'],
            'diamond_switch' => (int)$params['diamond_switch'],
            'game_type'      => $this->type,
        ];
        $info = XsBigarea::findOne($data['bigarea_id']);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前修改大区不存在');
        }

        list($res, $msg) = (new PsService())->setTeenPattiSwitch($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }
}