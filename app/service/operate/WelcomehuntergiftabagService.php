<?php

namespace Imee\Service\Operate;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBatchAgencyHunterGiftBag;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\XsstCouponAreaLog;
use Imee\Models\Xs\XsstCouponAreaManage;
use Imee\Models\Xs\XsstCouponIssued;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class WelcomehuntergiftabagService
{

    public function getConfigList(int $id, array $params): array
    {
        $data = XsBatchAgencyHunterGiftBag::findOne($id);
        if (!$data || $data['type'] == XsBatchAgencyHunterGiftBag::TYPE_CONDITIONS_SEND) {
            return [];
        }

        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 15;

        $start = ($page - 1) * $limit;

        $uid = $params['uid'] ?? '';
        $gbId = $params['gb_id'] ?? '';

        $data = @json_decode($data['config'], true);
        if (!$data) {
            return [];
        }

        if (!$uid && !$gbId) {
            return ['data' => array_slice($data, $start, $limit), 'total' => count($data)];
        }
        $results = [];

        foreach ($data as $item) {
            if ($uid && $item['uid'] != $uid) {
                continue;
            }
            if ($gbId && $item['gb_id'] != $gbId) {
                continue;
            }
            $results[] = $item;
        }
        return ['data' => array_slice($results, $start, $limit), 'total' => count($results)];
    }

    public function getListAndTotal(array $params, int $page, int $limit): array
    {
        $condition = [];
        if (isset($params['type']) && is_numeric($params['type'])) {
            $condition[] = ['type', '=', $params['type']];
        }
        if (isset($params['task_status']) && is_numeric($params['task_status'])) {
            $condition[] = ['task_status', '=', $params['task_status']];
        }
        if (isset($params['status']) && is_numeric($params['status'])) {
            $condition[] = ['status', '=', $params['status']];
        }
        if (!empty($params['id'])) {
            $condition[] = ['id', '=', $params['id']];
        }
        if (!empty($params['creator'])) {
            $condition[] = ['creator', '=', $params['creator']];
        }
        if (!empty($params['start_time'])) {
            $condition[] = ['create_time', '>=', strtotime($params['start_time'])];
        }
        if (!empty($params['end_time'])) {
            $condition[] = ['create_time', '<=', strtotime($params['end_time'])];
        }

        $data = XsBatchAgencyHunterGiftBag::getListAndTotal($condition, '*', 'id desc', $page, $limit);
        if (empty($data['data'])) {
            return $data;
        }

        $service = new WelcomegiftbagService();
        $bags = $service->getGiftBagCondition();
        $bags = array_column($bags, 'label', 'value');
        $bigareas = array_column($service->getBigareaMap(), 'label', 'value');
        $types = array_column($service->getTypeMap(), 'label', 'value');

        $admins = CmsUser::getAdminUserBatch(array_merge(array_column($data['data'], 'creator'), array_column($data['data'], 'auditor')));

        foreach ($data['data'] as &$item) {
            $item['create_time'] = Helper::now($item['create_time']);
            $item['audit_time'] = $item['audit_time'] > 0 ? Helper::now($item['audit_time']) : '';

            if ($item['type'] == XsBatchAgencyHunterGiftBag::TYPE_CONDITIONS_SEND) {
                $config = @json_decode($item['config'], true);
                $arr = [];
                $arr[] = '礼包名称：' . $bags[$config['gb_id']] ?? '';
                $arr[] = '发放天数：' . $config['valid_day'];
                $arr[] = '发放大区：' . $bigareas[$config['bigarea_id']] ?? '';
                $arr[] = '发放类型：' . $types[$config['type']] ?? '';
                $arr[] = '发放数量：' . $config['num'] ?? '';
                $item['send_type'] = implode(PHP_EOL, $arr);
            }

            if ($item['obj_ids']) {
                $item['obj_ids'] = [
                    'type'     => 'manMadeModal',
                    'modal_id' => 'gift_bag_bid',
                    'title'    => '查看公会ID',
                    'value'    => '查看公会ID',
                    'params'   => [
                        'bid' => explode(',', $item['obj_ids'])
                    ],
                ];
            }

            $item['creator'] = $admins[$item['creator']]['user_name'] ?? '';
            $item['auditor'] = $admins[$item['auditor']]['user_name'] ?? '';

            unset($item['config']);

            $item['batch_id'] = $item['id'];
        }

        return $data;
    }

    public function audit(array $params): array
    {
        $id = $params['id'];
        $status = $params['status'];
        if (!is_numeric($id) || !is_numeric($status) || !in_array($status, array_column($this->getAuditStatusMap(), 'value'))) {
            throw new ApiException(ApiException::MSG_ERROR, '参数有误');
        }

        $rec = XsBatchAgencyHunterGiftBag::findOne($id);
        if (($rec['status'] ?? -1) != XsBatchAgencyHunterGiftBag::STATUS_AUDIT_WAIT) {
            throw new ApiException(ApiException::MSG_ERROR, '状态已更新，请刷新页面');
        }

        $params = [
            'id'      => (int)$id,
            'status'  => (int)$status,
            'auditor' => (int)$params['admin_uid'],
        ];

        $data = XsstCouponIssued::getListByWhere([['batch_id', '=', $id], ['audit_status', '=', XsstCouponIssued::AUDIT_WAIT]], 'id,price,bigarea_id');
        if ($data) {
            if ($status == XsBatchAgencyHunterGiftBag::STATUS_AUDIT_PASS) {
                $bigareaAmount = [];
                foreach ($data as $item) {
                    $bigareaId = $item['bigarea_id'];
                    $bigareaAmount[$bigareaId] = $bigareaAmount[$bigareaId] ?? 0;
                    $bigareaAmount[$bigareaId] += $item['price'];
                }

                $couponAreaManage = XsstCouponAreaManage::getListByWhere([['bigarea_id', 'in', array_keys($bigareaAmount)]], 'id,bigarea_id,amount');
                $couponAreaManage = array_column($couponAreaManage, null, 'bigarea_id');

                $bigArea = XsBigarea::getAllNewBigArea();

                foreach ($bigareaAmount as $bigareaId => $price) {
                    if ($price > ($couponAreaManage[$bigareaId]['amount'] ?? 0)) {
                        throw new ApiException(ApiException::MSG_ERROR, '礼包内存在游戏优惠券，当前' . $bigArea[$bigareaId] . '的可用额度不足，请先申请OA流程增加大区账户余额后再进行审核');
                    }
                }
            }
        } else {
            throw new ApiException(ApiException::MSG_ERROR, '没有可审核的优惠券礼包');
        }

        list($res, $msg) = (new PsService())->updateBatchAgencyHunterGiftBag($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $msg);
        }
        $adminId = Helper::getSystemUid();

        if ($status == XsBatchAgencyHunterGiftBag::STATUS_AUDIT_PASS && !empty($bigareaAmount)) {
            foreach ($bigareaAmount as $bigareaId => $price) {
                $updateArr = [
                    'id'       => (int)($couponAreaManage[$bigareaId]['id'] ?? 0),
                    'type'     => XsstCouponAreaLog::TYPE_SEND,
                    'amount'   => $price,
                    'operator' => $adminId,
                ];
                [$result, $msg] = XsstCouponAreaManage::changeCouponAreaBalance($updateArr);

                if (!$result) {
                    $updateArr['err_msg'] = $msg;
                    $params['changeCouponAreaBalance:' . $bigareaId] = $updateArr;
                }
            }
        }

        if ($status == XsBatchAgencyHunterGiftBag::STATUS_AUDIT_PASS) {
            $auditStatus = XsstCouponIssued::AUDIT_SUCCESS;
        } else {
            $auditStatus = XsstCouponIssued::AUDIT_REFUSE;
        }

        $passData = [
            'audit_status' => $auditStatus,
            'audit_id'     => $adminId,
            'audit_at'     => time()
        ];
        retry(3, function () use ($id, $passData) {
            [$res, $msg, $_] = XsstCouponIssued::updateByWhere([['batch_id', '=', $id]], $passData);
            if (!$res) {
                throw new Exception();
            }
            return true;
        }, 100);

        return ['id' => $id, 'after_json' => $params];
    }

    public function getStatusMap()
    {
        return StatusService::formatMap(XsBatchAgencyHunterGiftBag::$statusMap, 'label,value');
    }

    public function getAuditStatusMap()
    {
        $arr = [XsBatchAgencyHunterGiftBag::STATUS_AUDIT_PASS, XsBatchAgencyHunterGiftBag::STATUS_AUDIT_FAIL];
        $map = [];
        foreach ($arr as $status) {
            $map[$status] = XsBatchAgencyHunterGiftBag::$statusMap[$status];
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getTaskStatusMap()
    {
        return StatusService::formatMap(XsBatchAgencyHunterGiftBag::$taskStatusMap, 'label,value');
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(XsBatchAgencyHunterGiftBag::$typeMap, 'label,value');
    }

    public function getCreatorMap()
    {
        $data = XsBatchAgencyHunterGiftBag::getListByWhere([], 'distinct(creator) as creator');
        $users = CmsUser::findByIds(array_column($data, 'creator'), 'user_id,user_name');
        $map = array_column($users, 'user_name', 'user_id');
        return StatusService::formatMap($map, 'label,value');
    }
}