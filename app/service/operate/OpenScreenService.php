<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsOpenScreen;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class OpenScreenService
{
    /** @var PsService $rpcService */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = XsOpenScreen::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $logs = BmsOperateLog::getFirstLogList('openscreen', array_column($list['data'], 'id'));
        $now = time();
        foreach ($list['data'] as &$item) {
            if ($item['start_time'] > $now && $item['end_time'] > $now) {
                $item['status'] = XsOpenScreen::NO_EFFECT;
            } else if ($item['start_time'] < $now && $item['end_time'] > $now) {
                $item['status'] = XsOpenScreen::IN_EFFECT;
            } else if ($item['end_time'] <= $now) {
                $item['status'] = XsOpenScreen::LOSE_EFFECT;
            }
            $item['visible_crowd'] = (string) $item['visible_crowd'];
            $item['start_time'] = Helper::now($item['start_time']);
            $item['end_time'] = Helper::now($item['end_time']);
            $item['img_url'] = Helper::getHeadUrl($item['img']);
            $item['operator'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['update_time'] = $item['dateline'];
            $item['dateline'] = Helper::now($item['dateline']);
        }
        // 按照生效状态(status)和操作时间(dateline)排序 未生效>生效中>已失效；操作时间越近，排序越靠前。
        $statusSort = array_column($list['data'], 'status');
        $timeSort = array_column($list['data'], 'update_time');
        @array_multisort($statusSort, SORT_ASC, $timeSort, SORT_DESC, $list['data']);
        return $list;
    }

    public function add(array $params): array
    {
        $this->valid($params);
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->createOpenScreen($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return [
            'id'         => $msg,
            'after_json' => $data
        ];
    }

    public function edit(array $params): array
    {
        $info = XsOpenScreen::findOne($params['id']);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '编辑的数据不存在');
        }
        $this->valid($params);
        $data = $this->formatData($params);
        list($res, $msg) = $this->rpcService->editOpenScreen($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return [
            'id'          => $params['id'],
            'after_json'  => $data,
            'before_json' => $info
        ];
    }

    public function disable(int $id): array
    {
        $info = XsOpenScreen::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '禁用的数据不存在');
        }
        list($res, $msg) = $this->rpcService->disableOpenScreen($id);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return [
            'id'          => $id,
            'after_json'  => [
                'end_time' => date('Y-m-d H:i:s', time())
            ],
            'before_json' => [
                'end_time' => Helper::now($info['end_time'])
            ]
        ];
    }

    private function formatData(array $params)
    {
        $data = [
            'name'           => $params['name'],
            'img'            => $params['img'],
            'visible_crowd'  => (int)$params['visible_crowd'],
            'jump_addr_type' => (int)$params['jump_addr_type'],
            'jump_addr'      => $params['jump_addr'],
            'big_area_id'    => (int)($params['big_area_id'] ?? 0),
            'weight'         => (int)$params['weight'],
            'start_time'     => $params['start_time'],
            'end_time'       => $params['end_time'],
            'dateline'       => time(),
        ];

        if (!empty($params['id'])) {
            $data['id'] = (int)$params['id'];
            unset($data['dateline']);
        }

        if ($data['visible_crowd'] == XsOpenScreen::VISIBLE_CROWD_SPECIFIC) {
            $data['crowd_uid_list'] = $params['crowd_uid_list'];
            $data['big_area_id'] = 0;
        }

        return $data;
    }

    private function valid(array &$params): void
    {
        // 开屏页结束展示的时间，不能小于或等于展示开始时间；且需大于当前时间。
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);
        if (($params['start_time'] >= $params['end_time']) || $params['end_time'] < time()) {
            throw new ApiException(ApiException::MSG_ERROR, '展示开始时间不能大于结束时间且结束时间需大于当前时间');
        }
        // 特定用户下需验证uid合法性
        if ($params['visible_crowd'] == XsOpenScreen::VISIBLE_CROWD_SPECIFIC) {
            $crowdUid = Helper::formatIdString($params['crowd_uid'] ?? '');
            $crowdUidFile = Helper::formatIdString($params['crowd_uid_file'] ?? '');
            if (!empty($crowdUid) && !empty($crowdUidFile)) {
                throw new ApiException(ApiException::MSG_ERROR, '特定uid输入和上传互斥');
            }
            $uids = $crowdUid ?: $crowdUidFile;
            if (empty($uids)) {
                throw new ApiException(ApiException::MSG_ERROR, '特定用户下，特定uid必填');
            }
            $params['crowd_uid_list'] = $this->checkUid($uids);
        }
        // 根据跳转类型验证跳转内容的uid和rid是否存在
        if ($params['jump_addr_type'] == XsOpenScreen::JUMP_TYPE_HOME_PAGE) {
            $user = XsUserProfile::findOne($params['jump_addr']);
            if (!$user) {
                throw new ApiException(ApiException::MSG_ERROR, '跳转地址为个人主页下：输入的跳转内容的UID：【' . $params['jump_addr'] . '】不存在，请重新输入');
            }
        } else if ($params['jump_addr_type'] == XsOpenScreen::JUMP_TYPE_ROOM) {
            $room = XsChatroom::findOne($params['jump_addr']);
            if (!$room) {
                throw new ApiException(ApiException::MSG_ERROR, '跳转地址为房间下：输入错误的房间id：【' . $params['jump_addr'] . '】不存在，请重新输入');
            }
        }
        // 权重值不可和未生效和生效中的配置重复
        $info = XsOpenScreen::getInfoByWeight($params['weight'], intval($params['id'] ?? 0));
        if ($info) {
            throw new ApiException(ApiException::MSG_ERROR, '权重数字重复，请重新输入');
        }
    }

    private function checkUid($uids)
    {
        if (!is_array($uids)) {
            $uids = Helper::formatIdString($uids);
        }

        $errorUid = XsUserProfile::checkUid($uids);

        if ($errorUid && is_array($errorUid)) {
            throw new ApiException(ApiException::MSG_ERROR, implode(',', $errorUid) . '以上UID错误');
        }

        return $uids;
    }

    private function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['show_time_sdate']) && !empty($params['show_time_sdate'])
            && isset($params['show_time_edate']) && !empty($params['show_time_edate'])) {
            $conditions[] = ['start_time', '>', strtotime($params['show_time_sdate'])];
            $conditions[] = ['end_time', '<', strtotime($params['show_time_edate'])];
        }
        // 存在展示时间筛选且未生效时取两者最大的开始时间
        // 存在展示时间筛选且已失效时取两者最小的结束时间
        if (isset($params['status']) && !empty($params['status'])) {
            $time = time();
            if ($params['status'] == XsOpenScreen::NO_EFFECT) {
                if (!empty($conditions) && ($conditions[0][2] > $time)) {
                    $time = $conditions[0][2];
                }
                $conditions[0] = ['start_time', '>', $time];
            } else if ($params['status'] == XsOpenScreen::IN_EFFECT) {
                $conditions[] = ['start_time', '<', $time];
                $conditions[] = ['end_time', '>', $time];
            } else if ($params['status'] == XsOpenScreen::LOSE_EFFECT) {
                if (!empty($conditions) && ($conditions[1][2] < $time)) {
                    $time = $conditions[1][2];
                }
                $conditions[1] = ['end_time', '<', $time];
            }
        }
        if (isset($params['big_area_id']) && !empty($params['big_area_id'])) {
            $conditions[] = ['big_area_id', '=', $params['big_area_id']];
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ['name', 'like', trim($params['name'])];
        }

        if (isset($params['jump_addr_type']) && !empty($params['jump_addr_type'])) {
            $conditions[] = ['jump_addr_type', '=', trim($params['jump_addr_type'])];
        }

        if (isset($params['jump_addr']) && !empty($params['jump_addr'])) {
            $conditions[] = ['jump_addr', 'like', trim($params['jump_addr'])];
        }

        return $conditions;
    }
}