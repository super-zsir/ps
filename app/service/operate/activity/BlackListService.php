<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Config\BbcRankWhiteList;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Rpc\PsService;

class BlackListService
{
    public function getListAndTotal($params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $actId = intval(array_get($params, 'act_id', 0));
        $uid = intval(array_get($params, 'uid', 0));


        //复用这张表 button_tag_id 填0 type 填3
        $query = [['button_tag_id', '=', 0], ['type', '=', 3], ['act_id', '=', $actId]];
        $uid && $query[] = ['uid', '=', $uid];

        $data = BbcRankWhiteList::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $uidArr = array_column($data['data'], 'uid');
        $userProfile = XsUserProfile::getListByWhere([['uid', 'in', array_values($uidArr)]], 'uid,name');
        $userProfile = array_column($userProfile, 'name', 'uid');

        $adminArr = array_column($data['data'], 'admin_id');
        $allAdmin = CmsUser::getListByWhere([['user_id', 'in', array_values($adminArr)]], 'user_id,user_name');
        $allAdmin = array_column($allAdmin, 'user_name', 'user_id');

        foreach ($data['data'] as &$rec) {
            $rec['user_name'] = array_get($userProfile, $rec['uid'], '');
            $rec['admin'] = array_get($allAdmin, $rec['admin_id'], '');
            $rec['dateline'] = date('Y-m-d H:i:s', $rec['dateline']);
        }
        return [true, $data];
    }

    public function add($params): array
    {
        $nowTime = time();
        $actId = intval(array_get($params, 'act_id', 0));
        $adminId = intval(array_get($params, 'admin_id', 0));
        $uidStr = str_replace('，', ',', trim(array_get($params, 'uid', 0)));

        $templateConfig = BbcTemplateConfig::findOne($actId);
        if (empty($templateConfig)) {
            return [false, '活动id错误'];
        }

        list($flg, $uidArr) = $this->validateAndFormatUid($uidStr);
        if (!$flg) {
            return [false, $uidArr];
        }

        $existUidArr = BbcRankWhiteList::getListByWhere([
            ['act_id', '=', $actId],
            ['button_tag_id', '=', 0],
            ['type', '=', 3],
            ['uid', 'in', $uidArr],
        ], 'uid');
        $existUidArr = array_values(array_column($existUidArr, 'uid'));

        $uidArr = array_diff($uidArr, $existUidArr);//只添加不存在的
        if (empty($uidArr)) {
            return [false, '所有数据已存在，无需添加'];
        }

        if (array_get($templateConfig, 'status') >= BbcTemplateConfig::STATUS_RELEASE) {
            return (new PsService())->activeAddBlackList(['act_id' => $actId, 'uids' => $uidArr, 'admin_id' => $adminId]);
        } else {
            $data = [];
            foreach ($uidArr as $uid) {
                $data[] = [
                    'act_id'        => $actId,
                    'button_tag_id' => 0,
                    'type'          => 3,
                    'uid'           => $uid,
                    'admin_id'      => $adminId,
                    'dateline'      => $nowTime,
                ];
            }
            return BbcRankWhiteList::addBatch($data);
        }
    }

    public function del($params): array
    {
        $id = intval(array_get($params, 'id', 0));
        $adminId = intval(array_get($params, 'admin_id', 0));
        $model = BbcRankWhiteList::findOne($id);
        if (empty($model)) {
            return [false, '数据错误'];
        }

        $actId = intval(array_get($model, 'act_id', 0));
        $uid = intval(array_get($model, 'uid', 0));

        $templateConfig = BbcTemplateConfig::findOne($actId);
        if (empty($templateConfig)) {
            return [false, '数据错误'];
        }

        if (array_get($templateConfig, 'status') >= BbcTemplateConfig::STATUS_RELEASE) {
            return (new PsService())->activeDelBlackList(['act_id' => $actId, 'uids' => [$uid], 'admin_id' => $adminId]);
        } else {
            $flg = BbcRankWhiteList::deleteById($id);
            return [$flg, $flg ? '' : '删除失败'];
        }
    }

    private function validateAndFormatUid($uidStr): array
    {
        if (!preg_match("/^[0-9\,]+/", $uidStr)) {
            return [false, '数据错误，多个uid，请使用英文逗号分隔'];
        }
        $uidArr = array_values(array_unique(explode(',', $uidStr)));
        $uidArr = array_map('intval', $uidArr);

        $lists = XsUserProfile::getListByWhere([['uid', 'in', $uidArr]], 'uid');

        if (count($uidArr) != count($lists)) {
            $diffUid = array_diff($uidArr, array_values(array_column($lists, 'uid')));
            return [false, '以下uid不存在：' . implode(',', $diffUid)];
        }
        return [true, $uidArr];
    }
}