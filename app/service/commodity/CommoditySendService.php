<?php

namespace Imee\Service\Commodity;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsCommoditySendReason;
use Imee\Models\Rpc\MoneyRpc;
use Imee\Models\Xs\BaseModel as XsBaseModel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsConsumeTodo;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCommoditySend;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstCommodityBudgetKa;
use Imee\Models\Xsst\XsstCommoditySendExtend;
use Imee\Models\Xsst\XsstCommoditySendKa;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Phalcon\Di;
use Imee\Models\Xsst\XsstOaPolicy;
use Imee\Models\Xsst\XsstOaRelation;
use Exception;

class CommoditySendService
{
    private function getCondition($params): array
    {
        $id = intval($params['id'] ?? 0);
        $aid = $params['aid'] ?? [];
        $state = trim($params['state'] ?? '');
        $admin = trim($params['admin_name'] ?? '');
        $verifyAdmin = trim($params['verify_name'] ?? '');
        $startTime = $params['dateline_sdate'] ?? '';
        $endTime = $params['dateline_edate'] ?? '';
        $uid = intval($params['uid'] ?? 0);
        $type = trim($params['type'] ?? '');

        $conditions = [];
        if ($id > 0) {
            $conditions[] = ['id', '=', $id];
        }
        if ($aid) {
            if (is_string($aid)) {
                $aid = Helper::formatIdString($aid);
            }
            $conditions[] = ['aid', 'in', $aid];
        }
        if ($uid > 0) {
            $conditions[] = ['uid', '=', $uid];
        }
        if ($state !== '' && in_array($state, [0, 1, 2])) {
            $conditions[] = ['state', '=', $state];
        }
        if (!empty($oaNum)) {
            $oa = XsstOaPolicy::findOneByWhere([['oa_num', '=', $oaNum]]);
            $relationList = XsstOaRelation::getListByWhere([['oa_id', '=', $oa['id']]], 'relation_id');
            $conditions[] = ['id', 'in', array_column($relationList, 'relation_id')];
        }
        if (!empty($admin)) {
            if (!is_numeric($admin)) {
                $admin = CmsUser::findOneByWhere([['user_name', '=', $admin]]);
                if (empty($admin)) {
                    return [];
                }
                $admin = $admin['user_id'];
            }
            $conditions[] = ['admin', '=', $admin];
        }
        if (!empty($verifyAdmin)) {
            if (!is_numeric($verifyAdmin)) {
                $verifyAdmin = CmsUser::findOneByWhere([['user_name', '=', $verifyAdmin]]);
                if (empty($verifyAdmin)) {
                    return [];
                }
                $verifyAdmin = $verifyAdmin['user_id'];
            }
            $conditions[] = ['verifyadmin', '=', $verifyAdmin];
        }
        if (!empty($startTime)) {
            $conditions[] = ['dateline', '>=', strtotime($startTime)];
        }
        if (!empty($endTime)) {
            $conditions[] = ['dateline', '<=', strtotime(date('Y-m-d 23:59:59', strtotime($endTime)))];
        }
        if ($type) {
            $cids = XsCommodity::getListByWhere([['type', '=', $type]], 'cid');
            if (!$cids) {
                return [];
            }
            $conditions[] = ['cid', 'in', array_column($cids, 'cid')];
        }

        return $conditions;
    }

    public function getList($params, $minId = 0, $limit = 15): array
    {
        $conditions = $this->getCondition($params);

        if ($minId) {
            $conditions[] = ['id', '<', $minId];
        }

        $list = XsCommoditySend::getListByWhere($conditions, '*', 'id desc', $limit);
        if (empty($list)) {
            return [];
        }

        $this->listFormat($list);
        return $list;
    }

    public function getTotal($params): int
    {
        $conditions = $this->getCondition($params);
        return XsCommoditySend::getCount($conditions);
    }

    public function getListAndTotal(array $params, $page = 1, $limit = 15): array
    {
        $conditions = $this->getCondition($params);

        $result = XsCommoditySend::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
        if (empty($result['data'])) {
            return $result;
        }

        $this->listFormat($result['data']);
        return $result;
    }

