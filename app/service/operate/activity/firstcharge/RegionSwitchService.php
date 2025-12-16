<?php

namespace Imee\Service\Operate\Activity\Firstcharge;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsTopUpActivity;
use Imee\Models\Xs\XsTopUpActivityReward;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;

class RegionSwitchService
{
    public function getList(): array
    {
        $list = XsBigarea::getListAndTotal([], 'id, first_recharge_act_switch');
        $logs = BmsOperateLog::getFirstLogList('firstchargeActivityregionswitch', array_column($list['data'], 'id'));
        foreach ($list['data'] as &$v) {
            $v['id'] = (string) $v['id'];
            $v['status'] = (string) $v['first_recharge_act_switch'];
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $list;
    }

    public function modify(array $params): array
    {
        $id = $params['id'] ?? 0;
        $status = $params['status'] ?? 0;

        $info = XsBigarea::findOne($id);

        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '大区不存在');
        }

        if ($status == 1) {
            $activity = XsTopUpActivity::findOneByWhere([
                ['bigarea_id', '=', $id],
                ['type', '=', XsTopUpActivity::TYPE_FIRST_RECHARGE]
            ]);
            $reward = XsTopUpActivityReward::findOneByWhere([
                ['top_up_activity_id', '=', $activity['id'] ?? 0],
                ['bigarea_id', '=', $id]
            ]);
            if (empty($activity) || empty($reward)) {
                throw new ApiException(ApiException::MSG_ERROR, '该大区奖励配置为空，无法开启');
            }
        }

        $update = [
            'first_recharge_act_switch'      => $status,
        ];

        list($res, $msg) = XsBigarea::edit($id, $update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '修改失败：'. $msg);
        }

        return ['id' => $id, 'after_json' => $update, 'before' => ['first_recharge_act_switch' => $info['first_recharge_act_switch']]];
    }
}