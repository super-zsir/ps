<?php

namespace Imee\Service\Operate\Whitelist;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserCountry;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xsst\BmsWhitelistSetting;
use Imee\Models\Config\XsstUidWhiteList;
use Imee\Service\Helper;

class UserWhiteListService
{
    public function getList(array $params, int $page, int $pageSize): array
    {
        $conditions = $this->getConditions($params);
        if (empty($conditions)) {
            return [];
        }
        $list = XsstUidWhiteList::getListAndTotal($conditions, '*', 'id desc', $page, $pageSize);
        if ($list['total'] == 0) {
            return [];
        }
        $uids = array_column($list['data'], 'uid');
        $uids = $this->handleIds($uids);
        $users = XsUserProfile::getUserProfileBatch($uids);
        $countrys = XsUserCountry::getUserCountryBatch($uids);
        $languages = XsUserSettings::getUserSettingBatch($uids);
        $admins = array_column($list['data'], 'admin_id');
        $admins = CmsUser::getAdminUserBatch($admins);
        foreach ($list['data'] as &$v) {
            $v['white_list_name'] = $v['type'];
            $v['uname'] = $users[$v['uid']]['name'] ?? '';
            $v['bigarea'] = $v['bigarea_id'];
            $v['uarea'] = $countrys[$v['uid']]['country'] ?? '';
            $v['language'] = XsBigarea::getLanguageName($languages[$v['uid']]['language'] ?? '');
            $v['dateline'] = date('Y-m-d H:i:s', $v['dateline']);
            $v['admin'] = $admins[$v['admin_id']]['user_name'] ?? '';
        }
        return $list;
    }

    public function handleIds($ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        $ids = array_unique($ids);
        return array_values($ids);
    }

    public function delete($id): array
    {
        $ids = $this->handleIds($id);
        return XsstUidWhiteList::deleteByWhere([
            ['id', 'IN', $ids]
        ]);
    }

    public function add(array $params): array
    {
        $user = XsUserProfile::findOne($params['uid']);
        if (empty($user)) {
            return [false, '此APP不存在该用户'];
        }
        $res = XsstUidWhiteList::findOneByWhere([
            ['uid', '=', $params['uid']],
            ['type', '=', $params['white_list_name']]
        ]);
        if ($res) {
            return [false, '该类型白名单已经存在'];
        }
        $bigArea = XsUserBigarea::findOne($params['uid']);
        $data = [
            'uid'        => $params['uid'],
            'admin_id'   => $params['admin_uid'],
            'type'       => $params['white_list_name'],
            'dateline'   => time(),
            'bigarea_id' => $bigArea['bigarea_id'] ?? ''
        ];
        return XsstUidWhiteList::add($data);
    }

    public function importList(array $data): array
    {
        $adminId = Helper::getSystemUid();
        $base = [
            'admin_id' => $adminId,
            'dateline' => time(),
        ];
        // 获取当前管理员下所有用户类白名单
        $types = BmsWhitelistSetting::getWhitelistByType('uid', $adminId);
        $types = array_keys($types);
        $uids = array_column($data, 'uid');
        $bigArea = [];
        foreach (array_chunk($uids, 500) as $uid) {
            $bigAreaList = XsUserBigarea::getUserBigareas($uid);
            $bigArea = $bigArea + $bigAreaList;
        }
        foreach ($data as $v) {
            if ($v['type'] < 1 || !in_array($v['type'], $types) || intval($v['uid']) < 1) {
                continue;
            }
            $res = XsUserProfile::findOneByWhere([
                ['uid', '=', $v['uid']]
            ]);
            if (empty($res)) {
                continue;
            };
            $rec = XsstUidWhiteList::findOneByWhere([
                ['uid', '=', $v['uid']],
                ['type', '=', $v['type']],
            ]);
            if ($rec) {
                continue;
            }
            $data = [
                'uid'        => $v['uid'],
                'type'       => $v['type'],
                'bigarea_id' => $bigArea[$v['uid']] ?? 0
            ];
            XsstUidWhiteList::add(array_merge($data, $base));
        }
        return [true, ''];
    }

    private function getConditions(array $params): array
    {
        if (!isset($params['white_list_name']) && !isset($params['bigarea']) && !isset($params['uid'])) {
            return [];
        }

        // 中东个人房白名单独立出去
        $conditions = [
            ['type', '>', '1']
        ];
        if (!empty($params['uid'])) {
            $uids = str_replace('，', ',', $params['uid']);
            $uids = explode(',', $uids);
            $uids = $this->handleIds($uids);
            $conditions[] = ['uid', 'IN', $uids];
        }
        if (!empty($params['bigarea'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea']];
        }
        // 获取当前管理员下所有用户类白名单
        $types = BmsWhitelistSetting::getWhitelistByType('uid', $params['admin_uid']);
        $types = array_keys($types);
        if (empty($types)) {
            return [];
        }
        if (!empty($params['white_list_name'])) {
            $conditions[] = ['type', '=', $params['white_list_name']];
        } else {
            if (ENV == 'prod' || $params['admin_uid'] != 1403) {
                $conditions[] = ['type', 'IN', $types];
            }
        }

        return $conditions;
    }
}