    private function listFormat(&$data)
    {
        $uids = array_values(array_unique(array_column($data, 'uid')));
        $users = XsUserProfile::getUserProfileBatch($uids);

        $admins = array_unique(array_merge(array_column($data, 'admin'), array_column($data, 'verifyadmin')));
        $admins = CmsUser::getAdminUserBatch(array_values($admins));

        $cidArr = array_values(array_unique(array_column($data, 'cid')));
        $commodities = XsCommodity::getListByWhere([['cid', 'in', $cidArr]], 'cid,name,type,period,period_hour,price,image');
        $commodities = array_column($commodities, null, 'cid');

        $ids = array_column($data, 'id');

        $reason = BmsCommoditySendReason::getReason($ids);
        $extend = XsstCommoditySendExtend::getBatchCommon($ids, ['sid', 'source'], 'sid');

        foreach ($data as &$rec) {

            $commodity = $commodities[$rec['cid']] ?? [];
            if (!empty($commodity)) {
                $rec['cname'] = $commodity['name'] ?? '';

                if ($commodity['type'] == 'coupon') {
                    $rec['price'] = floatval(($commodity['duction_money'] ?? 0) / 100);
                } else {
                    $rec['price'] = floatval(($commodity['price'] ?? 0) / 100);
                }

                $rec['total_price'] = bcmul($rec['price'], $rec['num'], 2);
                $rec['type'] = XsCommodityAdmin::$typeMap[$commodity['type']] ?? '';
                $rec['period'] = [];
                if ($commodity['period'] > 0) {
                    $rec['period'][] = $commodity['period'] . '天';
                }
                if ($commodity['period_hour'] > 0) {
                    $rec['period'][] = $commodity['period_hour'] . '小时';
                }
                $rec['period'] = implode(' ', $rec['period']);
            }
            $rec['admin_name'] = $admins[$rec['admin']]['user_name'] ?? '';
            $rec['verifyadmin_name'] = $admins[$rec['verifyadmin']]['user_name'] ?? '';
            $rec['verifytime'] = $rec['verifytime'] > 0 ? Helper::now($rec['verifytime']) : '';
            $rec['dateline'] = $rec['dateline'] > 0 ? Helper::now($rec['dateline']) : '';
            $rec['uname'] = $users[$rec['uid']]['name'] ?? '';
            $rec['image'] = Helper::getHeadUrl($commodity['image']);
            $rec['reason'] = $reason[$rec['id']] ?? '';
            $rec['source'] = $extend[$rec['id']]['source'] ?? '';
        }
    }

    private function checkCid(array $cidArr, $appId = APP_ID)
    {
        //物品不存在或未审核通过
        $commodities = XsCommodityAdmin::find([
            'columns'    => 'cid,ocid,only_newpay,type,price,ext_id',
            'conditions' => 'cid IN ({cid:array}) AND app_id=:app_id: AND state=:state:',
            'bind'       => ['cid' => $cidArr, 'app_id' => $appId, 'state' => XsCommodityAdmin::STATE_PASS]
        ])->toArray();
        $diff = array_diff($cidArr, array_column($commodities, 'cid'));
        if ($diff) {
            throw new ApiException(ApiException::MSG_ERROR, implode(',', $diff) . ' 物品不存在或未审核通过');
        }

        //新充值奖励的物品不支持发放
        $newPay = [];
        foreach ($commodities as $c) {
            if ($c['only_newpay'] == 1) {
                $newPay[] = $c['cid'];
            }
        }
        if (!empty($newPay)) {
            throw new ApiException(ApiException::MSG_ERROR, '新充值奖励的物品不支持发放：' . implode(',', $newPay));
        }

        return $commodities;
    }

