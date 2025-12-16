<?php

namespace Imee\Service\Domain\Service\Ka\Processes\User;

use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\Ka\BmsKaUserList;
use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Helper;
use Imee\Service\Lesscode\Traits\Curd\ListTrait;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsUserExp;

class ListProcess
{
    use ListTrait;

    private $isMaster;

    /**
     * @var array 客服ids
     */
    private $kfIds = [];

    /**
     * @var BmsKaUserList
     */
    private $kaUserModel = BmsKaUserList::class;

    /**
     * @var XsstKaOrganization
     */
    private $orgModel = XsstKaOrganization::class;

    /**
     * @var XsstKaOrganizationUser
     */
    private $orgUserModel = XsstKaOrganizationUser::class;

    public function onSetParams($params): void
    {
        $this->kfIds = $this->orgUserModel::getKfIdsByKfId(Helper::getSystemUid());
    }

    public function onGetFilter(&$filter)
    {
        if (isset($filter['org_id']) && $filter['org_id'] > 0) {
            $filter['kf_id'] = ['ins', $this->orgModel::getChildKfIdsByOrgId($filter['org_id'])];

            unset($filter['org_id']);
        }

        if (isset($filter['is_gid']) && $filter['is_gid'] == 1) {
            $filter['big_id'] = ['FIELD', 'uid'];
            unset($filter['is_gid']);
        }
    }

    public function onListFormat(&$item)
    {
        // TODO: Implement onListFormat() method.
    }

    public function onAfterList($list): array
    {
        $uids = array_column((array) $list, 'uid');
        // 查询用户信息
        $users = (array) XsUserProfile::getUserProfileBatch($uids, ['uid', 'deleted', 'name', 'dateline', 'city', 'sex', 'app_id']);
        //财富等级
        $expLvArr = XsUserExp::find([
            'conditions'    => 'uid in({uids:array})',
            'bind'          => ['uids' => $uids],
            'columns'       => 'uid,lv'
        ])->toArray();
        $expLvArr = !empty($expLvArr) ? array_column($expLvArr, 'lv', 'uid') : [];
        foreach ($list as &$item) {
            // 用户昵称
            $item['name'] = isset($users[$item['uid']]) ? $users[$item['uid']]['name'] : '';
            $item['sex'] = isset($users[$item['uid']]) ? $users[$item['uid']]['sex'] : 0;
            $item['app_id'] = isset($users[$item['uid']]) ? $users[$item['uid']]['app_id'] : 0;
            //财富等级
            $item['exp_lv'] = !empty($expLvArr[$item['uid']]) ? $expLvArr[$item['uid']] : 0;

            $item['city']          = isset($users[$item['uid']]) ? $users[$item['uid']]['city'] : '';
            $item['register_time'] = isset($users[$item['uid']]) ? date('Y-m-d H:i:s', $users[$item['uid']]['dateline']) : '';

            $broker = XsBroker::getByBid($item['broker_id']);
            $item['broker_name'] = $broker ? $broker->bname : '';

            // 大小号数量
            $smallNum = count(XsUserProfile::getGidSmallByUid($item['uid']));
            $smallNum = $smallNum > 0 ? $smallNum - 1 : $smallNum;

            $item['small_num'] = [
                'title'    => $smallNum,
                'value'    => $smallNum,
                'type'     => 'manMadeModal',
                'modal_id' => 'table_modal',
                'params'   => [
                    'title' => '大小号列表',
                    'uid'   => $item['uid'],
                    'gid'   => $item['gid'],
                    'guid'  => 'usersmall',
                ],
            ];

            // 用户封禁状态
            $deleted         = isset($users[$item['uid']]) ? $users[$item['uid']]['deleted'] : 0;
            $item['deleted'] = $deleted >= 2 ? 1 : 2;

            // 部门id
            $item['org_id'] = $this->orgUserModel::getOrgIdByKfId($item['kf_id']);
        }

        return $list;
    }
}
