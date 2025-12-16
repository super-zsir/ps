<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBanner;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsBannerExtend;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class BannerService
{
    public function getListAndTotal($params, $order = '', $page = 0, $pageSize = 0): array
    {
        $position = trim($params['position'] ?? '');
        if (empty($position)) {
            return ['data' => [], 'total' => 0];
        }
        $conditions = [['position', '=', $position]];
        $query = [];

        $scene = $params['_scene'] ?? '';
        $videoPositions = ['videoroom', 'videofeed'];
        if ($scene == 'video') {
            if (empty($position)) {
                $conditions[] = ['position', 'in', $videoPositions];
            } elseif (!in_array($position, $videoPositions)) {
                return [];
            }
        } else {
            if (empty($position)) {
                $conditions[] = ['position', 'not in', $videoPositions];
            } elseif (in_array($position, $videoPositions)) {
                return [];
            }
        }

        if (isset($params['display_position']) && is_numeric($params['display_position'])) {
            if ($scene == 'video') {
                if (!empty($position) && $position != 'videofeed') {
                    $conditions[] = ['id', '=', 0];
                } else {
                    $position != 'videofeed' && $conditions[] = ['position', '=', 'videofeed'];
                    $conditions[] = ['display_position', '=', (int)$params['display_position']];
                }
            } else {
                if (!empty($position) && $position != 'live') {
                    $conditions[] = ['id', '=', 0];
                } else {
                    $position != 'live' && $conditions[] = ['position', '=', 'live'];
                    $conditions[] = ['display_position', '=', (int)$params['display_position']];
                }
            }
        }
        if (!empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }
        if (!empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        }
        if (!empty($params['stype'])) {
            $conditions[] = ['stype', '=', $params['stype']];
        }
        if (isset($params['deleted']) && is_numeric($params['deleted'])) {
            $conditions[] = ['deleted', '=', (int)$params['deleted']];
        }
        if (!empty($params['title'])) {
            $conditions[] = ['title', 'llike', $params['title']];
        }
        if (!empty($params['area'])) {
            $conditions[] = ['area', '=', $params['area'] == 'cn' ? 'zh_tw' : $params['area']];
        }
        if (isset($params['status']) && is_numeric($params['status'])) {
            $now = time();
            switch ($params['status']) {
                case 0:
                    $query[] = "deleted >= 1 OR (end_time < {$now} AND end_time <> 0)";
                    break;
                case 1:
                    $query[] = "deleted < 1 AND (begin_time <= {$now} OR begin_time = 0) AND (end_time >= {$now} OR end_time = 0)";
                    break;
                case 2:
                    $conditions[] = ['deleted', '<', 1];
                    $conditions[] = ['begin_time', '>', $now];
                    break;
            }
        }
        if ($query) {
            $list = XsBanner::find([
                'conditions' => implode(' AND ', $query),
                'columns'    => 'id'
            ])->toArray();
            if ($list) {
                $conditions[] = ['id', 'IN', array_column($list, 'id')];
            } else {
                $conditions[] = ['id', '=', 0];
            }
        }
        $result = XsBanner::getListAndTotal($conditions, '*', $order, $page, $pageSize);
        if (!$result['total']) {
            return $result;
        }

        $result['data'] = $this->packResponse($result['data']);

        return $result;
    }

    public function getPreview($params): array
    {
        $position = trim($params['position'] ?? '');
        $displayPosition = $params['display_position'] ?? '';
        $type = trim($params['type'] ?? '');
        $area = trim($params['area'] ?? '');

        $conditions = [];
        $conditions[] = sprintf('position = "%s"', $position);
        $conditions[] = 'deleted = ' . XsBanner::DELETED_NO;
        $conditions[] = sprintf(" (end_time = 0 OR end_time > %d) ", time());
        $conditions[] = sprintf(" (begin_time = 0 OR begin_time < %d) ", time());

        is_numeric($displayPosition) && $conditions[] = 'display_position = ' . $displayPosition;
        $type && $conditions[] = sprintf('type = "%s"', $type);

        if ($area) {
            if ($area == 'cn') {
                $area = 'zh_tw';
            }
            $conditions[] = sprintf('area = "%s"', $area);
        }


        $data = [
            'android' => [],
            'ios'     => [],
        ];
        switch ($position) {
            case 'live':
            case 'videofeed':
                $data = [
                    'android' => [
                        'top'    => [],
                        'middle' => [],
                    ],
                    'ios'     => [
                        'top'    => [],
                        'middle' => [],
                    ],
                ];

                if (!empty($area)) {
                    $fields = 'id, icon, url, role, limit_lv, duration, display_position';
                    $lists = XsBanner::find([
                        'conditions' => implode(' AND ', $conditions),
                        'bind'       => $conditions['bind'] ?? [],
                        'columns'    => $fields,
                        'order'      => 'ordering desc, id asc',
                    ])->toArray();

                    foreach ($lists as $list) {
                        $list['icon'] = Helper::getHeadUrl($list['icon'] ?? '');

                        if (in_array($list['display_position'], [0, 2])) {

                            $data['android']['top'][] = $list;
                            $data['ios']['top'][] = $list;
                        }

                        if (in_array($list['display_position'], [1, 2])) {
                            $data['android']['middle'][] = $list;
                            $data['ios']['middle'][] = $list;
                        }
                    }
                }

                break;
            case 'chatroom':
            case 'videoroom';
                if (!empty($area)) {
                    $fields = 'id, type, icon, url, duration, cover_url, area';
                    $lists = XsBanner::find([
                        'conditions' => implode(' AND ', $conditions),
                        'bind'       => $conditions['bind'] ?? [],
                        'columns'    => $fields,
                        'order'      => 'ordering desc, id asc',
                    ])->toArray();

                    foreach ($lists as $list) {
                        $list['icon'] = Helper::getHeadUrl($list['icon'] ?? '');
                        $data['android'][] = $list;
                        $data['ios'][] = $list;
                    }
                }
                break;
            default:
                break;
        }
        return $data;
    }

    private function packResponse($data)
    {
        $now = time();

        $ids = array_column($data, 'id');
        $noteMap = BmsBannerExtend::BmsBannerExtend($ids);

        foreach ($data as &$v) {
            $v['status'] = 0;
            if ($v['deleted'] < 1) {
                if (($v['begin_time'] > 0 && $now > $v['begin_time']) || $v['begin_time'] < 1) {
                    if (($v['end_time'] > 0 && $now < $v['end_time']) || $v['end_time'] < 1) {
                        $v['status'] = 1;
                    }
                }
                if (($v['begin_time'] > 0 && $now < $v['begin_time'])) {
                    $v['status'] = 2;
                }
            }
            if (!in_array($v['position'], ['live', 'videofeed'])) {
                $v['role'] = '';
            }
            if (!in_array($v['position'], ['live', 'videofeed'])) {
                $v['display_position'] = '';
            }
            // 中文大区特殊处理
            $v['area'] = $v['area'] == 'zh_tw' ? 'cn' : $v['area'];
            $v['begin_time'] = $v["begin_time"] > 0 ? date("Y-m-d H:i", $v["begin_time"]) : "";
            $v['end_time'] = $v["end_time"] > 0 ? date("Y-m-d H:i", $v["end_time"]) : "";
            $v['dateline'] = $v["dateline"] > 0 ? date("Y-m-d H:i", $v["dateline"]) : "";
            $v['icon_url'] = Helper::getHeadUrl($v['icon']);
            $v['note'] = $noteMap[$v['id']]['note'] ?? '';
        }

        return $data;
    }

    private function getPackData($params)
    {
        return '';// 分享功能暂未开放

        if ($params['is_share'] == 0) {
            return '';
        }
        $allExists = (!empty($params['share_title']) && !empty($params['share_desc']) && !empty($params['share_url']));
        if (!$allExists) {
            return '';
        }
        $data = [
            'share'       => 1,
            'share_title' => $params['share_title'],
            'share_desc'  => $params['share_desc'],
            'share_url'   => $params['share_url'],
            'share_icon'  => !empty($params['share_icon']) ? Helper::getHeadUrl($params['share_icon']) : XsBanner::SHARE_ICON,
        ];
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function checkParams(array &$params): void
    {
        $params = Helper::trimParams($params);

        if (!empty($params['url'])) {
            if (strpos($params['url'], ' ') !== false) {
                throw new ApiException(ApiException::MSG_ERROR, '链接当中有空格，请检查');
            }
        }

        $params['begin_time'] = strtotime($params['begin_time'] ?? '');
        $params['end_time'] = strtotime($params['end_time'] ?? '');

        if ($params['begin_time'] > 0 && $params['end_time'] > 0 && $params['end_time'] <= $params['begin_time']) {
            throw new ApiException(ApiException::PARAMS_ERROR, '开始时间不能大于结束时间');
        }

        if (isset($params['role']) && $params['role'] == 3) {
            if ($params['duration'] < 0 || $params['duration'] > 100) {
                throw new ApiException(ApiException::PARAMS_ERROR, '新用户注册时间区间为0-100');
            }
        } else {
            if ($params['duration'] < 3 || $params['duration'] > 15) {
                throw new ApiException(ApiException::PARAMS_ERROR, '显示时间区间为3-15');
            }
        }
        if (!($params['position'] == 'chatroom' && $params['type'] == 'webview')) {
            $params['cover_url'] = '';
        }

        $limitLv = (int)($params['limit_lv'] ?? 0);
        // 语音房和视频房时设置默认值
        if (in_array($params['position'], ['live', 'videofeed']) && $params['role'] != 3) {
            if ($limitLv < 1 || $limitLv > 999) {
                throw new ApiException(ApiException::PARAMS_ERROR, '用户等级不能大于目前最高等级, 用户等级范围1-999');
            }
        }
        if (!in_array($params['position'], ['live', 'videofeed'])) {
            $params['role'] = 0;
            $params['limit_lv'] = 0;
        }

        // 中文大区要转换一下
        if ($params['area'] == 'cn') {
            $params['area'] = 'zh_tw';
        }

        if ($params['position'] == 'videofeed') {
            if (!isset($params['display_position']) || !is_numeric($params['display_position']) || !in_array($params['display_position'], array_keys(XsBanner::$displayPositionMapping))) {
                throw new ApiException(ApiException::PARAMS_ERROR, '请选择展示位置');
            }
        } elseif (($params['_scene'] ?? '') == 'video') {
            $params['display_position'] = 0;
        }
    }

    private function getAllowedUsers($params)
    {
        if ($params['role'] == 3 && empty($params['allowed_users'])) {
            throw new ApiException(ApiException::PARAMS_ERROR, '用户uid不能为空');
        }
        $uidStr = $params['allowed_users'] ?? '';
        if (!$uidStr) {
            return '';
        }
        $uidStr = str_replace('，', ',', $uidStr);
        $allowedUsersArr = explode(',', $uidStr);
        sort($allowedUsersArr);
        $uidLists = XsUserProfile::getListByWhere([['uid', 'IN', $allowedUsersArr]], 'uid');
        if (count($uidLists) != count($allowedUsersArr)) {
            $diffUid = array_diff($allowedUsersArr, array_column($uidLists, 'uid'));
            throw new ApiException(ApiException::PARAMS_ERROR, 'uid中存在无效的uid:' . implode(',', $diffUid));
        }
        return $uidStr;
    }

    public function create($params): array
    {
        $this->checkParams($params);

        $now = time();

        $insert = [
            'app_id'           => APP_ID,
            'type'             => $params['type'],
            'stype'            => $params['stype'],
            'position'         => $params['position'],
            'duration'         => (int)($params['duration'] ?? 0),
            'title'            => $params['title'] ?? '',
            'icon'             => $params['icon'] ?? '',
            'url'              => $params['url'] ?? '',
            'cover_url'        => $params['cover_url'] ?? '',
            'data'             => $this->getPackData($params),
            'deleted'          => (int)$params['deleted'],
            'ordering'         => (int)$params['ordering'],
            'role'             => (int)($params['role'] ?? 0),
            'limit_lv'         => $params['limit_lv'],
            'begin_time'       => intval($params['begin_time'] ?? 0),
            'end_time'         => intval($params['end_time'] ?? 0),
            'dateline'         => $now,
            'allowed_users'    => $this->getAllowedUsers($params),
            'area'             => $params['area'],
            'language'         => $params['language']??'zh_cn',
            'display_position' => $params['display_position'] ?? 0,//feed显示区域
        ];

        [$result, $id] = XsBanner::add($insert);
        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        //更新备注
        BmsBannerExtend::saveRow($id, $params['note']);

        return ['id' => $id, 'after_json' => $insert];
    }

    public function getInfo(int $id): array
    {
        $rec = XsBanner::findOne($id);
        if (empty($rec)) {
            return [];
        }

        //备注
        $ext = BmsBannerExtend::findOne($id);
        $rec['note'] = $ext['note'] ?? '';

        //分享数据解析
        $rec['is_share'] = '0';
        if ($rec['data']) {
            $share = is_json($rec['data'], true);
            if ($share) {
                $rec['is_share'] = '1';
                $rec += $share;
            }
        }

        $rec['begin_time'] = $rec['begin_time'] ? Helper::now($rec['begin_time']) : '';
        $rec['end_time'] = $rec['end_time'] ? Helper::now($rec['end_time']) : '';

        return $rec;
    }

    public function modify($params): array
    {
        $this->checkParams($params);

        $id = $params['id'];
        $rec = XsBanner::findOne($id, true);
        if (!$rec) {
            throw new ApiException(ApiException::MSG_ERROR, 'id错误，当前数据不存在，请确认');
        }

        $update = [
            'app_id'           => APP_ID,
            'type'             => $params['type'],
            'stype'            => $params['stype'],
            'duration'         => (int)($params['duration'] ?? 0),
            'title'            => $params['title'] ?? '',
            'icon'             => $params['icon'] ?? '',
            'url'              => $params['url'] ?? '',
            'limit_lv'         => $params['limit_lv'],
            'cover_url'        => $params['cover_url'] ?? '',
            'data'             => $this->getPackData($params),
            'deleted'          => (int)$params['deleted'],
            'ordering'         => (int)$params['ordering'],
            'role'             => (int)($params['role'] ?? 0),
            'begin_time'       => intval($params['begin_time'] ?? 0),
            'end_time'         => intval($params['end_time'] ?? 0),
            'allowed_users'    => $this->getAllowedUsers($params),
            'area'             => $params['area'],
            'language'         => $params['language']??'zh_cn',
            'display_position' => $params['display_position'] ?? 0,//feed显示区域
        ];

        [$result, $msg] = XsBanner::edit($id, $update);
        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        //更新备注
        BmsBannerExtend::saveRow($id, $params['note']);

        return ['before_json' => $rec, 'after_json' => $update];
    }
}