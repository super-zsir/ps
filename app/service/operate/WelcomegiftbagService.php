<?php

namespace Imee\Service\Operate;

use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\XsBatchAgencyHunterGiftBag;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerAdmin;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsGiftBag;
use Imee\Models\Xs\XsCommodity;

use Imee\Models\Xs\XsChatroomBackgroundMall;


use Imee\Models\Xs\XsstCouponAreaManage;
use Imee\Models\Xs\XsstCouponIssued;
use Imee\Service\Helper;
use Imee\Service\Operate\Coupon\GameCouponIssuedService;
use Imee\Service\Rpc\PsService;
use Imee\Service\Domain\Context\Welcomegiftbag\CreateContext;
use Imee\Service\Domain\Context\Welcomegiftbag\ModifyContext;

use Imee\Service\Domain\Context\Welcomegiftbag\ModifyhunterContext;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsAgencyHunterGiftBag;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Service\StatusService;

class WelcomegiftbagService
{
    use SingletonTrait;

    /**
     * @var array 优惠券下发数据源
     */
    private $couponData = [];

    /**
     * @var array 需要下发的uid
     * @var array
     */
    private $sendUidBigAreaMap = [];

    public function getStatus()
    {
        $format = [];
        foreach (XsGiftBag::$displayStatus as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getRewardType()
    {
        $format = [];
        foreach (XsGiftBag::$displayRewardType as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getConfig()
    {
        $typeChildrens = [];
        foreach (XsGiftBag::$displayRewardType as $type => $_) {
            $typeChildrens[$type] = $this->getSource($type);
        }

        return [
            'gb_type'      => $this->getRewardType(),
            'reward_id'    => $typeChildrens,
            'vip_day'      => $this->getVipDayMap(),
            'add_vip_type' => $this->getVipTypeMap(),
        ];
    }

    public function getGiftBag()
    {
        $list = XsGiftBag::getAllValidList();
        $format = [];
        if (empty($list)) {
            return $format;
        }
        foreach ($list as $k => $v) {
            $tmp['label'] = '(' . $v['id'] . ')' . $v['name'];
            $tmp['value'] = $v['id'];
            $format[] = $tmp;
        }
        return $format;
    }

    public function getGiftBagCondition()
    {
        $list = XsGiftBag::getAllValidList();
        $map = [];
        $format = [];

        foreach ($list as $item) {
            $rewardArr = @json_decode($item['reward_json'], true);
            $bool = true;
            foreach ($rewardArr as $reward) {
                if (in_array($reward['gb_type'], XsGiftBag::$couponList)) {
                    $bool = false;
                    break;
                }
            }
            if ($bool) {
                $map[] = $item;
            }
        }

        foreach ($map as $v) {
            $tmp['label'] = '(' . $v['id'] . ')' . $v['name'];
            $tmp['value'] = $v['id'];
            $format[] = $tmp;
        }
        return $format;

    }

    public function getHunterStatus()
    {
        $format = [];
        foreach (XsAgencyHunterGiftBag::$displayStatus as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getSource(int $type): array
    {
        $format = [];
        if (in_array($type, XsGiftBag::$commodityList)) {
            $commodityList = XsCommodity::getListByWhere([
                ['type', '=', XsGiftBag::$commodityTypeMap[$type]]
            ]);
            if ($commodityList) {
                foreach ($commodityList as $commodity) {
                    $tmp['label'] = '物品(' . $commodity['cid'] . ')' . $commodity['name'];
                    $tmp['value'] = '物品-' . $commodity['cid'];
                    $format[] = $tmp;
                }
            }
        } else if ($type == XsGiftBag::REWARD_MEDAL) {
            $medalList = XsMedalResource::getMedalList(XsMedalResource::HONOR_MEDAL);
            if ($medalList) {
                foreach ($medalList as $medal) {
                    $tmp['label'] = '勋章(' . $medal['id'] . ')' . $medal['name'];
                    $tmp['value'] = '勋章-' . $medal['id'];
                    $format[] = $tmp;
                }
            }
        } else if ($type == XsGiftBag::REWARD_BACKGROUND) {
            $bgcList = XsChatroomBackgroundMall::getOptions();
            if ($bgcList) {
                foreach ($bgcList as $bgc) {
                    [$id, $name] = explode('-', $bgc);
                    $tmp['label'] = '背景(' . $id . ')' . $name;
                    $tmp['value'] = '背景-' . $id;
                    $format[] = $tmp;
                }
            }
        } else if ($type == XsGiftBag::REWARD_PRETTY_UID) {
            $styleList = XsCustomizePrettyStyle::findAll();
            if ($styleList) {
                foreach ($styleList as $v) {
                    $tmp['label'] = '靓号(' . $v['id'] . ')' . $v['name'];
                    $tmp['value'] = '靓号-' . $v['id'];
                    $format[] = $tmp;
                }
            }
        } else if ($type == XsGiftBag::REWARD_GAME_COUPON) {
            $gameCouponList = (new PsService())->getGameCouponAllList();
            if ($gameCouponList) {
                foreach ($gameCouponList as $coupon) {
                    $tmp['label'] = '游戏优惠券（' . $coupon['id'] . '）' . $coupon['amount'] . '💎';
                    $tmp['value'] = '游戏优惠券-' . $coupon['id'];
                    $format[] = $tmp;
                }
            }
        } else if ($type == XsGiftBag::REWARD_VIP) {
            $vipList = [
                'vip-1' => 'vip1'
            ];
            $format = StatusService::formatMap($vipList);
        }

        return $format;
    }

    public function getList(array $params): array
    {
        $filter = [];

        if (isset($params['bid']) && is_numeric($params['bid'])) {
            $filter[] = ['id', '=', $params['bid']];
        }
        if (isset($params['name']) && $params['name']) {
            $filter[] = ['name', 'like', "%" . $params['name'] . "%"];
        }
        if (isset($params['status']) && is_numeric($params['status'])) {
            $filter[] = ['status', '=', $params['status']];
        }
        $res = XsGiftBag::getListAndTotal($filter, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if ($res['total'] == 0 || empty($res['data'])) {
            return $res;
        }
        foreach ($res['data'] as &$v) {
            $v['bid'] = $v['id'];
            unset($v['id']);
            if ($v['name_json']) {
                $nameArr = @json_decode($v['name_json'], true);
                if ($nameArr) {
                    $v = $v + $nameArr;
                }
            }
            $v['reward_list'] = $this->displayRewardJson($v['reward_json']);
            $v['reward_str'] = implode("<br />", $v['reward_list']);
            // reward_json
            $v['display_status'] = isset(XsGiftBag::$displayStatus[$v['status']]) ?
                XsGiftBag::$displayStatus[$v['status']] : '';
        }

        return $res;
    }

    private function displayRewardJson($rewardJson)
    {
        if (!$rewardJson) {
            return "";
        }
        $rewardArr = @json_decode($rewardJson, true);
        if (!$rewardArr) {
            return "";
        }
        $rewardlist = [];
        foreach ($rewardArr as $reward) {
            $tmp = isset(XsGiftBag::$displayRewardType[$reward['gb_type']]) ?
                XsGiftBag::$displayRewardType[$reward['gb_type']] : '-';

            if (!empty($reward['reward_id'])) {
                if (in_array($reward['gb_type'], XsGiftBag::$commodityList)) {
                    //查询物品存在否
                    $commodityInfo = XsCommodity::findOne($reward['reward_id']);
                    $tmp .= " (id:" . $reward['reward_id'] . ")";
                    if ($commodityInfo) {
                        $tmp .= $commodityInfo['name'];
                    }
                } elseif (in_array($reward['gb_type'], XsGiftBag::$medalList)) {
                    $medalInfo = XsMedalResource::findOne($reward['reward_id']);
                    $tmp .= " (id:" . $reward['reward_id'] . ")";
                    if ($medalInfo && $medalInfo['description_zh_tw']) {
                        $medalInfoName = @json_decode($medalInfo['description_zh_tw'], true);
                        if ($medalInfoName && isset($medalInfoName['name'])) {
                            $tmp .= $medalInfoName['name'];
                        }
                    }
                } elseif (in_array($reward['gb_type'], XsGiftBag::$backgroundList)) {
                    $tmp .= " (id:" . $reward['reward_id'] . ")";
                    $info = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $reward['reward_id']]]);
                    if ($info) {
                        $tmp .= $info['name'];
                    }
                } elseif (in_array($reward['gb_type'], [XsGiftBag::REWARD_PRETTY_UID])) {
                    $tmp .= " (id:" . $reward['reward_id'] . ")";
                    // $info = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $reward['reward_id']]]);
                    $info = XsCustomizePrettyStyle::findOneByWhere([['id', '=', $reward['reward_id']]]);
                    if ($info) {
                        $tmp .= $info['name'];
                    }
                } else if (in_array($reward['gb_type'], XsGiftBag::$couponList)) {
                    $tmp .= " (id:" . $reward['reward_id'] . ")";
                    list($amountFlg, $amount) = (new GameCouponIssuedService())->getCouponAmount($reward['reward_id']);
                    if ($amountFlg) {
                        $tmp .= '档位:' . $amount;
                    }
                } elseif ($reward['gb_type'] == XsGiftBag::REWARD_VIP) {
                    $tmp .= " (vip:" . $reward['reward_id'] . ")";
                    $vipDays = $reward['extra']['vip']['valid_days'] ?? 0;
                    $tmp .= " vip天数:{$vipDays}";
                    $vipType = $reward['extra']['vip']['add_vip_type'] ?? 0;
                    $vipTypeMap = array_column($this->getVipTypeMap(), 'label', 'value');
                    $tmp .= " 是否可赠送:" . $vipTypeMap[$vipType] ?? '';
                    if ($vipType > 0) {
                        $tmp .= " 数量:" . $reward['num'];
                    }
                }
                //  else {
                //     $tmp .= " " . $reward['reward_id'];
                // }
            }
            if ($reward['gb_type'] != XsGiftBag::REWARD_VIP) {
                $tmp .= " 数量为" . (isset($reward['num']) ? $reward['num'] : 0);
            }
            $rewardlist[] = $tmp;
        }
        return $rewardlist;
    }

    public function create($params)
    {
        $context = new CreateContext($params);
        $this->verifyCreate($context);
        $nameCollect = $context->toArray();
        unset($nameCollect['reward']);
        unset($nameCollect['remark']);
        $rewards = $context->reward;
        foreach ($rewards as &$v) {
            if (isset($v['reward_id'])) {
                $resultList = explode('-', $v['reward_id']);
                $v['reward_id'] = isset($resultList[1]) ? (int)$resultList[1] : 0;
            }
            // if (in_array($v['gb_type'], [XsGiftBag::REWARD_EXP, XsGiftBag::REWARD_PRETTY_UID])) {
            // $v['num'] = 1;
            $v['gb_type'] = (int)$v['gb_type'];
            if ($v['gb_type'] == XsGiftBag::REWARD_EXP) {
                $v['num'] = 30;
                $v['reward_id'] = 0;
            }
            if ($v['gb_type'] == XsGiftBag::REWARD_VIP) {
                $vipDay = (int)$v['vip_day'];
                $vipNum = (int)($v['vip_num'] ?? 0);
                $vipType = (int)$v['add_vip_type'];

                $v['num'] = $vipType > 0 ? $vipNum : 1;
                $v['extra'] = [
                    'vip' => [
                        'add_vip_type' => $vipType,
                        'valid_days'   => $vipDay,
                    ],
                ];
                unset($v['vip_day'], $v['add_vip_type'], $v['vip_num']);
            }
            // }
        }
        unset($v);

        foreach ($nameCollect as $k => $v) {
            if (empty($v)) {
                unset($nameCollect[$k]);
            }
        }
        $buildParams = [
            'name'            => isset($nameCollect['cn']) ? $nameCollect['cn'] : '',
            'name_json'       => $nameCollect,
            'remark'          => $context->remark ? $context->remark : '',
            'status'          => 0,
            'gift_bag_reward' => $rewards,
        ];
        list($res, $msg) = (new PsService())->createWelcomgiftbag($buildParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $msg);
        }
        return ['before_json' => '', 'after_json' => $buildParams];
    }

    private function verifyCreate($context)
    {
        //验证reward
        $i = 1;
        $gbTypeMap = [];
        $isCoupon = false;
        foreach ($context->reward as $v) {
            if (isset($v['reward_id'])) {
                $resultList = explode('-', $v['reward_id']);
                $v['reward_id'] = isset($resultList[1]) ? $resultList[1] : 0;
            }
            if (!isset($gbTypeMap[$v['gb_type']])) {
                $gbTypeMap[$v['gb_type']] = [];
            }
            $tmpRewardValue = -1;
            if (!in_array($v['gb_type'], [XsGiftBag::REWARD_EXP])) {
                $tmpRewardValue = $v['reward_id'];
            }
            if (in_array($tmpRewardValue, $gbTypeMap[$v['gb_type']])) {
                throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行填写重复(同一类型同一物品只允许出现一次)");
            }
            $gbTypeMap[$v['gb_type']][] = $tmpRewardValue;
            if (in_array($v['gb_type'], XsGiftBag::$commodityList)) {
                //查询物品存在否
                $commodityInfo = XsCommodity::findOne($v['reward_id']);
                if (!$commodityInfo) {
                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行的物品ID不存在");
                }

                if (!isset(XsGiftBag::$allooCommodityMap[$v['gb_type']])) {
                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行的类型允许的物品类型未配置");
                }
                if (!in_array($commodityInfo['type'], XsGiftBag::$allooCommodityMap[$v['gb_type']])) {
                    $allowTypes = XsGiftBag::$allooCommodityMap[$v['gb_type']];
                    $displayAllowTypes = array_map(function ($v) {
                        return isset(XsCommodityAdmin::$subTypeList[$v]) ? XsCommodityAdmin::$subTypeList[$v] : $v;
                    }, $allowTypes);

                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行只允许选择类型为" . implode(',', $displayAllowTypes) . "的物品");
                }
            } elseif (in_array($v['gb_type'], XsGiftBag::$medalList)) {
                $medalInfo = XsMedalResource::findOne($v['reward_id']);
                if (!$medalInfo) {
                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行的勋章ID不存在");
                }
                if ($medalInfo['type'] != XsMedalResource::HONOR_MEDAL) {
                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行的勋章不属于荣誉勋章");
                }
            } elseif (in_array($v['gb_type'], XsGiftBag::$backgroundList)) {
                $info = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $v['reward_id']]]);
                if (!$info) {
                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行的背景ID不存在");
                }
            } elseif (in_array($v['gb_type'], [XsGiftBag::REWARD_PRETTY_UID])) {
                // $info = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $v['reward_id']]]);
                $info = XsCustomizePrettyStyle::findOneByWhere([['id', '=', $v['reward_id']]]);
                if (!$info) {
                    throw new ApiException(ApiException::MSG_ERROR, "第" . $i . "行的靓号卡ID不存在");
                }
                if (isset($v['num']) && $v['num'] != 1) {
                    throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的天数/数量, 此项只允许填写1");
                }
            } elseif (in_array($v['gb_type'], [XsGiftBag::REWARD_EXP])) {
                if (isset($v['reward_id']) && strlen($v['reward_id']) > 0) {
                    throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的物品/勋章/背景ID, 此项不允许填写数据");
                }

                if (isset($v['num']) && strlen($v['num']) > 0) {
                    throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的天数/数量, 此项不允许填写数据");
                }
            } else if (in_array($v['gb_type'], XsGiftBag::$couponList)) {
                // 暂时限制一下每个礼包内只能配置一条优惠券奖励。避免后面多条优惠券下发审核逻辑问题。
                if ($isCoupon) {
                    throw new ApiException(ApiException::MSG_ERROR, '每个礼包内只能存在一条优惠券奖励');
                }
                $isCoupon = true;
            } elseif ($v['gb_type'] == XsGiftBag::REWARD_VIP) {
                $vip = $v['reward_id'] ?? '';
                $vipDay = $v['vip_day'] ?? '';
                $vipType = $v['add_vip_type'] ?? '';
                $vipTypeNum = $v['vip_num'] ?? '';
                if (!is_numeric($vip) || $vip < 1) {
                    throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的ID, 此项只允许选择vip1");
                }
                $vipDays = array_column($this->getVipDayMap(), 'value');
                if (!is_numeric($vipDay) || !in_array($vipDay, $vipDays)) {
                    throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的vip天数, 请选择正确的选项");
                }
                $vipTypes = array_column($this->getVipTypeMap(), 'value');
                if (!is_numeric($vipType) || !in_array($vipType, $vipTypes)) {
                    throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的是否可赠送, 请选择正确的选项");
                }
                if ($vipType > 0) {
                    if (!is_numeric($vipTypeNum) || $vipTypeNum < 1) {
                        throw new ApiException(ApiException::MSG_ERROR, "请检查第" . $i . "行的数量, 此项只允许填写大于0的数字");
                    }
                }
            }

            $i++;
        }
    }