    public function create(array $params, $appId = APP_ID): array
    {
        if (empty($params['cid'])) {
            return [false, '物品ID不能为空'];
        }
        if (empty($params['uid'])) {
            return [false, '请填写uid'];
        }
        if (empty($params['num']) || $params['num'] <= 0) {
            return [false, '请填写num>0'];
        }

        $cidArr = $params['cid'];
        $cidArr = array_unique($cidArr);
        $cidArr = array_values($cidArr);
        $num = intval($params['num']);
        $uidArr = trim($params['uid']);
        $uidArr = str_replace('，', ',', $uidArr);
        $uidArr = array_unique(explode(',', $uidArr));
        $mark = trim($params['mark'] ?? '');
        $source = trim($params['source'] ?? '');
        $expDays = trim($params['exp_days'] ?? 0);

        if ($num < 1 || $num > 100) {
            throw new ApiException(ApiException::MSG_ERROR, '数量错误');
        }
        if ($expDays && ((intval($expDays) != $expDays) || $expDays > 9999)) {
            throw new ApiException(ApiException::MSG_ERROR, '资格使用有效天数必须为正整数不能超过9999天');
        }
        $expDays = intval($expDays);
        //检测uid cid
        $this->checkUid($uidArr);
        $this->checkAdminUserAuth($uidArr);
        $commodities = $this->checkCid($cidArr);
        $this->checkPieceCommodity($commodities, $expDays);
        $this->checkLuckyGift($commodities);

        //组装数据
        $data = [];
        $now = time();
        foreach ($uidArr as $uid) {
            foreach ($cidArr as $cid) {
                $data[] = [
                    'app_id'   => $appId,
                    'uid'      => $uid,
                    'cid'      => $cid,
                    'num'      => $num,
                    'source'   => empty($source) ? XsstCommoditySendExtend::$source : $source,
                    'exp_days' => intval($expDays),
                    'mark'     => $mark,
                    'dateline' => $now,
                    'admin'    => $params['admin_id'],
                ];
            }
        }

        return $this->save($data, $commodities);
    }

    private function commoditySendVerify($rec, $admin): array
    {
        $keyLock = 'AdminCommodity.' . $rec->uid;
        $conn = Di::getDefault()->getShared(XsBaseModel::SCHEMA);
        $conn->begin();

        try {
            $r = Helper::fetchColumn("select get_lock('{$keyLock}', 10) ", XsBaseModel::SCHEMA);
            if (!is_numeric($r) || $r != 1) {
                throw new \Exception("未获取到锁");
            }

            //获取requestId
            $requestId = (new MoneyRpc())->getRequestId();
            if ($requestId <= 0) {
                throw new \Exception("[MoneyRpc:CommoditySend] getRequestId error requestId={$requestId}");
            }

            $todo = XsConsumeTodo::useMaster();
            $todo->uid = $rec->uid;
            $todo->ctype = MoneyRpc::$_consumeType['commodity_send'];
            $todo->request_id = $requestId;
            $todo->params = json_encode(array(
                'uid'         => $rec->uid,
                'type'        => MoneyRpc::$_consumeType['commodity_send'],
                'requestId'   => $requestId,
                'commodities' => array([
                    'cid' => intval($rec->cid),
                    'num' => intval($rec->num)
                ]),
            ));
            $todo->status = 0;
            $todo->retry_cnt = 0;
            $todo->update_time = time();
            $todo->create_time = time();
            $todo->save();

            $rec->state = XsCommoditySend::STATE_PASS;
            $rec->verifyadmin = $admin;
            $rec->verifytime = time();
            $rec->save();
            BmsCommoditySendReason::cancelReason($rec->id); //取消未通过原因

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            Helper::fetchColumn("select release_lock('{$keyLock}') ", XsBaseModel::SCHEMA);
            return [false, $e->getMessage()];
        }

        Helper::fetchColumn("select release_lock('{$keyLock}') ", XsBaseModel::SCHEMA);

        return [true, ''];
    }

    public function save(array $data, array $commodities): array
    {
        //数量检测
        if (empty($data)) {
            return [false, '发放数据不能为空'];
        }
        if (count($data) > 5000) {
            return [false, '一次最多发放5000条记录'];
        }

        //保存发放物品
        $commodityList = array_column($commodities, 'ocid', 'cid');
        $extendData = [];

        foreach ($data as $rec) {
            if (empty($rec['cid']) || empty($rec['num']) || empty($rec['uid'])) {
                continue;
            }
            $rec['source'] = empty($rec['source']) ? XsstCommoditySendExtend::$source : $rec['source'];
            //交换正式cid
            $rec['aid'] = $rec['cid'];
            $rec['cid'] = $commodityList[$rec['cid']];
            $rec['state'] = XsCommoditySend::STATE_WAIT;

            //添加
            [$res, $id] = XsCommoditySend::add($rec);
            if ($res) {
                $extendData[] = [
                    'sid'      => $id,
                    'source'   => $rec['source'],
                    'admin_id' => $rec['admin'],
                    'dateline' => $rec['dateline'],
                ];
            }
        }
        if ($extendData) {
            foreach (array_chunk($extendData, 1000) as $data) {
                if ($data) {
                    XsstCommoditySendExtend::addBatch($data, 'REPLACE');
                }
                usleep(100 * 1000);
            }

        }
        return [true, ''];
    }

