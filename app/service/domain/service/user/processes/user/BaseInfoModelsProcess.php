<?php

namespace Imee\Service\Domain\Service\User\Processes\User;

use Imee\Models\Xs\XsUserIp;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Lemon\UserVip;
use Imee\Models\Lemon\UserNobility;
use Imee\Models\Xs\XsUserTitleConfigNew;
use Imee\Service\Helper;
use Imee\Service\Domain\Context\User\User\BaseInfosContext;

/**
 * 获取用户基本信息
 */
class BaseInfoModelsProcess
{
    private $context;
    private $returnDataMap = [];
    private $uids;
    public function __construct(BaseInfosContext $context)
    {
        $this->context = $context;
        $this->getBase();
    }

    private function buildWhere()
    {
        $where = [
            'condition' => [],
            'bind' => [],
        ];
        if (!empty($this->context->userIds)) {
            $where['condition'][] = 'uid in({user_ids:array})';
            $where['bind']['user_ids'] = array_values(array_unique($this->context->userIds));
        }


        return $where;
    }

    private function getBase()
    {
        $where = $this->buildWhere();
        $returnData = [];

        if (empty($where['condition'])) {
            return;
        }

        $userModels = XsUserProfile::find([
            'conditions' => implode(' and ', $where['condition']),
            'bind' => $where['bind'],
        ]);


        foreach ($userModels as $userModel) {
            $tmp = [
                'uid' => $userModel->uid,
                'sex' => $userModel->sex,
                'app_id' => $userModel->app_id,
                'icon' => $userModel->icon,
                'user_icon' => Helper::getHeadUrl($userModel->icon),
                'name' => $userModel->name,
                'role' => $userModel->role,
                'pay_room_money' => $userModel->pay_room_money,
                'deleted' => $userModel->deleted,
                'dateline' => $userModel->dateline,

            ];
            $returnData[] = $tmp;
        }

        $this->returnDataMap = array_column($returnData, null, 'uid');
        $this->uids = array_keys($this->returnDataMap);
    }

    public function handle()
    {
        return $this->returnDataMap;
    }

    /**
     * 语言
     */
    public function language()
    {
        if (empty($this->returnDataMap)) {
            return $this;
        }
        $userSettingList = XsUserSettings::find([
            'conditions' => 'uid in ({uids:array})',
            'bind' => [
                'uids' => $this->uids,
            ]
        ])->toArray();
        $userSettingMap = array_column($userSettingList, null, 'uid');
        foreach ($this->returnDataMap as $uid => &$v) {
            $v['language'] = isset($userSettingMap[$uid]) ? $userSettingMap[$uid]['language'] : '';
            $v['language_name'] = Helper::getLanguageName($v['language']);
        }
        return $this;
    }

    /**
     * 大区
     */
    public function bigarea()
    {
        if (empty($this->returnDataMap)) {
            return $this;
        }
        $map = $this->getBigAreaMapByUids($this->uids);
        foreach ($this->returnDataMap as $uid => &$v) {
            $v['big_area_id'] = isset($map[$uid]) ? $map[$uid]['id'] : 0;
            $v['big_area_name'] = isset($map[$uid]) ? $map[$uid]['name'] : '';
            $v['big_area_cn_name'] = XsBigarea::getBigAreaCnName($v['big_area_name']);
        }
        return $this;
    }

    /**
     * VIP等级
     */
    public function vip()
    {
        if (empty($this->returnDataMap)) {
            return $this;
        }
        $userVipInfo = UserVip::find(array(
            'columns' => "uid, level",
            "uid in ({ids:array})",
            "bind" => array("ids" => $this->uids)
        ))->toArray();
        $userVipMap = array_column($userVipInfo, 'level', 'uid');

        foreach ($this->returnDataMap as $uid => &$v) {
            $v['vip'] = isset($userVipMap[$uid]) ? $userVipMap[$uid] : 0;
        }
        return $this;
    }

    /**
     * 爵位
     */
    public function title()
    {
        if (empty($this->returnDataMap)) {
            return $this;
        }
        $userNobility = UserNobility::find(array(
            'columns' => "uid, level",
            "uid in ({ids:array})",
            "bind" => array("ids" => $this->uids)
        ))->toArray();
        $userNobilityMap = array_column($userNobility, 'level', 'uid');
		$titleList = XsUserTitleConfigNew::getAllTitleName();
        foreach ($this->returnDataMap as $uid => &$v) {
            $v['title'] = isset($userNobilityMap[$uid]) ? $userNobilityMap[$uid] : 0;
            $v['title_name'] = isset($titleList[$v['title']]) ? $titleList[$v['title']] : '';
        }
        return $this;
    }

	/**
	 * 注册IP
	 */
	public function registerIp()
	{
		if (empty($this->returnDataMap)) {
			return $this;
		}
		$userIp = XsUserIp::find(array(
			'columns' => "uid, ip",
			"uid in ({ids:array})",
			"bind" => array("ids" => $this->uids)
		))->toArray();
		$userIpMap = array_column($userIp, 'ip', 'uid');
		foreach ($this->returnDataMap as $uid => &$v) {
			$v['register_ip'] = isset($userIpMap[$uid]) ? $userIpMap[$uid] : '';
		}
		return $this;
	}

    private function getBigAreaMapByUids($uids)
    {
        if (empty($uids)) {
            return [];
        }
        $userBigareaList = XsUserBigarea::find([
            'conditions' => "uid in ({uids:array})",
            'bind' => array(
                'uids' => array_values(array_unique($uids)),
            ),
        ])->toArray();
        if (empty($userBigareaList)) {
            return [];
        }
        $bigareaIds = array_column($userBigareaList, 'bigarea_id');

        $bigAreaList = XsBigarea::find([
            'conditions' => "id in ({ids:array})",
            'bind' => array(
                'ids' => array_values(array_unique($bigareaIds)),
            ),
        ])->toArray();

        $bigAreaMap = $bigAreaList ? array_column($bigAreaList, null, 'id') : [];
        $map = [];
        foreach ($userBigareaList as $v) {
            $map[$v['uid']] = isset($bigAreaMap[$v['bigarea_id']]) ?
                $bigAreaMap[$v['bigarea_id']] : '';
        }

        return $map;
    }
}
