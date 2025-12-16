<?php

namespace Imee\Service\Operate;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Nsq\CircleNsq;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCircleTag;

class CircleTagService
{
	use SingletonTrait;

	public function getTagListAndTotal($params, $order = '', $page = 0, $pageSize = 0): array
    {
		$filter = [];
		$rcmd = intval($params['rcmd'] ?? 0);
		$start = trim($params['start'] ?? 0);
		$uid = intval(trim($params['uid'] ?? 0));
		$id = intval(trim($params['id'] ?? 0));
		$name = trim($params['name'] ?? 0);
		$end = trim($params['end'] ?? 0);
		$bigarea_id = intval(trim($params['bigarea_id'] ?? 0));

		if ($rcmd != -1) {
			$filter[] = ['rcmd', '=', $rcmd];
		}

		if ($uid > 0) {
			$filter[] = ['uid', '=', $uid];
		}

		if ($bigarea_id > 0) {
			$filter[] = ['bigarea_id', '=', $bigarea_id];
		}

		if ($id > 0) {
			$filter[] = ['id', '=', $id];
		}

		if (!empty($name)) {
			$filter[] = ['name', 'like', '%' . $name . '%'];
		}

		if (!empty($start)) {
			$start = strtotime($start);
			$filter[] = ['dateline', '>=', $start];
		}

		if (!empty($end)) {
			$end = strtotime($end . ' 23:59:59');
			$filter[] = ['dateline', '<=', $end];
		}
		$result = XsCircleTag::getListAndTotal($filter, '*', $order, $page, $pageSize);
		if (0 == $result['total']) {
			return ['total' => 0, 'data' => []];
		}
		$adminUids = array_values(array_unique(array_column($result['data'], 'uid')));
		$adminList = CmsUser::getBatchCommon($adminUids, ['user_id', 'user_name'], 'user_id');
		foreach ($result['data'] as &$v) {
			$v['uname'] = $adminList[$v['uid']]['user_name'] ?? '-';
			$v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : ' - ';
			$v['rcmd_time'] = $v['rcmd_time'] > 0 ? date('Y-m-d H:i:s', $v['rcmd_time']) : ' - ';
			$v['bigarea_id'] = (int)$v['bigarea_id'];
		}
		return $result;
	}

	public function getMessageConditions(): array
    {
		$data = [];
		$bigareas = XsBigarea::getAreaList();
		foreach ($bigareas as $v) {
			$data['bigareas'][$v['id']] = $v['cn_name'];
		}
		return $data;
	}

	private function checkExist($name, $bigareaId, $id = 0): array
    {
		$condition = [
			['name', '=', $name],
			['bigarea_id', '=', $bigareaId],
		];
		if ($id) {
			$condition[] = ['id', '!=', $id];
		}
		return XsCircleTag::findOneByWhere($condition);
	}

	public function add($params): array
    {
		if ($this->checkExist($params['name'], $params['bigarea_id'])) {
			return ['status' => false, 'msg' => '同一大区下话题不可重复'];
		}
		$insert = [
			'name' => $params['name'],
			'rcmd' => 0,
			'dateline' => time(),
			'uid' => $params['admin_uid'],
			'weight' => $params['weight'] ?? 0,
			'bigarea_id' => $params['bigarea_id'],
		];
		list($result, $cid) = XsCircleTag::add($insert);
		if (!$result) {
			return ['status' => false, 'msg' => '请重试'];
		}

        CircleNsq::publishRsCircleTagEs('write',$cid);
		return ['status' => true, 'msg' => 'success'];
	}

	public function modify($params)
	{
		$info = XsCircleTag::findOne($params['id']);
		if (empty($info)) {
			return ['status' => false, 'msg' => '话题不存在'];
		}
		if ($this->checkExist($info['name'], $params['bigarea_id'], $params['id'])) {
			return ['status' => false, 'msg' => '同一大区下话题不可重复'];
		}
		$updateFlag = false;
		$update = [];
		foreach (['weight', 'bigarea_id', 'rcmd'] as $v) {
			if ($info[$v] != $params[$v]) {
				$updateFlag = true;
				$update[$v] = $params[$v];
			}
		}
		if (!$updateFlag) {
			return ['status' => true, 'msg' => 'success'];
		}
		$update['uid'] = $params['admin_uid'];
		$update['rcmd_time'] = time();
		list($result, $msg) = XsCircleTag::edit($info['id'], $update);
		if (!$result) {
			return ['status' => false, 'msg' => '请重试'];
		}

        $this->modifyPublishRsCircleTagEs($params['rcmd'], $info['id']);
		return ['status' => true, 'msg' => 'success'];
	}

    private function modifyPublishRsCircleTagEs($rcmd, $id): bool
    {
        //禁用 -> delete; 创建，取消禁用 -> write; 其它操作 -> update
        $cmd = 'update';
        if ($rcmd == XsCircleTag::RCMD_DISABLED) {
            $cmd = 'delete';
        }
        if ($rcmd == XsCircleTag::RCMD_DISABLED && $rcmd != XsCircleTag::RCMD_DISABLED) {
            $cmd = 'write';
        }
        return CircleNsq::publishRsCircleTagEs($cmd, $id);
    }
}