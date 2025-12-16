<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsEmoticonsReward;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class EmoticonsRewardService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $list = $this->rpcService->getEmoticonsRewardList($this->getConditions($params));

        $emoticons = XsEmoticons::getBatchCommon(array_column($list['data'], 'emoticons_id'), ['id', 'identity'], 'id');

        foreach ($list['data'] as &$item) {
            $item['create_time'] = is_numeric($item['create_time'] ?? '') ? Helper::now($item['create_time']) : '';
            $item['update_time'] = is_numeric($item['update_time'] ?? '') && $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';
            $identity = $emoticons[$item['emoticons_id']]['identity'] ?? 0;
            $item['identity'] = XsEmoticons::$identityMap[$identity] ?? '';

            $item['status'] = $item['status'] ? '1' : '2';
        }
        return $list;
    }

    public function getSearchList(array $params): array
    {
        $list = $this->rpcService->getEmoticonsRewardSearchList($this->getConditions($params));

        foreach ($list['data'] as &$item) {
            $item['identity'] = XsEmoticons::$identityMap[$item['identity']] ?? '';
            $item['status'] = $item['status'] ? '1' : '2';
        }
        return $list;
    }

    public function reduce(array $params): array
    {
        $data = [
            'uid'          => (int)$params['uid'],
            'emoticons_id' => (int)$params['emoticons_id'],
            'reduce_time'  => (int)$params['reduce_time'],
            'operator'     => Helper::getSystemUserInfo()['user_name'] ?? '',
        ];

        [$result, $msg] = $this->rpcService->reduceEmoticonsReward($data);

        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        usleep(500 * 1000);

        return ['id' => $params['id'], 'after_json' => $data];
    }

    public function create(array $params): array
    {
        $this->validate($params);

        $rec = [
            'emoticons_id' => (int)$params['emoticons_id'],
            'reward_time'  => (int)$params['reward_time'],
            'comment'      => $params['comment'] ?? '',
            'operator'     => Helper::getAdminName($params['admin_id']),
        ];

        $data = [];
        foreach ($params['uids'] as $uid) {
            $data[] = ['uid' => (int)$uid] + $rec;
        }

        [$result, $msg, $ids] = $this->rpcService->addEmoticonsReward($data);

        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        unset($rec['operator']);

        return ['id' => $ids, 'after_json' => $rec];
    }

    public function import(array $data)
    {
        $this->validateImport($data);

        [$result, $msg, $ids] = $this->rpcService->addEmoticonsReward($data);

        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        $data['ids'] = $ids;
        return ['id' => $ids[0], 'after_json' => $data];
    }

    private function validateImport(array &$data): void
    {
        $data = Helper::trimParams($data);

        if (empty($data) || !is_array($data)) {
            throw new ApiException(ApiException::MSG_ERROR, '请上传数据');
        }
        $data = array_filter($data, function ($item) {
            return $item['uid'] != XsEmoticonsReward::$uploadFields['uid'];
        });

        if (empty($data)) {
            throw new ApiException(ApiException::MSG_ERROR, '请上传数据');
        }
        $data = array_values($data);

        if (count($data) > 200) {
            throw new ApiException(ApiException::MSG_ERROR, '一次最多只能发放200条');
        }

        $emoticons = XsEmoticons::getListByWhere([['id', 'in', array_column($data, 'emoticons_id')], ['status', '=', XsEmoticons::LISTED_STATUS], ['identity', 'in', [XsEmoticons::EMOTICONS_IDENTITY_SELL, XsEmoticons::EMOTICONS_IDENTITY_ACTIVE]]], 'id,bigarea_id');
        if ($diff = array_diff(array_column($data, 'emoticons_id'), array_column($emoticons, 'id'))) {
            $diff = (array_chunk($diff, 10))[0];
            throw new ApiException(ApiException::MSG_ERROR, '配置ID必需是 活动奖励、限时购买这两种类型的配置：' . implode(',', $diff));
        }

        $emoticons = array_column($emoticons, 'bigarea_id', 'id');

        $uIds = array_values(array_unique(array_filter(array_column($data, 'uid'), 'intval')));
        $users = XsUserProfile::findByIds($uIds, 'uid');

        if (count($uIds) != count($users) && $diff = array_diff($uIds, array_column($users, 'uid'))) {
            $diff = (array_chunk($diff, 10))[0];
            throw new ApiException(ApiException::MSG_ERROR, 'UID不存在：' . implode(',', $diff));
        }

        $userAreas = XsUserBigarea::findByIds($uIds, 'uid,bigarea_id');

        if (count($uIds) != count($userAreas) && $diff = array_diff($uIds, array_column($userAreas, 'uid'))) {
            $diff = (array_chunk($diff, 10))[0];
            throw new ApiException(ApiException::MSG_ERROR, 'UID不存在大区：' . implode(',', $diff));
        }
        $userAreas = array_column($userAreas, 'bigarea_id', 'uid');

        $operator = Helper::getSystemUserInfo()['user_name'];

        $filter = [];
        foreach ($data as &$item) {
            if (empty($item['uid']) || !is_numeric($item['uid']) || $item['uid'] < 1) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写正确的UID');
            }
            if (empty($item['emoticons_id']) || !is_numeric($item['emoticons_id']) || $item['emoticons_id'] < 1) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写正确的配置ID');
            }
            if (empty($item['reward_time']) || !is_numeric($item['reward_time']) || $item['reward_time'] < 1) {
                throw new ApiException(ApiException::MSG_ERROR, '请填写正确的有效时长');
            }

            if ($emoticons[$item['emoticons_id']] != $userAreas[$item['uid']]) {
                throw new ApiException(ApiException::MSG_ERROR, "用户大区与配置大区不一致：{$item['uid']} {$item['emoticons_id']} {$item['reward_time']}");
            }

            $key = $item['uid'] . '_' . $item['emoticons_id'];
            if (isset($filter[$key])) {
                throw new ApiException(ApiException::MSG_ERROR, "记录重复：{$item['uid']} {$item['emoticons_id']} {$item['reward_time']}");
            }
            $filter[$key] = 1;

            $item = [
                'uid'          => (int)$item['uid'],
                'emoticons_id' => (int)$item['emoticons_id'],
                'reward_time'  => (int)$item['reward_time'],
                'comment'      => '',
                'operator'     => $operator,
            ];
        }
    }

    private function validate(array &$params): void
    {
        $params = Helper::trimParams($params);

        $emoticon = XsEmoticons::findOne($params['emoticons_id']);
        if (!$emoticon || $emoticon['status'] != XsEmoticons::LISTED_STATUS || !in_array($emoticon['identity'], [XsEmoticons::EMOTICONS_IDENTITY_SELL, XsEmoticons::EMOTICONS_IDENTITY_ACTIVE])) {
            throw new ApiException(ApiException::MSG_ERROR, '配置ID数据有更新，请重新选择');
        }

        $uIds = is_array($params['uids']) ? $params['uids'] : array_unique(explode(',', $params['uids']));
        $filter = array_filter($uIds, function ($uid) {
            return is_numeric($uid) && $uid > 0;
        });
        if ($diff = array_diff($uIds, $filter)) {
            $diff = (array_chunk($diff, 10))[0];
            throw new ApiException(ApiException::MSG_ERROR, '下发人群UID填写有误：' . implode(',', $diff));
        }

        if (count($uIds) > 500) {
            throw new ApiException(ApiException::MSG_ERROR, '一次最多只能发放500人');
        }

        foreach (array_chunk($uIds, 1000) as $uidArr) {
            $users = XsUserProfile::findByIds($uidArr, 'uid');
            if ($diff = array_diff($uidArr, array_column($users, 'uid'))) {
                $diff = (array_chunk($diff, 10))[0];
                throw new ApiException(ApiException::MSG_ERROR, '下发人群UID不存在：' . implode(',', $diff));
            }

            $users = XsUserBigarea::getListByWhere([['uid', 'in', $uidArr], ['bigarea_id', '=', $emoticon['bigarea_id']]], 'uid');
            if ($diff = array_diff($uidArr, array_column($users, 'uid'))) {
                $diff = (array_chunk($diff, 10))[0];
                throw new ApiException(ApiException::MSG_ERROR, '用户UID和配置id所对应的大区不在一个大区：' . implode(',', $diff));
            }
        }

        $params['uids'] = $uIds;
    }

    private function getConditions(array $params): array
    {
        $conditions = [
            'page'        => (int)($params['page'] ?? 1),
            'page_size'   => (int)($params['limit'] ?? 15),
        ];
        if (!empty($params['reward_type'])) {
            $conditions['reward_type'] = $params['reward_type'];
        }
        if (!empty($params['uid'])) {
            $conditions['uid'] = (int)$params['uid'];
        }
        if (!empty($params['emoticons_id'])) {
            $conditions['emoticons_id'] = (int)$params['emoticons_id'];
        }

        if (isset($params['status']) && is_numeric($params['status'])) {
            $conditions['status'] = (int)$params['status'];
        }

        if (!empty($params['dateline_sdate'])) {
            $conditions['start_time'] = strtotime($params['dateline_sdate']);
        }

        if (!empty($params['dateline_edate'])) {
            $conditions['end_time'] = strtotime($params['dateline_edate']);
        }

        return $conditions;
    }

    public function getEmoticonsMap(): array
    {
        $map = XsEmoticons::getListByWhere([['status', '=', XsEmoticons::LISTED_STATUS], ['identity', 'in', [XsEmoticons::EMOTICONS_IDENTITY_SELL, XsEmoticons::EMOTICONS_IDENTITY_ACTIVE]]], 'id,identity');
        if ($map) {
            foreach ($map as &$item) {
                $item['identity'] = $item['id'] . ' - 【' . (XsEmoticons::$identityMap[$item['identity']] ?? $item['identity']) . '】';
            }
        }

        $map = array_column($map, 'identity', 'id');
        return StatusService::formatMap($map, 'label,value');
    }
}