    public function import(array $data, $adminId, $appId = APP_ID): array
    {
        $result = [];
        $uidArr = [];
        $cidArr = [];
        foreach ($data as $line => $rec) {
            $lineStr = '【line:' . ($line + 1) . '】: ';
            if ($rec['uid'] == 'UID' || $rec['num'] == '数量') {
                continue;
            }
            $rec = Helper::trimParams($rec);
            if (!empty($rec['uid']) && !empty($rec['cid']) && !empty($rec['num'])) {
                $msg = [];
                if (!is_numeric($rec['uid']) || $rec['uid'] < 1) {
                    $msg[] = 'uid填写有误';
                }
                if (!is_numeric($rec['cid']) || $rec['cid'] < 1) {
                    $msg[] = 'cid填写有误';
                }
                if (!is_numeric($rec['num']) || $rec['num'] < 1 || $rec['num'] > 100) {
                    $msg[] = '数量填写有误';
                }
                $rec['exp_days'] = trim($rec['exp_days']);
                if ($rec['exp_days'] && ((intval($rec['exp_days']) != $rec['exp_days']) || $rec['exp_days'] > 9999)) {
                    $msg[] = '资格使用有效天数必须为正整数不能超过9999天';
                }

                if (count($msg)) {
                    return [false, $lineStr . implode('; ', $msg)];
                }

                $result[] = $rec;
                $uidArr[] = $rec['uid'];
                $cidArr[] = $rec['cid'];
            } else {
                return [false, $lineStr . 'UID|物品记录ID|数量必填'];
            }
        }

        //检测uid cid
        $this->checkUid($uidArr);
        $this->checkAdminUserAuth($uidArr);
        $commodities = $this->checkCid($cidArr);
        $this->checkPieceCommodity($commodities, 0, $result, 2);
        $this->checkLuckyGift($commodities);

        //组装数据
        $data = [];
        $now = time();
        foreach ($result as $rec) {
            $data[] = [
                'app_id'   => $appId,
                'uid'      => $rec['uid'],
                'cid'      => $rec['cid'],
                'num'      => $rec['num'],
                'source'   => empty($rec['source']) ? XsstCommoditySendExtend::$source : $rec['source'],
                'exp_days' => intval($rec['exp_days'] ?? 0),
                'mark'     => $rec['mark'],
                'dateline' => $now,
                'admin'    => $adminId,
            ];
        }

        return $this->save($data, $commodities);
    }

    public function checkAdminUserAuth($uidArr)
    {
        $userBigAreaList = XsUserBigarea::getUserBigareasChunk($uidArr);
        $bigAreaList = XsBigarea::getAllBigAreaCode();
        $admin = CmsUser::findOne(Helper::getSystemUid());
        $adminUserBigArea = array_map(function ($item) use ($bigAreaList) {
            return array_search($item, $bigAreaList);
        }, explode(',', $admin['bigarea']));
        $data = [];
        foreach ($userBigAreaList as $uid => $bigArea) {
            if (!in_array($bigArea, $adminUserBigArea)) {
                $data[] = $bigArea;
            }
        }
        $data = array_values(array_unique($data));
        $bigAreaName = array_map(function ($item) {
            return XsBigarea::AREA_MAP[$item];
        }, $data);
        if ($bigAreaName) {
            throw new ApiException(ApiException::MSG_ERROR, '你无权发放物品给' . implode(',', $bigAreaName) . '的用户');
        }
    }

    public function checkPieceCommodity(array $commodities, int $expDays = 0, array $data = [], int $type = 1)
    {
        $cidArr = [];
        if ($type == 2) {
            $commodityMap = array_column($commodities, 'type', 'cid');
            foreach ($data as $item) {
                if ($item['cid'] == 0) continue;
                $type = $commodityMap[$item['cid']] ?? '';
                if ($type == 'piece' && $item['exp_days'] > 0) {
                    $cidArr[] = $item['cid'];
                }
            }
        } else {
            if ($expDays) {
                foreach ($commodities as $c) {
                    $c['type'] == 'piece' && $cidArr[] = $c['cid'];
                }
            }
        }

        if (!empty($cidArr)) {
            throw new ApiException(ApiException::MSG_ERROR, '物品类型为“碎片” 时，不可设置有限的“资格使用有效天数” 。cid：' . implode(',', $cidArr));
        }
    }

