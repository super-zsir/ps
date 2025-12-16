<?php

namespace Imee\Service\Operate\User;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegisterLoginLimitService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsBigarea::getListAndTotal($conditions, 'id,name,register_login_config', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigAreaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('registerloginlimit', $bigAreaIds);
        foreach ($list['data'] as &$v) {
            $v['name'] = XsBigarea::getBigAreaCnName($v['name']);
            $config = json_decode($v['register_login_config'], true);
            $v['device_register_num_limit'] = $config['device_register_num_limit'] ?? 7;
            $v['device_daily_register_num_limit'] = $config['device_daily_register_num_limit'] ?? 2;
            $v['device_daily_login_num_limit'] = $config['device_daily_login_num_limit'] ?? 7;
            $v['device_weekly_login_num_limit'] = $config['device_weekly_login_num_limit'] ?? 7;
            $v['update_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['update_time'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
         }
        return $list;
    }

    public function edit(array $params)
    {
        list($res, $msg) = (new PsService())->updateRegisterLoginConfig($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }

        return $conditions;
    }
}