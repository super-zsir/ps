<?php

namespace Imee\Service\Operate\Commodity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xs\BaseModel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCommoditySend;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstCommoditySendExtend;
use Imee\Service\Helper;
use Imee\Service\Operate\Reward\RewardSendPlatformService;
use Imee\Service\Operate\Reward\RewardSendService;
use Imee\Service\Rpc\PsService;
use Phalcon\Di;

class CommoditySendService
{
    /**
     * 物品下发
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function send(array $params): array
    {
        $uidList = $params['uid_list'];
        $cid = $params['cid'];
        $num = $params['num'];
        $mark = $params['remark'];
        $source = $params['source'];
        $adminId = $params['admin_id'];
        $now = time();
        $this->validSend($uidList, $cid, $adminId);
        $commodity = XsCommodityAdmin::getInfo($cid);
        $baseData = [
            'aid'      => $commodity['cid'],
            'cid'      => $commodity['ocid'],
            'app_id'   => APP_ID,
            'num'      => $num,
            'dateline' => $now,
            'admin'    => $adminId,
            'state'    => 0,
            'mark'     => $mark,
        ];
        // 确保统一批次用户发放成功
        $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
        $conn->begin();
        try {
            $sidArr = [];
            foreach ($uidList as $uid) {
                $data = array_merge($baseData, ['uid' => $uid]);
                list($res, $id) = XsCommoditySend::add($data);
                if (!$res) {
                    throw new ApiException(ApiException::MSG_ERROR, '添加失败，原因：' . $id);
                }
                $sidArr[] = $id;
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ApiException(ApiException::MSG_ERROR, $e->getMessage());
        }

        $extendBaseData = [
            'source' => $source,
            'admin_id' => $adminId,
            'dateline' => $now,
        ];
        $extendBatchData = [];
        foreach ($sidArr as $sid) {
            $extendBatchData[] = array_merge($extendBaseData, ['sid' => $sid]);
        }
        XsstCommoditySendExtend::addBatch($extendBatchData);
        return ['id' => $sidArr, 'after_json' => array_merge($baseData, ['uid_list' => $uidList])];
    }

    public function validSend($uidList, $cid, $adminId): void
    {
        // 验证物品基础信息
        (new RewardSendPlatformService())->validCommodityReward(['rewardItem' => ['id' => $cid]]);
        // 验证用户UID
        $errorUidList = XsUserProfile::checkUid($uidList);
        if ($errorUidList) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('用户：%s不存在，请修改后重试', implode(',', array_unique($errorUidList))));
        }
        $this->checkAdminUserAuth($uidList, $adminId);
    }

    /**
     * 验证后台管理员是否具有该大区下发权限
     * @param $uidArr
     * @param $adminId
     * @return void
     * @throws ApiException
     */
    public function checkAdminUserAuth($uidArr, $adminId): void
    {
        $userBigAreaList = XsUserBigarea::getUserBigareasChunk($uidArr);
        $userBigAreaList = array_values(array_unique($userBigAreaList));
        $bigAreaList = XsBigarea::getAllBigAreaCode();
        $bigAreaMap = XsBigarea::getAllNewBigArea();
        $admin = CmsUser::findOne($adminId);
        $adminUserBigArea = array_map(function($item) use ($bigAreaList) {
            return array_search($item, $bigAreaList);
        }, explode(',', $admin['bigarea']));
        $diffBigArea = array_diff($userBigAreaList, $adminUserBigArea);
        $bigAreaName = array_map(function($item) use ($bigAreaMap) {
            return $bigAreaMap[$item];
        }, $diffBigArea);
        if ($bigAreaName) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('你无权发放物品给%s的用户', implode(',', $bigAreaName)));
        }
    }

    public function audit(array $params): void
    {
        $ids = Helper::formatIdString($params['ids']);
        $state = (int) $params['state'];
        $adminId = $params['admin_id'];
        $now = time();
        $reqList = $this->validAudit($ids, $state, $adminId);
        $flg = true;
        $rec = '';
        if ($state == XsCommoditySend::STATE_PASS && $reqList) {
            $data = [
                'list' => $reqList,
                'operate' => Helper::getAdminName($adminId),
                'reason' => ''
            ];
            list($flg, $rec)= (new PsService())->sendCommodity($data);
        }
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, '物品接口下发失败，失败原因：' . $rec);
        }

        $update = [
            'state'       => $state,
            'verifyadmin' => $adminId,
            'verifytime'  => $now
        ];
        list($res, $msg, $_) = XsCommoditySend::updateByWhere([['id', 'in', $ids]], $update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '审核失败，失败原因：' . $msg);
        }
    }

    private function validAudit($ids, $state, $adminId): array
    {
        $sendList = XsCommoditySend::useMaster()::getListByWhere([
            ['id', 'in', $ids],
        ]);
        $extendList = XsstCommoditySendExtend::getListBySid($ids);
        $reqList = [];
        $sendIdArr = Helper::arrayFilter($sendList, 'id');
        $diff = array_diff($ids, $sendIdArr);
        if ($diff) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('发放记录%s不存在', implode(',', $diff)));
        }
        $errorIdArr = [];
        foreach ($sendList as $send) {
            $extend = $extendList[$send['id']] ?? [];
            if ($send['state'] == $state || $send['state'] == 1 || empty($send['cid']) || empty($send['aid'])) {
                $errorIdArr[] = $send['id'];
                continue;
            }
            $reqList[] = [
                'order_id'    => (int)$send['id'],
                'uid'         => (int)$send['uid'],
                'remark'      => '',
                'source_desc' => $extend['source'] ?? '官方发放',
                'item'        => [
                    'type' => 1,
                    'id'   => (int)$send['cid'],
                    'num'  => (int)$send['num'],
                ],
            ];
        }
        if ($errorIdArr) {
            throw new ApiException(ApiException::MSG_ERROR,sprintf('发放记录%s信息不正确', implode(',', $errorIdArr)));
        }
        $cidArr = Helper::arrayFilter($sendList, 'cid');
        $commodityList = XsCommodity::getListByWhere([['cid', 'IN', $cidArr]], 'cid');
        $commodityIdArr = Helper::arrayFilter($commodityList, 'cid');
        $commodityDiff = array_diff($cidArr, $commodityIdArr);
        if ($commodityDiff) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('物品%s不存在', implode(',', $diff)));
        }

        $aidArr = Helper::arrayFilter($sendList, 'aid');
        $commodityAdminList = XsCommodityAdmin::getListByWhere([['cid', 'IN', $aidArr]], 'cid,type,state');
        $commodityAdminIdArr = Helper::arrayFilter($commodityAdminList, 'cid');
        $commodityAdminDiff = array_diff($aidArr, $commodityAdminIdArr);
        if ($commodityAdminDiff) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('物品%s不存在', implode(',', $diff)));
        }
        $typeArr = Helper::arrayFilter($commodityAdminList, 'type');
        if (in_array('gift', $typeArr) || in_array('coupon', $typeArr)) {
            (new RewardSendService())->validAuditWhitelist($adminId);
        }

        $errorCidArr = [];
        foreach ($commodityAdminList as $commodity) {
            if ($commodity['state'] != XsCommoditySend::STATE_PASS) {
                $errorCidArr[] = $commodity['cid'];
            }
        }

        if ($errorCidArr) {
            throw new ApiException(ApiException::MSG_ERROR,sprintf('物品%s信息不正确', implode(',', $errorIdArr)));
        }
        return $reqList;
    }

    /**
     * 物品发放+审核
     * @param array $params
     * @return void
     * @throws ApiException
     */
    public function sendAndAudit(array $params): void
    {
        $data = $this->send($params);
        usleep(1000 * 10);
        $auditData = [
            'ids'      => implode(',', $data['id']),
            'state'    => XsCommoditySend::STATE_PASS,
            'admin_id' => $params['admin_id']
        ];
        $this->audit($auditData);
    }
}