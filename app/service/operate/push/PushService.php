<?php

namespace Imee\Service\Operate\Push;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Config\BbcPushCate;
use Imee\Models\Config\BbcPushContentConfig;
use Imee\Models\Config\BbcTransformPush;
use Imee\Service\Helper;

/**
 * PUSH 相关服务（从老后台迁移）
 */
class PushService
{
    /**
     * 批量塞入队列
     * @param string $cmdStr
     * @param array $paramData
     * @param int $delay
     */
    public static function addPushList(string $cmdStr = 'push_management', array $paramData = [], int $delay = 0)
    {
        NsqClient::publish(NsqConstant::TOPIC_USER_PUSH_MESSAGE, [
            'cmd'  => $cmdStr,
            'data' => $paramData
        ], $delay);
    }

    public function getCateList($params): array
    {
        $conditions = [];
        if (isset($params['deleted'])) {
            $conditions[] = ['deleted', (int)$params['deleted']];
        } else {
            $conditions[] = ['deleted', 0];
        }
        $res = BbcPushCate::getListAndTotal($conditions, '*', 'cid desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (!$res['data']) {
            return $res;
        }
        foreach ($res['data'] as &$val) {
            $user = CmsUser::findOne($val['admin_id']);
            $val['admin_id'] = $user ? $val['admin_id'] . $user['user_name'] : ' - ';
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i', $val['dateline']) : '-';
        }
        return $res;
    }

    public function addCate($params): array
    {
        $exist = BbcPushCate::findOneByWhere([['name', $params['name']], ['deleted', 0]], '*', '', true);
        if ($exist) {
            return [false, '已存在此分类'];
        }
        return BbcPushCate::add([
            'name'     => $params['name'],
            'admin_id' => $params['admin_id'],
            'dateline' => time()
        ]);
    }

    public function deleteCate($cid): array
    {
        $exist = BbcPushCate::findOne($cid, true);
        if (!$exist) {
            return [false, 'cid错误'];
        }
        return BbcPushCate::edit($cid, ['deleted' => 1]);
    }

    public function getContentList($params): array
    {
        $conditions = [];
        if (isset($params['deleted'])) {
            $conditions[] = ['deleted', (int)$params['deleted']];
        } else {
            $conditions[] = ['deleted', 0];
        }
        if (isset($params['status'])) {
            $conditions[] = ['status', (int)$params['status']];
        }
        if (!empty($params['cid'])) {
            $conditions[] = ['cid', (int)$params['cid']];
        }
        if (!empty($params['area'])) {
            $conditions[] = ['area', $params['area']];
        }
        if (!empty($params['title'])) {
            $conditions[] = ['title', 'like', trim($params['title'])];
        }
        $res = BbcPushContentConfig::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (!$res['data']) {
            return $res;
        }
        foreach ($res['data'] as &$val) {
            $user = CmsUser::findOne($val['admin_id']);
            $val['admin_id'] = $user ? $val['admin_id'] . $user['user_name'] : '';
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i', $val['dateline']) : '';
            $val['time'] = $val['time'] ? date('Y-m-d H:i', $val['time']) : '';
            $val['page_url'] = $val['page'];
            $val['image_url'] = Helper::getHeadUrl($val['image']);
        }
        return $res;
    }

    public function addContent($params): bool
    {
        $insert = [
            'app_id'    => APP_ID,
            'cid'       => $params['cid'],
            'content'   => $params['content'],
            'title'     => $params['title'],
            'admin_id'  => $params['admin_id'],
            'dateline'  => time(),
            'sex'       => $params['sex'],
            'page'      => $params['page_url'],
            'is_pic'    => !empty($params['image']) ? 1 : 0,
            'send_type' => $params['send_type'],
            'time'      => !empty($params['time']) ? strtotime($params['time']) : 0,
            'image'     => $params['image'] ?? '',
        ];

        if (!empty($params['time'])) {
            $insert['time'] = strtotime($params['time']);
        }

        foreach ($params['area'] as $area) {
            $insert['area'] = $area;
            BbcPushContentConfig::add($insert);
        }

        return true;
    }

