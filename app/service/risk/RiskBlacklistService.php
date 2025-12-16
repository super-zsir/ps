<?php

namespace Imee\Service\Risk;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xsst\XsstRiskBlacklist;

/**
 * 风控黑名单管理
 */
class RiskBlacklistService
{
    public function getListAndTotal(array $params, $order = '', int $page = 0, int $pageSize = 0): array
    {
        $condition = [];
        if (!empty($params['type'])) {
            $condition[] = ['type', '=', $params['type']];
        }
        if (!empty($params['handle_method'])) {
            $condition[] = ['handle_method', '=', $params['handle_method']];
        }
        if (isset($params['status']) && in_array($params['status'], ['0', '1'])) {
            $condition[] = ['status', '=', $params['status']];
        }
        if (!empty($params['begin_time'])) {
            $condition[] = ['update_time', '>=', strtotime($params['begin_time'])];
        }
        if (!empty($params['end_time'])) {
            $condition[] = ['update_time', '<=', strtotime($params['end_time'] . ' 23:59:59')];
        }

        $result = XsstRiskBlacklist::getListAndTotal($condition, '*', $order, $page, $pageSize);
        $adminIds = array_column($result['data'], 'admin_id');
        $adminIds = array_unique($adminIds);
        $adminIds = array_values($adminIds);
        $adminList = CmsUser::getAdminUserBatch($adminIds);
        foreach ($result['data'] as &$val) {
            $val['status_name'] = XsstRiskBlacklist::$statusMapping[$val['status']] ?? '';
            $val['handle_method_name'] = XsstRiskBlacklist::$handleMethodMapping[$val['handle_method']] ?? '';
            $val['type_name'] = XsstRiskBlacklist::$typeMapping[$val['type']] ?? '';
            $val['admin_name'] = $adminList[$val['admin_id']]['user_name'] ?? '';
            $val['update_time'] = date('Y-m-d H:i:s', $val['update_time']);
            $val['status'] = (string)$val['status'];
        }
        return $result;
    }

    public function getOpenListAndTotal(array $params, $order = '', int $page = 0, int $pageSize = 0): array
    {
        $condition = [];
        if (!empty($params['update_time'])) {
            $condition[] = ['update_time', '>=', $params['update_time']];
        }
        $result = XsstRiskBlacklist::getListAndTotal($condition, '*', $order, $page, $pageSize);
        $adminIds = array_column($result['data'], 'admin_id');
        $adminIds = array_unique($adminIds);
        $adminIds = array_values($adminIds);
        $adminList = CmsUser::getAdminUserBatch($adminIds);
        foreach ($result['data'] as &$val) {
            $val['status_name'] = XsstRiskBlacklist::$statusMapping[$val['status']] ?? '';
            $val['handle_method_name'] = XsstRiskBlacklist::$handleMethodMapping[$val['handle_method']] ?? '';
            $val['type_name'] = XsstRiskBlacklist::$typeMapping[$val['type']] ?? '';
            $val['admin_name'] = $adminList[$val['admin_id']]['user_name'] ?? '';
        }
        return $result;
    }

    public function add($params): array
    {
        [$result, $msg] = $this->filter($params);
        if (!$result) {
            return [false, $msg];
        }

        $condition = [];
        $condition[] = ['type', '=', $params['type']];
        $condition[] = ['rule_content', '=', $params['rule_content']];
        if (XsstRiskBlacklist::findOneByWhere($condition, true)) {
            return [false, '已经存在该黑名单'];
        }

        $now = time();
        $insert = [
            'type'          => $params['type'],
            'rule_content'  => $params['rule_content'],
            'handle_method' => $params['handle_method'],
            'status'        => $params['status'] ?? XsstRiskBlacklist::STATUS_INVALID,
            'admin_id'      => $params['admin_id'],
            'create_time'   => $now,
            'update_time'   => $now
        ];

        return XsstRiskBlacklist::add($insert);
    }

    public function filter($params): array
    {
        if (mb_strlen($params['rule_content']) > 250) {
            return [false, '长度超过最大250个字符'];
        }

        switch ($params['type']) {
            case 'ip':
                if (!filter_var($params['rule_content'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return [false, 'ipv4验证失败'];
                }
                break;
            case 'ip_subnet':
                //例如37.164.187,用ip验证规则
                if (!filter_var($params['rule_content'] . '.1', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return [false, 'ip子网验证失败'];
                }
                break;
            case 'mac':
            case 'channel':
            case 'special_word':
            case 'simulator':
            case 'app_version':
            case 'backup_1':
            case 'backup_2':
            case 'backup_3':
            case 'backup_4':
            case 'backup_5':
                break;
            default:
                return [false, '类型不支持'];
        }
        return [true, ''];
    }

    public function status($id, $status): array
    {
        $update = [
            'status'      => $status,
            'update_time' => time()
        ];

        return XsstRiskBlacklist::edit($id, $update);
    }

    public function edit($id, $params): array
    {
        [$result, $msg] = $this->filter($params);
        if (!$result) {
            return [false, $msg];
        }

        $condition = [];
        $condition[] = ['type', '=', $params['type']];
        $condition[] = ['rule_content', '=', $params['rule_content']];
        $info = XsstRiskBlacklist::findOneByWhere($condition, true);
        if ($info && $info['id'] != $id) {
            return [false, '已经存在该黑名单'];
        }

        $update = [
            'type'          => $params['type'],
            'rule_content'  => $params['rule_content'],
            'handle_method' => $params['handle_method'],
            'status'        => $params['status'] ?? XsstRiskBlacklist::STATUS_INVALID,
            'admin_id'      => $params['admin_id'],
            'update_time'   => time()
        ];

        return XsstRiskBlacklist::edit($id, $update);
    }
}