    public static function checkLuckyGift(array $commodities)
    {
        $ids = [];
        foreach ($commodities as $c) {
            if ($c['type'] == 'gift' && $c['ext_id']) {

                $ids[] = [
                    'cid' => $c['cid'],
                    'gid' => $c['ext_id']
                ];
            }
        }
        $giftIds = array_column($ids, 'gid');
        $giftIds = array_unique($giftIds);
        $giftIds = array_values($giftIds);
        $res = XsGift::getLuckyGiftByIdBatch($giftIds);
        if (!empty($res)) {
            $luckyComIds = [];
            foreach ($ids as $v) {
                if (isset($res[$v['gid']])) {
                    $luckyComIds[] = $v['cid'];
                }
            }
            throw new ApiException(ApiException::MSG_ERROR, '幸运礼物物品不支持发放' . implode(',', $luckyComIds));
        }
    }

    private function checkUid(array $uidArr, $appId = APP_ID): bool
    {
        $uidFilter = array_filter($uidArr, function ($uid) {
            return intval(trim($uid)) > 0;
        });
        if (empty($uidFilter) || count($uidFilter) != count($uidArr)) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID错误');
        }
        $uidArr = array_values(array_unique($uidArr));

        foreach (array_chunk($uidArr, 1000) as $uids) {
            if (!$uids) {
                continue;
            }
            $users = XsUserProfile::getListByWhere([['uid', 'in', array_values($uids)], ['app_id', '=', $appId]], 'uid');
            if (count($uids) != count($users)) {
                $diffUid = array_diff($uids, array_column($users, 'uid'));
                throw new ApiException(ApiException::MSG_ERROR, implode(',', $diffUid) . ' UID有误');
            }
            usleep(100 * 1000);
        }
        return true;
    }

    /**
     * @desc 批量审批优化
     */
    public function auditMulti(array $ids, int $state, int $adminId): array
    {
        $ids = array_values($ids);

        $filterIds = array_filter($ids, function ($id) {
            return is_numeric($id) && $id > 0;
        });
        if (count($filterIds) != count($ids) || !$ids) {
            return [false, '非法操作'];
        }
        if (!is_numeric($state) || !in_array($state, [XsCommoditySend::STATE_PASS, XsCommoditySend::STATE_FAIL])) {
            return [false, '审核状态提交不正确'];
        }
        $sends = XsCommoditySend::useMaster()->findByIds($ids);
        if (count($sends) != count($ids)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前数据不存在，请确认');
        }

        $commodities = XsCommodity::findByIds(array_column($sends, 'cid'), 'cid,type');
        $commodities = array_column($commodities, null, 'cid');

        $adminCommodities = XsCommodityAdmin::findByIds(array_column($sends, 'aid'), 'cid,state');
        $adminCommodities = array_column($adminCommodities, null, 'cid');

        $extends = XsstCommoditySendExtend::findByIds($ids, 'sid,source');
        $extends = array_column($extends, null, 'sid');

        $purviews = CmsModuleUser::getUserAllAction($adminId);
        $reqList = [];

        foreach ($sends as $rec) {
            if ($rec['state'] == $state) {
                throw new ApiException(ApiException::MSG_ERROR, '状态没改：' . $rec['id']);
            }
            if ($rec['state'] >= XsCommoditySend::STATE_PASS) {
                throw new ApiException(ApiException::MSG_ERROR, '审核后不可修改：' . $rec['id']);
            }
            if ($rec['cid'] < 1 || $rec['aid'] < 1) {
                throw new ApiException(ApiException::MSG_ERROR, '物品错误：' . $rec['id']);
            }

            $commodity = $commodities[$rec['cid']] ?? [];
            if (!$commodity) {
                throw new ApiException(ApiException::MSG_ERROR, '物品错误');
            }
            if (in_array($commodity['type'], ['gift', 'coupon'])) {
                if (!in_array('operate/commodity/commoditysend.giftSendVerify', $purviews)) {
                    throw new ApiException(ApiException::MSG_ERROR, '本次要发放的物品中包含了礼物或优惠券类型的物品，你没有发放审核权限');
                }
            }

            $adminCommodity = $adminCommodities[$rec['aid']] ?? [];
            if (!$adminCommodity) {
                throw new ApiException(ApiException::MSG_ERROR, '物品错误[admin]');
            }
            if ($adminCommodity['state'] != XsCommodityAdmin::STATE_PASS) {
                throw new ApiException(ApiException::MSG_ERROR, "物品未审核通过[物品记录ID:{$rec['aid']}]");
            }

            if (ENV != 'dev' && $rec['admin'] == $adminId) {
                //throw new ApiException(ApiException::MSG_ERROR, '自己不能审核自己！');
            }

            if ($state == XsCommoditySend::STATE_PASS) {
                $extend = $extends[$rec['id']] ?? [];
                $reqList[] = [
                    'order_id'    => $rec['id'],
                    'uid'         => $rec['uid'],
                    'remark'      => '',
                    'source_desc' => $extend['source'] ?? XsstCommoditySendExtend::$source,
                    'item'        => [
                        'type'     => 1,//默认
                        'id'       => $rec['cid'],
                        'num'      => $rec['num'],
                        'exp_days' => $rec['exp_days'],
                    ]
                ];
            }
        }

        $success = true;
        try {
            if ($reqList) {
                list($success, $msg) = (new PsService())->sendCommodity([
                    'list'     => $reqList,
                    'operator' => Helper::getAdminName($adminId),
                    'reason'   => ''
                ]);
            }

            if ($success) {
                retry(3, function () use ($ids, $state, $adminId) {
                    [$res, $msg, $_] = XsCommoditySend::updateByWhere([['id', 'in', $ids]], [
                        'state'       => $state,
                        'verifyadmin' => $adminId,
                        'verifytime'  => time(),
                    ]);
                    if (!$res) {
                        throw new Exception();
                    }
                    return true;
                }, 100);

                retry(3, function () use ($ids) {
                    [$res, $msg, $_] = BmsCommoditySendReason::updateByWhere([['sid', 'in', $ids]], [
                        'reason'      => '',
                        'update_time' => time(),
                    ]);
                    if (!$res) {
                        throw new Exception();
                    }
                    return true;
                }, 100);

            } else {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }
        } catch (Exception $e) {
            throw new ApiException(ApiException::MSG_ERROR, $e->getMessage());
        }

        return [];
    }

    private function pass($rec, $adminId): array
    {
        $commodity = XsCommodity::findOne($rec['cid']);
        if (empty($commodity)) {
            return [false, '物品错误'];
        }
        if (in_array($commodity['type'], ['gift', 'coupon'])) {
            $purviews = CmsModuleUser::getUserAllAction($adminId);
            if (!in_array('operate/commodity/commoditysend.giftSendVerify', $purviews)) {
                return [false, '本次要发放的物品中包含了礼物或优惠券类型的物品，你没有发放审核权限'];
            }
        }

        $commodityAdmin = XsCommodityAdmin::findOne($rec['aid']);
        if (empty($commodityAdmin)) {
            return [false, '物品错误[admin]'];
        }

        if ($commodityAdmin['state'] != XsCommodityAdmin::STATE_PASS) {
            return [false, '物品未审核通过'];
        }

        if (ENV != 'dev' && $rec['admin'] == $adminId) {
            //return [false, '自己不能审核自己！'];
        }

        return $this->commoditySendVerify($rec, $adminId);
    }

    private function noPass($rec, $adminId): array
    {
        [$success, $msg] = XsCommoditySend::edit($rec->id, [
            'state'       => XsCommoditySend::STATE_FAIL,
            'verifyadmin' => $adminId,
            'verifytime'  => time()
        ]);

        if ($success) {
            OperateLog::addOperateLog([
                'uid'         => $rec->uid,
                'content'     => $rec->cid,
                'before_json' => ['state' => XsCommoditySend::STATE_WAIT],
                'after_json'  => ['state' => XsCommoditySend::STATE_FAIL],
                'type'        => BmsOperateLog::TYPE_OPERATE_LOG,
                'model'       => XsCommoditySend::getTableName(),
                'model_id'    => $rec->id,
                'action'      => BmsOperateLog::ACTION_REVIEW,
            ]);
        }
        return [$success, $msg];
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(XsCommodityAdmin::$typeMap, 'label,value');
    }
}