    public function modify($params)
    {
        $context = new ModifyContext($params);
        $info = $this->verifyModify($context);

        if ($context->status == XsGiftBag::STATUS_VALID) {
            $nameCollect = $context->toArray();
            unset($nameCollect['bid']);
            unset($nameCollect['remark']);
            unset($nameCollect['status']);
            foreach ($nameCollect as $k => $v) {
                if (empty($v)) {
                    unset($nameCollect[$k]);
                }
            }

            $buildParams = [
                'name'      => isset($nameCollect['cn']) ? $nameCollect['cn'] : '',
                'name_json' => $nameCollect,
                'remark'    => $context->remark ? $context->remark : '',
                'status'    => $context->status,
                'id'        => (int)$context->bid,
            ];
        } else {
            $buildParams = [
                'name'      => $info['name'],
                'name_json' => $info['name_json'],
                'remark'    => $info['remark'],
                'status'    => $context->status,
                'id'        => (int)$context->bid,
            ];
        }


        list($res, $msg) = (new PsService())->modifyWelcomgiftbag($buildParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $msg);
        }

        return ['before_json' => $info, 'after_json' => $buildParams];
    }

    private function verifyModify($context)
    {
        $info = XsGiftBag::findOne($context->bid);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, "数据不存在");
        }
        if ($info['status'] == XsGiftBag::STATUS_UNVALID) {
            throw new ApiException(ApiException::MSG_ERROR, "无效的数据不允许再操作数据");
        }
        return $info;
    }

    public function getHunterList($params)
    {
        $filter = [];

        if (isset($params['gb_id']) && is_numeric($params['gb_id'])) {
            $filter[] = ['gb_id', '=', $params['gb_id']];
        }

        if (isset($params['uid']) && is_numeric($params['uid'])) {
            $filter[] = ['uid', '=', $params['uid']];
        }

        if (isset($params['cn']) && $params['cn']) {
            $bagInfos = XsGiftBag::find([
                'conditions' => 'name like :name:',
                'bind'       => [
                    'name' => "%" . $params['cn'] . "%",
                ],
            ])->toArray();
            $bagIds = array_column($bagInfos, 'id');
            $bagIds = array_merge($bagIds, [-1]);
            $filter[] = ['gb_id', 'in', $bagIds];
        }

        if (isset($params['max_id']) && $params['max_id']) {
            $filter[] = ['id', '<', $params['max_id']];
        }

        if (!empty($params['start_time'])) {
            $filter[] = ['create_time', '>=', strtotime($params['start_time'])];
        }

        if (!empty($params['end_time'])) {
            $filter[] = ['create_time', '<=', strtotime($params['end_time'])];
        }

        if (isset($params['status']) && is_numeric($params['status'])) {
            if ($params['status'] == 1) {
                $filter[] = ['deleted', '=', 1];
            } else if ($params['status'] == 3) {
                $filter[] = ['status', 'IN', [XsAgencyHunterGiftBag::HAVE_AUDIT_STATUS, XsAgencyHunterGiftBag::ERROR_AUDIT_STATUS]];
            } else {
                $filter[] = ['deleted', '=', 0];
                $filter[] = ['status', 'IN', [XsAgencyHunterGiftBag::NO_AUDIT_STATUS, XsAgencyHunterGiftBag::SUCCESS_AUDIT_STATUS]];
                if ($params['status'] == 0) {
                    $filter[] = ['expire_time', '>=', time()];
                } else {
                    $filter[] = ['expire_time', '<', time()];
                }
            }
        }

        if (!empty($params['batch_id'])) {
            $filter[] = ['batch_id', '=', $params['batch_id']];
        }

        $res = XsAgencyHunterGiftBag::getListAndTotal($filter, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if ($res['total'] == 0 || empty($res['data'])) {
            return $res;
        }
        $bagIds = [];
        $uids = [];
        foreach ($res['data'] as $v) {
            $bagIds[] = $v['gb_id'];
            $uids[] = $v['uid'];
        }
        $bagList = XsGiftBag::find([
            'conditions' => 'id in({ids:array})',
            'bind'       => [
                'ids' => $bagIds,
            ],
        ])->toArray();
        $bagMap = array_column($bagList, null, 'id');
        $userMap = XsUserProfile::getUserProfileBatch($uids);
        $userBigAreaMap = XsUserBigarea::getUserBigareas($uids);
        $bigAreaMap = XsBigarea::getAllNewBigArea();
        $couponIssuedList = XsstCouponIssued::getListByAgbId(array_column($res['data'], 'id'));

        foreach ($res['data'] as &$v) {
            $v['user_name'] = isset($userMap[$v['uid']]) ? $userMap[$v['uid']]['name'] : '';
            $bagInfo = isset($bagMap[$v['gb_id']]) ? $bagMap[$v['gb_id']] : [];

            $nameArr = @json_decode($bagInfo['name_json'], true);
            $v['cn'] = $nameArr && isset($nameArr['cn']) ? $nameArr['cn'] : '';
            $v['en'] = $nameArr && isset($nameArr['en']) ? $nameArr['en'] : '';
            $v['bigarea_name'] = isset($userBigAreaMap[$v['uid']]) &&
            isset($bigAreaMap[$userBigAreaMap[$v['uid']]]) ?
                $bigAreaMap[$userBigAreaMap[$v['uid']]] : '';
            $isCoupon = $couponIssuedList[$v['id']] ?? 0;
            $v['audit_status'] = $this->formatAuditStatus($v['status'], $isCoupon);
            $v['status'] = XsAgencyHunterGiftBag::displayStatus($v, $isCoupon);
            $v['display_status'] = isset(XsAgencyHunterGiftBag::$displayStatus[$v['status']]) ?
                XsAgencyHunterGiftBag::$displayStatus[$v['status']] : '-';
            $v['create_time'] = $v['create_time'] > 0 ? date('Y-m-d H:i:s', $v['create_time']) : '';
            $v['expire_time'] = $v['expire_time'] > 0 ? date('Y-m-d H:i:s', $v['expire_time']) : '';
        }

        return $res;
    }

    public function createhunter(array $params): array
    {
        $uidArr = Helper::formatIdString($params['uid']);
        $gbId = intval($params['gb_id'] ?? 0);
        $num = intval($params['num'] ?? 0);
        $validDay = intval($params['valid_day'] ?? 0);
        $bigAreaId = intval($params['big_area_id'] ?? 0);
        $operateUid = intval($params['operate_uid'] ?? $params['admin_uid']);

        $this->validateUid($uidArr, 10, $bigAreaId);


        $this->validateUid($uidArr, 10);
        $data = [];
        foreach ($this->sendUidBigAreaMap as $uid => $bigAreaId) {
            $data[] = [
                'uid'       => $uid,
                'gb_id'     => $gbId,
                'num'       => $num,
                'valid_day' => $validDay,
            ];
        }

        $this->validateGiftBag($data);

        $this->addBatch($data, $operateUid, '迎新礼包下发[无需在此审核]');

        $afterJson = [
            'gb_id'     => $gbId,
            'uid'       => $params['uid'],
            'valid_day' => $validDay,
            'num'       => $num,
        ];

        return ['before_json' => '', 'after_json' => $afterJson];
    }

    /**
     * 验证用户信息
     * @param array $params
     * @return void
     * @throws ApiException
     */
    private function validateUid(array $uidArr, int $maxCount = 0, int $bigAreaId = 0): void
    {
        if ($maxCount && count($uidArr) > $maxCount) {
            throw new ApiException(ApiException::MSG_ERROR, "一次最多只支持10个用户");
        }

        $userLists = XsUserProfile::getListByWhere([['uid', 'IN', $uidArr]], 'uid');
        if (count($userLists) != count($uidArr)) {
            $diffUid = array_diff($uidArr, array_column($userLists, 'uid'));
            throw new ApiException(ApiException::MSG_ERROR, sprintf("%s用户不存在，请检查后再试", implode(',', $diffUid)));
        }

        $uidBigAreaMap = [];
        if ($bigAreaId) {
            foreach ($uidArr as $uid) {
                $uidBigAreaMap[$uid] = $bigAreaId;
            }
        } else {
            $uidBigAreaMap = XsUserBigarea::getUserBigareasChunk($uidArr);
        }
        $this->sendUidBigAreaMap = $uidBigAreaMap;
    }

    public function modifyhunter($params)
    {
        $context = new ModifyhunterContext($params);
        $info = $this->verifyModifyhunter($context);

        $buildParams = [
            'id'      => (int)$context->id,
            'deleted' => (int)$context->deleted,
            'num'     => (int)($context->deleted == XsAgencyHunterGiftBag::DELETED_YES ? $info['num'] : $context->num),
        ];

        list($res, $msg) = (new PsService())->modifyWelcomgifthunter($buildParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $msg);
        }
        return ['before_json' => $info, 'after_json' => $buildParams];
    }

    private function verifyModifyhunter($context)
    {
        $info = XsAgencyHunterGiftBag::findFirst($context->id);

        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, "数据不存在");
        }
        $info = $info->toArray();
        $bagModel = XsGiftBag::findFirst($info['gb_id']);
        if ($context->deleted != XsAgencyHunterGiftBag::DELETED_YES) {
            if ($info['deleted'] == XsAgencyHunterGiftBag::DELETED_YES || $info['expire_time'] < time()) {
                throw new ApiException(ApiException::MSG_ERROR, "只有生效的数据才允许修改");
            }

            //礼包总数量
            if ($context->num < $info['used_num']) {
                throw new ApiException(ApiException::MSG_ERROR, "礼包数量不可小于已发放的数量");
            }

            $this->checkGiftBagExistsCoupon($bagModel->reward_json);
        } else {
            if ($info['status'] == XsAgencyHunterGiftBag::HAVE_AUDIT_STATUS) {
                throw new ApiException(ApiException::MSG_ERROR, '审核中不可删除');
            }
        }

        if (!$bagModel) {
            throw new ApiException(ApiException::MSG_ERROR, "礼包不存在, 不允许修改当前数据");
        }

        if ($bagModel->status != XsGiftBag::STATUS_VALID) {
            throw new ApiException(ApiException::MSG_ERROR, "礼包已失效, 不允许修改当前数据");
        }

        return $info;
    }

    /**
     * 验证礼包中是否存在优惠券
     * @param string $rewardJson
     * @return void
     */
    private function checkGiftBagExistsCoupon(string $rewardJson): void
    {
        if (empty($rewardJson)) {
            return;
        }
        $rewardArr = @json_decode($rewardJson, true);
        $types = array_column($rewardArr, 'gb_type');
        if (in_array(XsGiftBag::REWARD_GAME_COUPON, $types)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前礼包内含有优惠券不可修改数量，请重新下发');
        }
        return;
    }

    /**
     * 礼包存在优惠券时需拼接上优惠券下发ID
     * 格式化礼包下发审核状态
     * @param int $status
     * @param int $couponIssuedId
     * @return string
     */
    private function formatAuditStatus(int $status, int $couponIssuedId): string
    {
        $auditContext = XsAgencyHunterGiftBag::$auditStatus[$status] ?? '';

        if ($status && $couponIssuedId) {
            $auditContext .= '（优惠券下发ID：' . $couponIssuedId . '）';
        }

        return $auditContext;
    }

    public function importBag(array $params, int $adminId)
    {
        $data = $params['data'] ?? [];
        if (empty($data)) {
            throw new ApiException(ApiException::MSG_ERROR, '导入数据为空');
        }

        $duplicates = [];
        foreach ($data as $item) {
            $key = $item['uid'] . ':' . $item['gb_id'];
            if (in_array($key, $duplicates)) {
                throw new ApiException(ApiException::MSG_ERROR, '导入记录中存在重复：' . $key);
            }

            $duplicates[] = $key;
        }

        $this->validateUid(Helper::arrayFilter($data, 'uid'));

        $this->validateGiftBag($data);

        $this->addBatch($data, $adminId, '迎新礼包下发[批量，无需在此审核]');

        return (bool)$this->couponData;
    }

    private function addBatch($data, $adminId, $note)
    {
        $data = array_map(function ($item) {
            return [
                'uid'       => (int)$item['uid'],
                'gb_id'     => (int)$item['gb_id'],
                'valid_day' => (int)$item['valid_day'],
                'num'       => (int)$item['num'],
                'status'    => 0,
            ];
        }, $data);
        $params = [
            'bags'    => $data,
            'status'  => $this->couponData ? XsBatchAgencyHunterGiftBag::STATUS_AUDIT_WAIT : XsBatchAgencyHunterGiftBag::STATUS_AUDIT_NO,
            'creator' => $adminId,
        ];
        list($res, $id) = (new PsService())->createWelcomgifthunterBatch($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $id);
        }

        $this->addCouponIssuedDataNew($adminId, 0, $id, $note);
    }

    private function addCouponIssuedDataNew(int $adminId, int $agbId = 0, int $batchId = 0, string $note = ''): void
    {
        if ($this->couponData) {
            $now = time();
            foreach ($this->couponData as &$item) {
                $item = array_merge($item, [
                    'issued_type'    => XsstCouponIssued::ISSUED_TYPE,
                    'uid_bigarea_id' => $item['bigarea_id'],
                    'expire_time'    => 0,
                    'note'           => $note,
                    'audit_status'   => XsstCouponIssued::AUDIT_WAIT,
                    'created_id'     => $adminId,
                    'created_at'     => $now,
                    'agb_id'         => $agbId,
                    'batch_id'       => $batchId,
                ]);
            }
            XsstCouponIssued::addBatch(array_values($this->couponData));
        }
    }


    public function addByCondition(array $params): array
    {
        $gbid = $params['gb_id'];
        $bag = XsGiftBag::findOne($gbid);

        if (!$bag) {
            throw new ApiException(ApiException::MSG_ERROR, '礼包不存在');
        }

        $rewardArr = @json_decode($bag['reward_json'], true);
        foreach ($rewardArr as $reward) {
            if (in_array($reward['gb_type'], XsGiftBag::$couponList)) {
                throw new ApiException(ApiException::MSG_ERROR, '按条件发放功能不允许发放包含游戏优惠券的迎新礼包，请使用批量发放功能进行操作');
            }
        }

        $data = [
            'gb_id'      => (int)$params['gb_id'],
            'valid_day'  => (int)$params['valid_day'],
            'bigarea_id' => (int)$params['bigarea_id'],
            'type'       => (int)$params['type'],
            'num'        => (int)$params['num'],
            'status'     => 0,
            'creator'    => (int)$params['admin_uid'],
        ];
        set_time_limit(60);
        list($res, $id) = (new PsService())->createWelcomgifthunterBatchByCondition($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function createBroker(&$params): void
    {
        $bid = $params['bid'] ?? 0;

        // 创建公会和创建挖猎人要调用不同接口
        if ($bid) {
            $params['send_user_type'] = XsAgencyHunterGiftBag::SEND_USER_TYPE__BROKER_OWNER;
            $params['admin_uid'] = $params['operate_uid'];
            $this->addByCrowd($params);
        } else {
            $this->createhunter($params);
        }

    }

    public function addByCrowd(array $params): array
    {
        $bidArr = Helper::formatIdString($params['bid'] ?? '');
        $bidFileArr = Helper::formatIdString($params['bid_file'] ?? '');
        $gbId = (int)$params['gb_id'];
        $validDay = (int)$params['valid_day'];
        $sendUserType = (int)$params['send_user_type'];
        $num = (int)$params['num'];
        $bigAreaId = (int)($params['big_area_id'] ?? 0);
        $uid = (int)($params['big_area_id'] ?? 0);

        if ($bidArr && $bidFileArr) {
            throw new ApiException(ApiException::MSG_ERROR, '公会ID和上传公会ID为互斥关系');
        }

        if (empty($bidArr) && empty($bidFileArr)) {
            throw new ApiException(ApiException::MSG_ERROR, '公会ID和上传公会ID必须上传一个');
        }
        $bidNewArr = $bidArr ?: $bidFileArr;

        if ($bigAreaId && $uid) {
            $this->sendUidBigAreaMap = [$uid => $bigAreaId];
        } else {
            $this->validateBroker($bidNewArr, $sendUserType);
        }
        $data = [];
        foreach ($this->sendUidBigAreaMap as $uid => $bigAreaId) {
            $data[] = [
                'uid'       => $uid,
                'gb_id'     => $gbId,
                'num'       => $num,
                'valid_day' => $validDay,
            ];
        }

        $this->validateGiftBag($data);
        $rpcData = [
            'gb_id'          => $gbId,
            'send_user_type' => $sendUserType,
            'object_ids'     => implode(',', $bidNewArr),
            'valid_day'      => $validDay,
            'num'            => $num,
            'status'         => $this->couponData ? 1 : 0,
            'creator'        => $params['admin_uid'],
        ];
        list($res, $id) = (new PsService())->batchAgencyHunterGiftBagByUserType($rpcData);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, "API错误，原因:" . $id);
        }

        $this->addCouponIssuedDataNew($params['admin_uid'], 0, $id, '迎新礼包下发[按人群发放，无需在此审核]');

        return ['id' => $id, 'after_json' => $rpcData];
    }

    /**
     * 验证公会数据
     * @param array $bidArr
     * @param int $sendUserType
     * @return $this
     * @throws ApiException
     */
    private function validateBroker(array $bidArr, int $sendUserType): void
    {
        if (count($bidArr) > 500) {
            throw new ApiException(ApiException::MSG_ERROR, '输入的公会id数量限制为500');
        }

        $brokerList = XsBroker::getBrokerBatch($bidArr, ['bid, creater']);
        $diffBidArr = array_diff($bidArr, array_column($brokerList, 'bid'));

        if ($diffBidArr) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('输入的公会id:%s不存在', implode(',', $diffBidArr)));
        }

        $uidArr = [];
        if ($sendUserType == XsAgencyHunterGiftBag::SEND_USER_TYPE__BROKER_OWNER) {
            $uidArr = Helper::arrayFilter($brokerList, 'creater');
        } else if ($sendUserType == XsAgencyHunterGiftBag::SEND_USER_TYPE__BROKER_ADMIN) {
            $uidArr = XsBrokerAdmin::getListByBidArr($bidArr);
        }

        $uidArr && $this->sendUidBigAreaMap = XsUserBigarea::getUserBigareasChunk($uidArr);
    }

    private function validateGiftBag(array $data)
    {
        $gbids = array_values(array_unique(array_column($data, 'gb_id')));
        $bags = XsGiftBag::findByIds($gbids, 'id,status,reward_json');
        if ($diff = array_diff($gbids, array_column($bags, 'id'))) {
            throw new ApiException(ApiException::MSG_ERROR, '礼包ID不存在：' . implode(',', $diff));
        }

        $unvalidBag = array_filter($bags, function ($bag) {
            return $bag['status'] != XsGiftBag::STATUS_VALID;
        });

        if ($unvalidBag) {
            throw new ApiException(ApiException::MSG_ERROR, "礼包已失效：" . implode(',', array_column($unvalidBag, 'id')));
        }

        $bagCoupons = [];
        foreach ($bags as $bag) {
            if (empty($bag['reward_json'])) {
                continue;
            }
            $rewardArr = @json_decode($bag['reward_json'], true);
            foreach ($rewardArr as $reward) {
                if (in_array($reward['gb_type'], XsGiftBag::$couponList)) {
                    $bagCoupons[$bag['id']] = [
                        'id'  => $reward['reward_id'],
                        'num' => $reward['num'],
                    ];
                }
            }
        }
        if ($bagCoupons) {
            $bigareaIds = array_values($this->sendUidBigAreaMap);

            $couponAreaManages = XsstCouponAreaManage::getListByWhere([['bigarea_id', 'in', $bigareaIds]], 'bigarea_id,amount');
            $couponAreaManages = array_column($couponAreaManages, 'amount', 'bigarea_id');

            $bigareaWaitPrices = XsstCouponIssued::getListByWhere([['bigarea_id', 'in', $bigareaIds], ['audit_status', '=', XsstCouponIssued::AUDIT_WAIT]], 'bigarea_id,SUM(price) AS price', '', 0, 0, 'bigarea_id');
            $bigareaWaitPrices = array_column($bigareaWaitPrices, 'price', 'bigarea_id');

            $coupons = XsCoupon::findByIds(array_column($bagCoupons, 'id'), 'id,amount');
            $coupons = array_column($coupons, 'amount', 'id');
            $bigArea = XsBigarea::getAllNewBigArea();
            foreach ($data as $item) {
                $uid = $item['uid'];
                $gbid = $item['gb_id'];
                $num = $item['num'];

                $bigareaId = $this->sendUidBigAreaMap[$uid];

                if (!isset($couponAreaManages[$bigareaId])) {
                    throw new ApiException(ApiException::MSG_ERROR, '礼包内存在优惠券且' . $bigArea[$bigareaId] . '未配置，请移步优惠券管理-大区账户管理进行配置');
                }

                $bagCoupon = $bagCoupons[$gbid] ?? [];
                if ($bagCoupon) {
                    $couponAmount = $coupons[$bagCoupon['id']] ?? 0;
                    $rewardNum = $bagCoupon['num'];

                    $price = $couponAmount * $rewardNum * $num;

                    $waitPrice = $bigareaWaitPrices[$bigareaId] ?? 0;

                    $couponAreaMount = $couponAreaManages[$bigareaId];

                    if ($couponAreaMount < 0 || $couponAreaMount < $price + $waitPrice) {
                        throw new ApiException(ApiException::MSG_ERROR, '礼包内存在优惠券且' . $bigArea[$bigareaId] . '可用余额不足');
                    }

                    $couponAreaManages[$bigareaId] -= $price;

                    $this->couponData[$uid] = [
                        'bigarea_id' => $bigareaId,
                        'uid'        => $uid,
                        'coupon_id'  => $bagCoupon['id'],
                        'num'        => $num * $rewardNum,
                        'amount'     => $couponAmount,
                        'price'      => $price,
                    ];
                }
            }
        }
    }


    public function getBigareaMap()
    {
        $bigareas = XsBigarea::getAreaList();
        $bigareas = array_column($bigareas, 'cn_name', 'id');
        return StatusService::formatMap($bigareas, 'label,value');
    }

    public function getTypeMap()
    {
        $map = [1 => '按照礼包数量发放', 2 => '按照礼包数量补齐'];
        return StatusService::formatMap($map, 'label,value');
    }

    public function getSendUserTypeMap()
    {
        return StatusService::formatMap(XsAgencyHunterGiftBag::$sendUserTypeMap, 'label,value');
    }

    public function getVipDayMap()
    {
        $map = [
            30 => '30',
        ];
        return StatusService::formatMap($map);
    }

    public function getVipTypeMap()
    {
        $map = [
            '0' => '直接生效',
            '1' => '用户手动可转赠',
            '2' => '用户手动不可转赠',
        ];
        return StatusService::formatMap($map);
    }


}