    public function editContent($params): array
    {
        $exist = BbcPushContentConfig::findOne($params['id'], true);
        if (!$exist) {
            return [false, 'id错误'];
        }
        $update = [
            'cid'       => $params['cid'],
            'content'   => $params['content'],
            'title'     => $params['title'],
            'admin_id'  => $params['admin_id'],
            'sex'       => $params['sex'],
            'page'      => $params['page_url'],
            'image'     => $params['image'] ?? '',
            'is_pic'    => !empty($params['image']) ? 1 : 0,
            'send_type' => $params['send_type'],
            'time'      => !empty($params['time']) ? strtotime($params['time']) : 0,
        ];

        return BbcPushContentConfig::edit($params['id'], $update);
    }

    public function deleteContent($id): array
    {
        $exist = BbcPushContentConfig::findOne($id, true);
        if (!$exist) {
            return [false, 'id错误'];
        }
        return BbcPushContentConfig::edit($id, ['deleted' => 1]);
    }

    public function getPushList($params): array
    {
        $conditions = [];
        if (!empty($params['title'])) {
            $conditions[] = ['title', 'like', trim($params['title'])];
        }
        if (!empty($params['push_time_start'])) {
            $conditions[] = ['push_time', '>=', strtotime($params['push_time_start'])];
        }
        if (!empty($params['push_time_end'])) {
            $conditions[] = ['push_time', '<=', strtotime($params['push_time_end'])];
        }

        $res = BbcTransformPush::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (!$res['data']) {
            return $res;
        }
        foreach ($res['data'] as &$val) {
            $user = CmsUser::findOne($val['op_id']);
            $val['op_id'] = $user ? $val['op_id'] . $user['user_name'] : '';
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i:s', $val['dateline']) : '';
            //每天展示时分秒
            $val['push_time'] = $val['push_time'] ? date('Y-m-d H:i:s', $val['push_time']) : '';
            $val['image_url'] = Helper::getHeadUrl($val['image']);
        }
        return $res;
    }

    public function addPush($params): array
    {
        if ($params['package_id'] == 0 && empty($params['push_uid'])) {
            return [false, '请上传UID名单,一行一个UID'];
        }

        $insert = [
            'push_uid'    => $params['push_uid'] ?? '',
            'push_method' => $params['push_method'],
            'package_id'  => $params['package_id'],
            'push_time'   => strtotime($params['push_time']),
            'op_id'       => $params['admin_id'],
            'dateline'    => time(),
            'status'      => 0,
            'page_link'   => $params['page_link'],
            'title'       => $params['title'],
            'subtitle'    => $params['subtitle'],
            'image'       => $params['image'] ?? '',
        ];

        return BbcTransformPush::add($insert);
    }

    public function editPush($params): array
    {
        if ($params['package_id'] == 0 && empty($params['push_uid'])) {
            return [false, '请上传UID名单,一行一个UID'];
        }

        $exist = BbcTransformPush::findOne($params['id'], true);
        if (!$exist) {
            return [false, 'id错误'];
        }
        $update = [
            'push_uid'    => $params['push_uid'] ?? '',
            'push_method' => $params['push_method'],
            'package_id'  => $params['package_id'],
            'push_time'   => strtotime($params['push_time']),
            'op_id'       => $params['admin_id'],
            'dateline'    => time(),
            'page_link'   => $params['page_link'],
            'title'       => $params['title'],
            'subtitle'    => $params['subtitle'],
            'image'       => $params['image'] ?? '',
            'status'      => 0,
        ];

        return BbcTransformPush::edit($params['id'], $update);
    }

    public function statusPush($params): array
    {
        $exist = BbcTransformPush::findOne($params['id'], true);
        if (!$exist) {
            return [false, 'id错误'];
        }
        return BbcTransformPush::edit($params['id'], ['status' => $params['status']]);
    }

    public function deletePush($id): array
    {
        $exist = BbcPushContentConfig::findOne($id, true);
        if (!$exist) {
            return [false, 'id错误'];
        }
        return [BbcTransformPush::deleteById($id), ''];
    }
}