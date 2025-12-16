<?php
/**
 * 房间游戏热更新
 */

namespace Imee\Service\Game;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Config\BbcGameRenewal;
use Imee\Service\Helper;

class GamehotrenewalService
{
    public function getListAndTotal($params, $order = '', $page = 0, $pageSize = 0): array
    {
        $filter = [];
        if (!empty($params['app_id'])) {
            $filter[] = ['app_id', '=', $params['app_id']];
        }
        if (!empty($params['remark'])) {
            $filter[] = ['remark', 'like', $params['remark']];
        }
        if (!empty($params['game_name'])) {
            $filter[] = ['game_name', 'like', $params['game_name']];
        }
        if (!empty($params['version'])) {
            $filter[] = ['version', '=', $params['version']];
        }
        $result = BbcGameRenewal::getListAndTotal($filter, '*', $order, $page, $pageSize);
        $adminIds = [];
        foreach ($result['data'] as $v) {
            $adminIds[] = $v['op_uid'];
            $adminIds[] = $v['mop_uid'];
        }
        $adminIds = array_unique($adminIds);
        $adminIds = array_values($adminIds);
        $adminUsers = CmsUser::getAdminUserBatch($adminIds);
        foreach ($result['data'] as &$val) {
            $val['mop_name'] = $adminUsers[$val['mop_uid']]['user_name'] ?? '';
            $val['app_name'] = Helper::getAppName($val['app_id']);
            $val['op_name'] = $adminUsers[$val['op_uid']]['user_name'] ?? '';
            $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
            $val['modify_time'] = $val['modify_time'] ? date('Y-m-d H:i:s', $val['modify_time']) : '';
            $val['display_source_path'] = Helper::getHeadUrl($val['source_path']);
        }
        return $result;
    }

    public function deleteById($id): bool
    {
        return BbcGameRenewal::deleteById($id);
    }

    public function add($params): array
    {
        $data = [
            'app_id'      => $params['app_id'],
            'remark'      => $params['remark'],
            'game_name'   => $params['game_name'],
            'source_path' => $params['source_path'],
            'version'     => $params['version'],
            'orientation' => $params['orientation'],
            'op_uid'      => $params['admin_id'],
            'status'      => BbcGameRenewal::STATUS_INVALID,
            'dateline'    => time(),
        ];
        return BbcGameRenewal::add($data);
    }

    public function edit($id, $params): array
    {
        $data = [
            'app_id'      => $params['app_id'],
            'remark'      => $params['remark'],
            'game_name'   => $params['game_name'],
            'source_path' => $params['source_path'],
            'version'     => $params['version'],
            'orientation' => $params['orientation'],
            'mop_uid'     => $params['admin_id'],
            'modify_time' => time(),
        ];

        return BbcGameRenewal::edit($id, $data);
    }

    public function status($id, $params): array
    {
        $data = [
            'status'      => $params['status'],
            'mop_uid'     => $params['admin_id'],
            'modify_time' => time(),
        ];

        return BbcGameRenewal::edit($id, $data);
    }
}
