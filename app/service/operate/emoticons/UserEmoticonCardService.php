<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserEmoticonCardRecord;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class UserEmoticonCardService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params, int $page, int $limit): array
    {
        $conditions = [];
        
        if (!empty($params['uid']) && is_numeric($params['uid'])) {
            $conditions[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['user_big_area_id']) && is_numeric($params['user_big_area_id'])) {
            $conditions[] = ['user_big_area_id', '=', $params['user_big_area_id']];
        }
        if (!empty($params['dateline_sdate'])) {
            $conditions[] = ['update_time', '>=', strtotime($params['dateline_sdate'])];
        }
        if (!empty($params['dateline_edate'])) {
            $conditions[] = ['update_time', '<=', strtotime($params['dateline_edate'])];
        }
        if (isset($params['status'])) {
            $conditions[] = ['status', '=', $params['status']];
        }

        $list = XsUserEmoticonCardRecord::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {

            $item['can_expire'] = 1;
            if (in_array($item['status'], [3, 6]) || $item['expired_time'] <= time()) {
                $item['can_expire'] = 0;
            }
            
            $item['create_time'] = Helper::now($item['create_time']);
            $item['update_time'] = $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';
            $item['expired_time'] = $item['expired_time'] > 0 ? Helper::now($item['expired_time']) : '';
            $item['show_expired_time'] = $item['show_expired_time'] > 0 ? Helper::now($item['show_expired_time']) : '';

            foreach (['can_send', 'user_big_area_id', 'effective_days', 'status', 'source', 'reject_type'] as $field) {
                $item[$field] = (string)($item[$field] ?? '');
            }

            $item['img_url_full'] = Helper::getHeadUrl($item['img_url']); 
            $item['img_url_first'] = $item['img_url_full'] ? $item['img_url_full'] . '?x-oss-process=image/auto-orient,1/format,png' : '';

        }

        return $list;
    }

    public function import(array $list)
    {
        foreach ($list as $index => &$item) {
            $rowNum = $index + 1;
            
            if (empty($item['uid']) || !is_numeric($item['uid'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：uid不能为空");
            }
            if (empty($item['num']) || !is_numeric($item['num']) || $item['num'] < 1) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：数量不对");
            }
            if (empty($item['effective_days']) || !is_numeric($item['effective_days']) || !in_array($item['effective_days'], array_keys(XsUserEmoticonCardRecord::$effectiveDays))) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：单张生效时间(天)不对");
            }
            $expireTime = $item['expired_time'] ?? '';

            if ($expireTime) {
                $expireTime = strtotime($expireTime);
            }
            if (!$expireTime || $expireTime <= time()) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：过期时间不对");
            }
            $item['expired_time'] = $expireTime;

            if (!isset($item['can_send'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：是否发放不对");
            }
            if (is_numeric($item['can_send'])) {
                if (!is_numeric($item['can_send']) || !in_array($item['can_send'], array_keys(XsUserEmoticonCardRecord::$canSend))) {
                    throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：是否发放不对");
                }
            } else {
                $item['can_send'] = trim($item['can_send']);
                if ($item['can_send'] != '是' && $item['can_send'] != '否') {
                    throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：是否发放不对");
                }
                $item['can_send'] = $item['can_send'] == '是' ? 1 : 0;
            }
            if (empty($item['reason'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：发放理由不能为空");
            }

            foreach (['uid', 'num', 'effective_days', 'expired_time', 'can_send'] as $field) {
                $item[$field] = (int)$item[$field];
            }
        }

        $uids = array_unique(array_column($list, 'uid'));
        if (count($uids) != count($list)) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID不能重复');
        }

        $users = XsUserProfile::findByIds($uids, 'uid');
        $uids = array_diff($uids, array_column($users, 'uid'));
        if ($uids) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID不存在：' . implode(',', $uids));
        }

        $post = [
            'info_list' => $list,
            'operator' => Helper::getSystemUserInfo()['user_name'],
        ];

        list($res, $msg) = $this->rpcService->sendUserEmoticons($post);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => 0, 'after_json' => $list];
    }

    public function send(array $params): array
    {
        $uids = empty($params['uid']) ? [] : explode(',', str_replace('，', ',', trim($params['uid'])));
        $uids = array_filter($uids, function($uid) {
            return is_numeric($uid) && $uid > 0;
        });
        $uids = array_values($uids);
        if (!$uids || !($users = XsUserProfile::findByIds($uids, 'uid'))) {
            throw new ApiException(ApiException::MSG_ERROR, '无此UID');
        }
        if ($diff = array_diff($uids, array_column($users, 'uid'))) {
            throw new ApiException(ApiException::MSG_ERROR, '无此UID:' . implode(',', $diff));
        }
        $data = [];
        $row = [
            'num' => (int)$params['num'],
            'effective_days' => (int)$params['effective_days'],
            'expired_time' => strtotime($params['expired_time']),
            'can_send' => (int)$params['can_send'],
            'reason' => trim($params['reason']),
        ];

        foreach ($uids as $uid) {
            $data[] = array_merge($row, ['uid' => (int)$uid]);
        }

        $post = [
            'info_list' => $data,
            'operator' => Helper::getSystemUserInfo()['user_name'],
        ];

        list($res, $msg) = $this->rpcService->sendUserEmoticons($post);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => 0, 'after_json' => $data];
    }

    public function expire(array $params): array
    {
        $id = $params['id'] ?? 0;
        $rec = XsUserEmoticonCardRecord::findOne($id);
        if (!$rec) {
            throw new ApiException(ApiException::MSG_ERROR, '该记录不存在');
        }

        if (in_array($rec['status'], [3, 6]) || $rec['expired_time'] <= time()) {
            throw new ApiException(ApiException::MSG_ERROR, '审核通过/已过期/已经失效，这三种状态下不允许操作');
        }

        $data = [
            'id' => (int)$id,
            'operator' => Helper::getSystemUserInfo()['user_name'],
        ];

        list($res, $msg) = $this->rpcService->expireUserEmoticons($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function audit(array $params): array
    {
        $id = $params['id'] ?? 0;
        $rec = XsUserEmoticonCardRecord::findOne($id);
        if (!$rec) {
            throw new ApiException(ApiException::MSG_ERROR, '该记录不存在');
        }

        if ($rec['status'] != 2) {
            throw new ApiException(ApiException::MSG_ERROR, '该记录状态已修改');
        }

        $data = [
            'id' => (int)$id,
            'pass' => $params['pass'] == 1,
            'reject_type' => intval($params['reject_type'] ?? 0),
            'operator' => Helper::getSystemUserInfo()['user_name'],
        ];

        list($res, $msg) = $this->rpcService->auditUserEmoticons($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function getEffectiveDays()
    {
        return StatusService::formatMap(XsUserEmoticonCardRecord::$effectiveDays);
    }

    public function getCanSend()
    {
        return StatusService::formatMap(XsUserEmoticonCardRecord::$canSend);
    }

    public function getStatus()
    {
        return StatusService::formatMap(XsUserEmoticonCardRecord::$status);
    }

    public function getSource()
    {
        return StatusService::formatMap(XsUserEmoticonCardRecord::$source);
    }

    public function getRejectType()
    {
        return StatusService::formatMap(XsUserEmoticonCardRecord::$rejectType);
    }
    
}