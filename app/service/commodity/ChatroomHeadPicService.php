<?php

namespace Imee\Service\Commodity;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsChatroomHeadpic;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;

class ChatroomHeadPicService
{
    public function getListAndTotal(array $params): array
    {
        $nowTime = time();
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $uid = str_replace('，', ',', trim(array_get($params, 'uid', '')));
        $uidArr = $uid ? explode(',', $uid) : [];
        $deleted = array_get($params, 'deleted');
        $state = array_get($params, 'state');

        $query = [];
        count($uidArr) && $query[] = ['uid', 'in', $uidArr];
        is_numeric($deleted) && $query[] = ['deleted', '=', $deleted];
        if ($state) {
            switch ($state) {
                case XsChatroomHeadpic::STATE_WAITE:
                    $query[] = ['deleted', '=', XsChatroomHeadpic::DELETE_NO];
                    $query[] = ['times', '>', $nowTime];
                    break;
                case XsChatroomHeadpic::STATE_DOING:
                    $query[] = ['deleted', '=', XsChatroomHeadpic::DELETE_NO];
                    $query[] = ['times', '<', $nowTime];
                    $query[] = ['timee', '>', $nowTime];
                    break;
                case XsChatroomHeadpic::STATE_FINISH:
                    $_ids = XsChatroomHeadpic::find([
                        'conditions' => 'deleted = 1 OR timee < ' . $nowTime,
                        'columns'    => 'id'
                    ])->toArray();
                    if (empty($_ids)) {
                        $query[] = ['id', '=', 0];
                    } else {
                        $query[] = ['id', 'in', array_column($_ids, 'id')];
                    }
                    break;
                default:
                    $query[] = ['id', '=', 0];
                    break;
            }
        }

        $data = XsChatroomHeadpic::getListAndTotal($query, '*', 'id desc', $page, $limit);


        $uidMap = array_column($data['data'], 'uid');
        $uidMap = XsUserProfile::getListByWhere([['uid', 'in', array_values(array_unique($uidMap))]], 'uid, name');
        $uidMap = array_column($uidMap, 'name', 'uid');

        foreach ($data['data'] as &$rec) {
            $times = array_get($rec, 'times', 0);
            $timee = array_get($rec, 'timee', 0);
            $dateline = array_get($rec, 'dateline', 0);

            $rec['icon'] = 'static/effect/' . array_get($rec, 'icon') . '.png';
            $rec['icon_url'] = Helper::getHeadUrl($rec['icon']);
            $rec['uname'] = array_get($uidMap, array_get($rec, 'uid'), ' - ');

            $rec['state'] = XsChatroomHeadpic::STATE_FINISH;
            if (array_get($rec, 'deleted') == XsChatroomHeadpic::DELETE_NO) {
                if ($nowTime < $times) {
                    $rec['state'] = XsChatroomHeadpic::STATE_WAITE;
                }
                if ($nowTime > $times && $nowTime < $timee) {
                    $rec['state'] = XsChatroomHeadpic::STATE_DOING;
                }
            }

            $rec['dateline'] = $dateline ? date('Y-m-d H:i', $dateline) : '-';
            $rec['times'] = $times ? date('Y-m-d H:i', $times) : '';
            $rec['timee'] = $timee > 0 ? date('Y-m-d H:i', $timee) : '';
            $rec['time_all'] = $rec['times'] . ' ~ ' . $rec['timee'];
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec,) = XsChatroomHeadpic::addBatch($data);
        return [$flg, $flg ? ['after_json' => $data] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsChatroomHeadpic::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $update = [];
        $data = $this->validateAndFormatData($params);
        foreach ($data as $k => $v) {
            if ($v != array_get($setting, $k)) {
                $update[$k] = $v;
            }
        }

        if (count($update)) {
            list($flg, $rec) = XsChatroomHeadpic::updateByWhere([['id', '=', $id]], $update);

            return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
        }
        return [false, '数据不需要更新'];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsChatroomHeadpic::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $flg = XsChatroomHeadpic::deleteById($id);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }

    private function validateAndFormatData($params)
    {
        $id = intval(array_get($params, 'id', 0));

        $uid = str_replace('，', ',', trim(array_get($params, 'uid', '')));
        $icon = trim(array_get($params, 'icon', ''));
        $deleted = intval(array_get($params, 'deleted', 0));

        $times = trim(array_get($params, 'times', ''));
        $timee = trim(array_get($params, 'timee', ''));
        $times = $times ? strtotime($times) : 0;
        $timee = $timee ? strtotime($timee) : 0;

        if ($timee && $timee <= $times) {
            throw new ApiException(ApiException::MSG_ERROR, '时间填写错误');
        }

        if (preg_match('/static\/effect\/(\S+)\.png/', $icon, $matches)) {
            $icon = $matches[1] ?? '';
        } else {
            throw new ApiException(ApiException::MSG_ERROR, '必须上传png格式头像框');
        }


        if ($id) {
            return [
                'icon'    => $icon,
                'deleted' => $deleted,
                'times'   => $times,
                'timee'   => $timee,
            ];
        }

        $uidArr = $uid ? explode(',', $uid) : [];
        if (count($uidArr) != count(array_unique($uidArr))) {
            throw new ApiException(ApiException::MSG_ERROR, '有重复的UID');
        }
        $uidAll = XsUserProfile::getListByWhere([['uid', 'in', $uidArr]], 'uid');
        if (count($uidArr) != count($uidAll)) {
            $diffUid = array_diff($uidArr, array_values(array_column($uidAll, 'uid')));
            throw new ApiException(ApiException::MSG_ERROR, 'UID不存在：' . implode(',', $diffUid));
        }

        $data = [];
        foreach ($uidArr as $v) {
            $data[] = [
                'uid'      => $v,
                'icon'     => $icon,
                'deleted'  => $deleted,
                'times'    => $times,
                'timee'    => $timee,
                'dateline' => time(),
            ];
        }

        return $data;
    }

}