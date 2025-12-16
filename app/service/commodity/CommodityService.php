<?php
/**
 * 物品列表管理
 */

namespace Imee\Service\Commodity;

use Imee\Comp\Common\Redis\RedisSimple;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsCommodityVerify;
use Imee\Models\Xsst\XsstCommodityOperationLog;
use Imee\Models\Config\BbcBenefitPool;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsCommodityGroup;
use Imee\Models\Xs\XsCommodityProperty;
use Imee\Models\Xs\XsCommodityPropertyAdmin;
use Imee\Models\Xs\XsCommoditySend;
use Imee\Models\Xs\XsCommodityTag;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserTitleConfigNew;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class CommodityService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new RedisSimple();
    }

    public function getListAndTotal($params, $order = '', $page = 0, $pageSize = 0): array
    {
        $filter = [];
        $filter[] = ['app_id', '=', APP_ID];
        //物品cid对应审核表ocid
        if (!empty($params['cid'])) {
            if (is_array($params['cid'])) {
                $filter[] = ['ocid', 'IN', $params['cid']];
            } else {
                $filter[] = ['ocid', '=', $params['cid']];
            }
        }
        if (!empty($params['id'])) {
            $filter[] = ['cid', '=', $params['id']];
        }

        //spuid
        if (!empty($params['group_id']) && empty($params['group_name'])) {
            $filter[] = ['group_id', '=', $params['group_id']];
        }
        //spu名称
        if (!empty($params['group_name'])) {
            $groupList = $this->getCommodityGroupIdsByGroupName(APP_ID, $params['group_name']);
            $groupIds = array_keys($groupList);
            if (!empty($params['group_id'])) {
                $groupIds = array_merge($groupIds, [$params['group_id']]);
            }
            if ($groupIds) {
                $filter[] = ['group_id', 'in', $groupIds];
            }
        }
        if (!empty($params['name'])) {
            $filter[] = ['name', 'like', $params['name']];
        }

        if (!empty($params['type'])) {
            if (is_array($params['type'])) {
                $filter[] = ['type', 'IN', $params['type']];
            } else {
                $filter[] = ['type', '=', $params['type']];
            }
        }
        //审核状态 0待审核，1审核通过，2审核不通过
        if (isset($params['state']) && $params['state'] != -1) {
            $filter[] = ['state', '=', $params['state']];
        }
        //从礼盒中开 头像框、坐骑能否在开箱子中开出来 默认0 不可以 1可以
        if (isset($params['can_opened_by_box']) && $params['can_opened_by_box'] != -1) {
            $filter[] = ['can_opened_by_box', '=', $params['can_opened_by_box']];
        }
        //物品是否能够赠送 1代表可以 0代表不可以 默认可以赠送
        if (isset($params['can_give']) && $params['can_give'] != -1) {
            $filter[] = ['can_give', '=', $params['can_give']];
        }
        //售卖状态：0非售卖,1商城售卖,2金币商城,3碎片商城,4联盟银币商城,5联盟特权商城
        if (isset($params['saling_on_shop']) && is_numeric($params['saling_on_shop'])) {
            $filter[] = ['saling_on_shop', '=', $params['saling_on_shop']];
        }
        //财富类型  money 对应 金豆， coin 对应家族钻。 piece， union_god， union_silver 没有用到
        //1 伴伴币 * 20 = veeka 金豆 = veeka 家族钻
        if (!empty($params['money_type'])) {
            if (is_array($params['money_type'])) {
                $filter[] = ['money_type', 'IN', $params['money_type']];
            } else {
                $filter[] = ['money_type', '=', $params['money_type']];
            }
        }

        if (!empty($params['start_time'])) {
            $filter[] = ['dateline', '>=', strtotime($params['start_time'])];
        }

        if (!empty($params['end_time'])) {
            $filter[] = ['dateline', '<=', strtotime($params['end_time'] . ' 23:59:59')];
        }

        $priceLevel = array_get($params, 'price_level', []);
        $grantLimit = array_get($params, 'grant_limit', []);
        $couponType = array_get($params, 'coupon_type', []);
        $period = array_get($params, 'period');
        $periodHour = array_get($params, 'period_hour');

        is_numeric($period) && $filter[] = ['period', '=', $period];
        is_numeric($periodHour) && $filter[] = ['period_hour', '=', $periodHour];

        //购买限制搜索
        if (!empty($grantLimit)) {
            $grantLimit = is_array($grantLimit) ? $grantLimit : explode(',', $grantLimit);
            if (in_array('nobility', $grantLimit)) {
                $grantLimit[] = 'title';
            }
            $propertyList = XsCommodityPropertyAdmin::getListByWhere([['grant_limit', 'in', $grantLimit]], 'cid');
            $propertyList = array_column($propertyList, 'cid');

            if (empty($propertyList)) {
                $filter[] = ['cid', '=', 0];
            } else {
                $filter[] = ['cid', 'in', $propertyList];
            }
        }

        //优惠券类型
        if (!empty($couponType)) {
            $couponType = is_array($couponType) ? $couponType : explode(',', $couponType);
            $filter[] = ['coupon_type', 'IN', $couponType];
        }

        $searchCid = null;
        foreach ($filter as $k => $v) {
            if (isset($v[0]) && $v[0] == 'cid') {
                if ($v[2] == 0) {
                    $searchCid = [];
                    break;
                }
                $v[2] = is_array($v[2]) ? $v[2] : [$v[2]];
                $searchCid = array_intersect(is_array($searchCid) ? $searchCid : $v[2], $v[2]);
                unset($filter[$k]);
            }
        }
        foreach ($filter as $k => $v) {
            if (isset($v[0]) && $v[0] == 'cid') {
                unset($filter[$k]);
            }
        }
        if (is_array($searchCid)) {
            if (count($searchCid)) {
                $filter[] = ['cid', 'in', array_values($searchCid)];
            } else {
                $filter[] = ['cid', '=', 0];
            }
        }

        $filter = array_values($filter);
        $result = XsCommodityAdmin::getListAndTotal($filter, '*', $order, $page, $pageSize);

        if (!isset($groupList)) {
            $groupIds = array_column($result['data'], 'group_id');
            $groupIds = array_filter($groupIds);
            $groupIds = array_unique($groupIds);
            $groupIds = array_values($groupIds);
            $groupList = $this->getCommodityGroupListByGroupIds($groupIds);
        }
        $allCid = array_column($result['data'], 'cid');
        $propertyInfoLists = XsCommodityPropertyAdmin::getListByWhere([['cid', 'in', $allCid]]);

        $propertyInfoGrantLimitLists = array_column($propertyInfoLists, 'grant_limit', 'cid');
        $propertyInfoGrantWay = array_column($propertyInfoLists, 'grant_way', 'cid');

        foreach ($result['data'] as &$val) {
            //$val['price_level'] = array_get(BbcCommodityPriceLevel::$level, $this->getCommodityPriceLevel($val), '-');
            $val['ext_id'] = $this->getLoadExtIdName($val['ext_id'], $val['type'], $val['coupon_type']);

            $_grantLimit = array_get($propertyInfoGrantLimitLists, $val['cid'], '');
            if ($_grantLimit == 'title') {
                $val['grant_limit'] = '指定爵位';
            } else {
                $val['grant_limit'] = array_get(XsCommodityAdmin::$grantLimit, $_grantLimit, '-');
            }

            $val['grant_way'] = array_get($propertyInfoGrantWay, $val['cid'], '');
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i:s', $val['dateline']) : '';
            $val['group_name'] = $groupList[$val['group_id']] ?? '';
            $val['type'] = XsCommodityAdmin::$typeMap[$val['type']] ?? $val['type'];
            $val['saling_on_shop'] = XsCommodityAdmin::$salingShop[$val['saling_on_shop']] ?? '';
            $val['money_type'] = XsCommodityAdmin::$moneyType[$val['money_type']] ?? '';
            $val['sub_type'] = $this->getCommoditySubTypeName($val['type'], $val['sub_type']);
            $val['price'] /= 100;
            $val['image'] = Helper::getHeadUrl($val['image']);
            $val['image_bg'] = Helper::getHeadUrl($val['image_bg']);
            $val['startline'] = $val['startline'] ? date('Y-m-d H:i:s', $val['startline']) : '';
            $val['endline'] = $val['endline'] ? date('Y-m-d H:i:s', $val['endline']) : '';
        }
        return $result;
    }

    public function getListByWhere($params, $field = ['cid', 'ocid', 'name'], $order = 'cid desc', $limit = 0): array
    {
        $filter = [];
        $filter[] = [];
        //审核状态 0待审核，1审核通过，2审核不通过
        if (isset($params['state'])) {
            $filter[] = ['state', '=', $params['state']];
        }
        $field = implode(',', $field);
        return XsCommodityAdmin::getListByWhere($filter, $field, $order, $limit);
    }

    /**
     * 物品发放下拉
     * @return array
     */
    public function getCommodity(): array
    {
        $condition = [];
        $condition[] = ['app_id', '=', APP_ID];
        $condition[] = ['state', '=', XsCommodityAdmin::STATE_PASS];
        //$condition[] = ['type', '!=', 'title'];//限制发放爵位类物品
        $data = XsCommodityAdmin::getListByWhere($condition, 'cid,name', 'cid desc');
        if (!empty($data)) {
            $data = array_map(function ($v) {
                $v['name'] = $v['cid'] . '-' . $v['name'];
                return $v;
            }, $data);
            $data = array_column($data, 'name', 'cid');
            return StatusService::formatMap($data, StatusService::PARAMS_FORMAT);
        }
        return [];
    }

    /**
     * 返回爵位类物品后台cid
     * @return array
     */
    public function getTitleCommodityCidArr(): array
    {
        $condition = [];
        $condition[] = ['app_id', '=', APP_ID];
        $condition[] = ['state', '=', XsCommodityAdmin::STATE_PASS];
        $condition[] = ['type', '=', 'title'];
        $titleCidArr = XsCommodityAdmin::getListByWhere($condition, 'cid');
        return array_column($titleCidArr, 'cid');
    }

    public static function getCommodityCidByType($type): array
    {
        $condition = [];
        $condition[] = ['app_id', '=', APP_ID];
        $condition[] = ['state', '=', XsCommodityAdmin::STATE_PASS];
        $condition[] = ['type', '=', $type];
        $titleCidArr = XsCommodityAdmin::getListByWhere($condition, 'cid');
        return array_column($titleCidArr, 'cid');
    }

    public function getCommodityGroupIdsByGroupName($appId, $groupName): array
    {
        $list = XsCommodityGroup::getListByWhere([['app_id', '=', $appId], ['group_name', 'like', $groupName]], 'group_id, group_name');
        return array_column($list, 'group_name', 'group_id');
    }

    public function getCommodityGroupListByGroupIds($groupIds): array
    {
        if (!$groupIds) {
            return [];
        }
        $filter = [];
        $filter[] = ['group_id', 'in', $groupIds];
        $list = XsCommodityGroup::getListByWhere($filter);
        return array_column($list, 'group_name', 'group_id');
    }

    private function getValidParams($params): array
    {
        $cid = (int)array_get($params, 'cid', 0);
        $type = array_get($params, 'type', '');
        $subType = array_get($params, 'sub_type', '');
        $price = array_get($params, 'price', 0);
        $period = intval(array_get($params, 'period', 0));
        $periodHour = intval(array_get($params, 'period_hour', 0));
        $image = array_get($params, 'image', '');
        $couponType = trim(array_get($params, 'coupon_type', 'none'));
        $ductionMoney = intval(array_get($params, 'duction_money', 0));
        $grantLimit = trim(array_get($params, 'grant_limit', ''));
        $titleNew = (int)array_get($params, 'title_new', '');

        $extId = intval(array_get($params, 'ext_id', 0));
        $extName = trim(array_get($params, 'ext_name', ''));
        $salingOnShop = array_get($params, 'saling_on_shop', 0);
        $moneyType = array_get($params, 'money_type', '');
        $canOpenedByBox = (int)array_get($params, 'can_opened_by_box', 0);
        $panelImage = array_get($params, 'panel_image', '');

        //开始使用时间
        $startLine = trim(array_get($params, 'startline', ''));
        $endLine = trim(array_get($params, 'endline', ''));
        $startLine = $startLine ? strtotime($startLine) : 0;
        $endLine = $endLine ? strtotime($endLine) : 0;
        $weight = intval($params['weight'] ?? 0);
        $excludes = $params['excludes'] ?? [];

        $limitAddType = ['exp', 'box', 'key', 'defend', 'radio-defend'];
        if (in_array($type, $limitAddType)) {
            $typeStr = implode(' ', array_map(function ($t) {
                return XsCommodityAdmin::$typeMap[$t] ?? $t;
            }, $limitAddType));
            return [false, '目前' . $typeStr . ' 不支持新增'];
        }

        if (in_array($subType, ['VIP专属', '爵位专属', '特权']) && $canOpenedByBox == 1) {
            return [false, '特权礼物不支持从促销中开出'];
        }

        //物品类型为主页装扮时，panel_image图片必上传
        if (!$panelImage && in_array($type, ['decorate', 'effect', 'experience_voucher'])) {
            return [false, '物品类型为主页装扮、入场特效、体验券时，panel_image图片必上传'];
        }

        if ($price < 0) {
            return [false, '价格不允许为负数'];
        }

        if (in_array($type, ['header', 'mounts', 'bubble', 'effect', 'decorate', 'ring']) && empty($params['group_id'])) {
            return [false, '该物品类型，必须指定分组名'];
        }

        if (empty($image)) {
            return [false, '图片未上传'];
        }

        if ($extId && (in_array($type, ['gift', 'mounts']) || $couponType == 'gift' || in_array($subType, ['cq_experience_voucher', 'magic_experience_voucher']))) {
            $giftRes = XsGift::findOne($extId);
            if (!$giftRes) {
                return [false, '优惠券id数据有误，礼物表里无该记录'];
            }
            if ($excludes) {
                $giftExcludes = $giftRes['excludes'];
                if (!empty($giftExcludes)) {
                    $giftExcludes = explode(',', $giftExcludes);
                    foreach ($giftExcludes as $ge) {
                        if (!in_array($ge, $excludes)) {
                            return [false, '物品地区必须是礼物地区的子集'];
                        }
                    }
                }
            }

            $extName = array_get($giftRes, 'name', '');
        } elseif ($extName) {
            $extName = explode(',', $extName);
            $extName = trim(end($extName));
        }

        if (in_array($subType, ['cq_experience_voucher', 'magic_experience_voucher'])) {
            if (empty($giftRes)) {
                return [false, '优惠券id数据有误，礼物表里无该记录'];
            }
            if ($subType == 'magic_experience_voucher' && !in_array(13, explode(':', $giftRes['tag_ids']))) {
                return [false, '魔法礼物体验券必须是magic gift标签'];
            } elseif ($subType == 'cq_experience_voucher' && !in_array('finger_guess', explode(',', $giftRes['sub_display']))) {
                return [false, '猜拳体验券礼物需要finger_guess'];
            }
        }

        //优惠券物品id
        if (in_array($type, ['header', 'union_header'])) {
            if (!strstr($image, 'static/effect/')) {
                return [false, '头像框图片路径必须放置static/effect/下'];
            }
            $file = pathinfo($image);
            $extName = str_replace(['.jpg', '.png', '.jpeg'], '', $file['basename']);

            if (!$extName) {
                return [false, '图片未上传'];
            }

            $imageName = pathinfo($image)['filename'];
            if (strpos($extName, '.webp') > 0) {
                $extNameBk = str_replace('.webp', '', $extName);
            } else {
                $extNameBk = $extName;
            }
            if ($imageName != $extNameBk) {
                return [false, '请检查优惠券物品名称'];
            }
        }

        if (in_array($type, ['bubble', 'effect']) && empty($params['color'])) {
            return [false, '聊天气泡和入场特效必须选择颜色'];
        }

        if (!in_array($type, ['header', 'mounts', 'bubble', 'effect', 'decorate', 'ring']) && !empty($params['show_on_panel'])) {
            return [false, '此类型不可选在面板展示'];
        }
        if ($type == 'experience_voucher') {
            if (empty($params['image_bg'])) {
                return [false, '体验券的背景图必填'];
            }
            if (!$period && !$periodHour) {
                return [false, '体验券的有效期必填'];
            }
            if ($weight < 1) {
                return [false, '体验券的权重必填'];
            }
            if ($extId < 1) {
                return [false, '体验券礼物id没填'];
            }
        }

        $extra = [];
        //联盟宝箱需要有奖池
        if ($type == 'union_box') {
            if (empty($params['poolid'])) {
                return [false, '联盟宝箱必须选择奖池'];
            }
            $extra['poolid'] = (int)$params['poolid'];
        }

        if (in_array($type, ['effect', 'bubble'])) {
            if (!Helper::checkColor($params['color'])) {
                return [false, '颜色填写不正确！'];
            }
            $filedColor = $params['type'] . '_front_color';
            $extra[$filedColor] = $params['color'];
        }
        if (!empty($panelImage)) {
            $extra['panel_image'] = $panelImage;
        }
        //系列名称
        if (!empty($params['name_series'])) {
            $extra['name_series'] = $params['name_series'];
        }
        if ($subType == 'interactive_animations') {
            $extra['action'] = $params['action'];
        }

        if (in_array($type, ['header', 'union_header'])) {
            $imageName = pathinfo($image)['filename'];
            if (strpos($extName, '.webp') > 0) {
                $extNameBk = str_replace('.webp', '', $extName);
            } else {
                $extNameBk = $extName;
            }
            if ($imageName != $extNameBk) {
                return [false, '请检查优惠券物品名称'];
            }
        }

        if ($type == 'gift') {
            if ($extId < 1) {
                return [false, '礼物id没填'];
            }
            !isset($giftRes) && $giftRes = XsGift::findOne($extId);
            $giftPrice = round($giftRes['price'] * 100);
            if ($giftRes['gift_type'] == 'normal' && $moneyType == 'money' && $price != $giftPrice) {
                return [false, '物品价值与礼物价值不相等'];
            }
            if ($giftRes['gift_type'] == 'normal' && $moneyType != 'money') {
                return [false, '物品财富类型有误：gift_type=normal & 财富类型=星球币物品'];
            }
            if ($giftRes['gift_type'] == 'coin' && $moneyType != 'coin') {
                return [false, '物品财富类型与礼物财富类型不一致:gift_type=coin & 财富类型=金币物品'];
            }
        }

        if ($type == 'mounts' && $extId < 1) {
            return [false, '坐骑id没填'];
        }
        if ($type == 'title' && $extId < 1) {
            return [false, '爵位填写有误'];
        }

        //优惠券校验
        if ($type != 'coupon') {
            if ($couponType != 'none') {
                return [false, '非优惠券不需要填写优惠券类型'];
            }
            if ($ductionMoney > 0) {
                return [false, '非优惠券不需要填写优惠券金额'];
            }
        } else {
            // 判断优惠券的价格是否合理 不超过价格的百分之30
            //如果优惠金额与优惠物品没变就不校验
            $cidModel = $cid ? XsCommodityAdmin::findOne($cid) : [];
            $ductionMoneyOrigin = array_get($cidModel, 'duction_money', -1);
            $extIdOrigin = array_get($cidModel, 'ext_id', -1);

            if ($ductionMoneyOrigin != $ductionMoney || $extIdOrigin != $extId) {
                if (in_array($couponType, ['title', 'defend', 'radio-defend'])) {
                    // 这部分从xs_commodity里查
                    $filter = [];
                    $filter[] = ['type', '=', $couponType];
                    $filter[] = ['ext_id', '=', $extId];
                    $comRes = XsCommodity::findOneByWhere($filter);
                    if (empty($comRes)) {
                        return [false, '优惠券id数据有误，物品表里无该记录'];
                    }
                    if ($ductionMoney >= $comRes['price'] * 0.3) {
                        return [false, '优惠券金额超过上限，超过物品的30%'];
                    }
                } else {
                    // 这部分从xs_gift取
                    !isset($giftRes) && $giftRes = XsGift::findOne($extId);
                    if ($ductionMoney >= round(array_get($giftRes, 'price') * 30)) {
                        return [false, '优惠券金额超过上限，超过礼物的30%'];
                    }
                }
            }
        }
        $grantLimitRange = '';
        //指定范围
        if ($grantLimit) {
            if (in_array($grantLimit, ['vip', 'title', 'ka'])) {
                if (empty($params['limit_start']) && empty($params['limit_end'])) {
                    return [false, '请输入指定范围'];
                }
                if ($params['limit_start'] && $params['limit_end'] && $params['limit_start'] > $params['limit_end']) {
                    return [false, '指定范围不对'];
                }
            }
            $grantLimitRange = "{$params['limit_start']}:{$params['limit_end']}";

            if ($grantLimit == 'title') {
                $grantLimitRange .= ":{$titleNew}";
            }
        }

        if ($extra) {
            $extra = json_encode($extra);
            if (strlen($extra) > 255) {
                return [false, 'extra长度不够，请联系后台管理员'];
            }
        } else {
            $extra = '';
        }

        if (!empty($params['group_id'] ?? '')) {
            $commodity = XsCommodityAdmin::findOneByWhere([
                ['group_id', '=', $params['group_id']]
            ]);
            if ($commodity && $commodity['type'] != $type) {
                $groupType = XsCommodityAdmin::$typeMap[$commodity['type']];
                return [false, "录入的分组和物品类型不一致，请检查重试[分组物品类型：{$groupType}]"];
            }
        }

        $commodityRowData = [
            'type'              => $type,
            'price'             => $price,
            'period'            => $period,
            'period_hour'       => $periodHour,
            'only_newpay'       => intval($params['only_newpay'] ?? 0),
            'coupon_type'       => $couponType,
            'duction_money'     => $ductionMoney,
            'ext_id'            => $extId,
            'ext_name'          => $extName,
            'jump_page'         => trim($params['jump_page'] ?? '', "'"),
            'is_continue'       => intval($params['is_continue'] ?? 0),
            'can_opened_by_box' => $canOpenedByBox,
            'can_give'          => intval($params['can_give'] ?? 0),
            'saling_on_shop'    => $salingOnShop,
            'dateline'          => time(),
            'admin'             => $params['admin_id'],
            'mark'              => $params['mark'] ?? '',
            'money_type'        => $moneyType,
            'excludes'          => implode(',', $excludes),
            'description'       => array_get($params, 'description', ''),
            'image_bg'          => $params['image_bg'] ?? '',
            'tag_ids'           => $params['tag_ids'] ?? '',
            'sub_type'          => $params['sub_type'] ?? '',
            'group_id'          => intval($params['group_id'] ?? 0),
            'startline'         => $startLine,
            'endline'           => $endLine,
            'extra'             => $extra,
            'app_id'            => APP_ID,

            //property
            'duction_limit_min' => intval($params['duction_limit_min'] ?? 0),
            'duction_limit_max' => intval($params['duction_limit_max'] ?? 0),
            'duction_rate'      => intval($params['duction_rate'] ?? 0),
            'show_on_panel'     => intval($params['show_on_panel'] ?? 0),
            'grant_limit_range' => $grantLimitRange,
            'grant_way'         => $params['grant_way'] ?? '',
            'grant_limit'       => $grantLimit,
            'weight'            => intval($params['weight'] ?? 0),
        ];

        //其它大区名称
        foreach (XsCommodityAdmin::$nameBigarea as $key => $_) {
            if (!empty($commodityRowData[$key])) {
                continue;
            }
            $commodityRowData[$key] = $params[$key] ?? '';
        }

        //其它大区的图片
        foreach (XsCommodityAdmin::$imageBigarea as $key => $_) {
            if (!empty($commodityRowData[$key])) {
                continue;
            }
            $commodityRowData[$key] = $params[$key] ?? '';
        }

        return [true, $commodityRowData];
    }

    public function checkLimitUser(string $uid, int $appId): bool
    {
        if (empty($uid)) {
            throw new ApiException(ApiException::MSG_ERROR, '指定UID不能为空');
        }
        $uidArr = explode(',', trim(str_replace('，', ',', $uid), ','));
        if (count($uidArr) != count(array_filter($uidArr, 'intval'))) {
            throw new ApiException(ApiException::MSG_ERROR, '指定UID输入不正确');
        }
        if (count($uidArr) > 500) {
            throw new ApiException(ApiException::MSG_ERROR, '指定UID过多[最多500个]');
        }

        $res = XsUserProfile::getListByWhere([['uid', 'in', $uidArr]], 'app_id');
        if (count($res) != count($uidArr)
            || count(array_unique(array_column($res, 'app_id'))) != 1
            || $res[0]['app_id'] != $appId) {
            throw new ApiException(ApiException::MSG_ERROR, '指定UID填写有误');
        }
        return true;
    }

    public function add($params): array
    {
        $params = Helper::trimParams($params);
        unset($params['cid']);
        list($result, $commodityRowData) = $this->getValidParams($params);
        if (!$result) {
            return [false, $commodityRowData];
        }

        //新增物品
        $commodityRowData['state'] = XsCommodityAdmin::STATE_WAIT;
        list($result, $cid) = XsCommodityAdmin::add($commodityRowData);
        if (!$result) {
            return [false, $cid];
        }

        //新增物品属性
        list($result, $msg, $propertyRowData) = $this->addPropertyAdminData($cid, $commodityRowData);
        if (!$result) {
            return [false, $msg];
        }

        //新增物品记录日志
        $logData = [
            'commodity' => $commodityRowData,
            'property'  => $propertyRowData
        ];

        return $this->addLog($cid, $logData, XsstCommodityOperationLog::TYPE_ADD, $params['admin_id']);
    }

    public function edit($cid, $params): array
    {
        $params = Helper::trimParams($params);
        list($result, $commodityRowData) = $this->getValidParams($params);
        if (!$result) {
            return [false, $commodityRowData];
        }

        $rec = XsCommodityAdmin::findOne($cid, true);
        if (!$rec) {
            return [false, '当前数据不存在，请确认'];
        }

        if ($rec['type'] != $commodityRowData['type']) {
            return [false, '物品类型不支持修改'];
        }

        if ($rec['coupon_type'] != $commodityRowData['coupon_type']) {
            return [false, '优惠券类型不支持修改'];
        }

        if ($rec['duction_money'] != $commodityRowData['duction_money'] && $rec['type'] != 'coupon') {
            return [false, '优惠券价格只支持优惠券类型物品修改'];
        }

        if ($commodityRowData['money_type'] == 'bean') {
            $tmpRes = XsCommodityAdmin::findOneByWhere([
                ['ext_id', '=', $commodityRowData['ext_id']],
                ['period', '=', $commodityRowData['period']],
                ['cid', '<>', $cid],
            ]);
            if ($tmpRes) {
                return [false, '该有效期（天）下礼物物品已存在'];
            }
        }

        //判断哪些字段有更新
        $updateRowData = [];
        foreach ($rec as $key => $val) {
            if (!isset($commodityRowData[$key])) {
                continue;
            }
            if ($val != $commodityRowData[$key]) {
                $updateRowData[$key] = $commodityRowData[$key];
            }
        }

        //再看属性表是否有修改
        $recProperty = XsCommodityPropertyAdmin::findOne($cid, true);
        if ($recProperty) {
            $updatePropertyRowData = [];
            foreach ($recProperty as $key => $val) {
                if (!isset($commodityRowData[$key]) || $key == 'dateline') {
                    continue;
                }
                if ($val != $commodityRowData[$key]) {
                    $updatePropertyRowData[$key] = $commodityRowData[$key];
                }
            }
            if ($updatePropertyRowData) {
                $updatePropertyRowData['updateline'] = $commodityRowData['dateline'];
                list($result, $msg) = XsCommodityPropertyAdmin::edit($cid, $updatePropertyRowData);
            } else {
                $result = true;
            }
        } else {
            list($result, $msg, $updatePropertyRowData) = $this->addPropertyAdminData($cid, $commodityRowData);
        }
        if (!$result) {
            return [false, $msg];
        }

        //如果有更新需要设置为待审核
        if ($updateRowData || $updatePropertyRowData) {
            $updateRowData['state'] = XsCommodityAdmin::STATE_WAIT;
            list($result, $msg) = XsCommodityAdmin::edit($cid, $updateRowData);
            if (!$result) {
                return [false, $msg];
            }
        }

        //新增物品记录日志
        $logData = [
            'commodity' => $updateRowData,
            'property'  => $updatePropertyRowData,
        ];
        return $this->addLog($cid, $logData, XsstCommodityOperationLog::TYPE_UPDATE, $params['admin_id']);
    }

    /**
     * 添加物品属性
     * @param $cid
     * @param $params
     * @return array
     */
    public function addPropertyAdminData($cid, $params): array
    {
        $propertyRowData = [];
        if (!empty($params['grant_way'])) {
            $propertyRowData['grant_way'] = trim($params['grant_way']);
        }
        if (!empty($params['grant_limit'])) {
            $propertyRowData['grant_limit'] = trim($params['grant_limit']);
        }
        if (!empty($params['weight'])) {
            $propertyRowData['weight'] = (int)$params['weight'];
        }
        if (!empty($params['grant_limit_range'])) {
            $propertyRowData['grant_limit_range'] = trim($params['grant_limit_range']);
        }
        if (!empty($params['duction_rate'])) {
            $propertyRowData['duction_rate'] = (int)$params['duction_rate'];
        }
        if (!empty($params['duction_limit_min'])) {
            $propertyRowData['duction_limit_min'] = (int)$params['duction_limit_min'];
        }
        if (!empty($params['duction_limit_max'])) {
            $propertyRowData['duction_limit_max'] = (int)$params['duction_limit_max'];
        }
        if (!empty($params['show_on_panel'])) {
            $propertyRowData['show_on_panel'] = (int)$params['show_on_panel'];
        }

        if (!empty($propertyRowData)) {
            $propertyRowData['cid'] = $cid;
            $propertyRowData['dateline'] = time();
            $propertyRowData['updateline'] = time();
            list($result, $msg) = XsCommodityPropertyAdmin::add($propertyRowData);
            if (!$result) {
                return [false, $msg, []];
            }
        }

        return [true, '', $propertyRowData];
    }

    /**
     * 添加物品操作日志
     * @param $cid
     * @param $data
     * @param $type 1新增 2修改
     * @param $adminId
     * @return array
     */
    public function addLog($cid, $data, $type, $adminId): array
    {
        $insert = [
            'cid'      => $cid,
            'admin'    => $adminId,
            'type'     => $type,
            'content'  => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'dateline' => time()
        ];
        return XsstCommodityOperationLog::add($insert);
    }

    public function getGroupInfo(int $groupId): array
    {
        $rec = XsCommodityGroup::findOne($groupId);
        $data = [];
        foreach (XsCommodityAdmin::$nameBigarea as $k => $v) {
            $kk = 'group_' . $k;
            $data[$k] = $rec[$kk] ?? '';
        }

        return $data;
    }

    public function getGroupList(string $keyword): array
    {
        if (!$keyword) {
            return [];
        }
        if (is_numeric($keyword)) {
            $data = XsCommodityGroup::findOne($keyword);
            if ($data) {
                $data = [$data];
            }
        } else {
            $data = XsCommodityGroup::getListByWhere([['group_name', 'like', $keyword]], 'group_id,group_name');
        }
        return $this->packGroupList($data);
    }

    /**
     * group_name映射name
     * @param $groupList
     * @return array
     */
    private function packGroupList($groupList): array
    {
        $data = [];
        $data[] = ['label' => '所有spu', 'value' => 0, 'type' => ''];

        foreach ($groupList as $val) {
            $data[] = ['label' => $val['group_id'] . '-' . $val['group_name'], 'value' => $val['group_id']];
        }

        return $data;
    }

    /**
     * v-k
     * @param $list
     * @return array
     */
    private function packLabel($list): array
    {
        $data = [];
        foreach ($list as $value => $label) {
            $data[] = ['label' => $label, 'value' => (string)$value];
        }
        return $data;
    }

    /**
     * k-v
     * @param $list
     * @return array
     */
    private function packLabelKv($list): array
    {
        $data = [];
        foreach ($list as $label => $value) {
            $data[] = ['label' => $label, 'value' => (string)$value];
        }
        return $data;
    }

    /**
     * 优惠券类型list
     * @param $type
     * @return array
     */
    public function getCouponTypeList($type): array
    {
        $list = XsCommodityAdmin::$couponType;
        if ($type != 'coupon') {
            $list = ['none' => '无'];
        } else {
            unset($list['none']);
        }
        return $this->packLabel($list);
    }

    public function getGiftInfo(int $gid): array
    {
        $rec = XsGift::findOne($gid);
        $data = [];
        foreach (XsCommodityAdmin::$nameBigarea as $k => $v) {
            $data[$k] = $rec[$k] ?? '';
        }
        $data['price'] = (string)(array_get($rec, 'price', 0) * 100);//填充价格
        $data['image'] = 'static/gift_big/' . $rec['id'] . '.png';//默认礼物的png图片

        return $data;
    }

    /**
     * 物品新增/编辑/详情下拉选项
     * @param $appId
     * @return array
     */
    public function getOptions($appId): array
    {
        ini_set('memory_limit', '256M');
        //分组列表
        /*$filter = [];
        $filter[] = ['app_id', '=', $appId];
        $groupList = XsCommodityGroup::getListByWhere($filter, 'group_id,group_name', 'group_id desc');
        $groupList = $this->packGroupList($groupList);*/

        //标签列表
        $filter = [];
        $filter[] = ['app_id', '=', $appId];
        $tagList = XsCommodityTag::getListByWhere($filter, 'id,name');
        array_unshift($tagList, ['id' => '', 'name' => '所有标签']);
        $tagList = array_column($tagList, 'name', 'id');

        //奖品池
        $pooList = $this->getPoolList($appId, BbcBenefitPool::STATUS_ON);
        $pooList = array_column($pooList, 'pool_name', 'id');

        //新爵位
        $titleList = XsUserTitleConfigNew::getAllTitleName();

        /*$filter = [['app_id', '=', $appId]];
        $giftList = XsGift::getListByWhere($filter, '*', 'id desc');
        $giftList = $this->packGiftList($giftList);*/

        return [
            //'group_list'             => $groupList,
            'type_list'              => $this->packLabel(XsCommodityAdmin::$typeMap),
            'bigarea_name_list'      => $this->packLabel($this->getNameFiledList()),
            'bigarea_desc_list'      => $this->packLabel($this->getDescriptionFiledList()),
            'bigarea_image_list'     => $this->packLabel($this->getImageFiledList()),
            'bigarea_list'           => $this->packLabel($this->getLanguageList()),
            'pool_list'              => $this->packLabel($pooList),
            'name_series_list'       => $this->packLabel(XsCommodityAdmin::getNameSeries()),
            'state_list'             => $this->packLabel(XsCommodityAdmin::$state),
            'grant_way_list'         => $this->packLabel(XsCommodityAdmin::$grantWay),
            'grant_limit'            => $this->packLabel(XsCommodityAdmin::$grantLimit),
            'saling_on_shop_list'    => $this->packLabel(XsCommodityAdmin::$salingShop),
            'money_list'             => $this->packLabel(XsCommodityAdmin::$moneyType),
            'can_opened_by_box_list' => $this->packLabel(XsCommodityAdmin::$canOpenedByBox),
            'can_give_list'          => $this->packLabel(XsCommodityAdmin::$canGive),
            'tag_list'               => $this->packLabel($tagList),
            'title_list'             => $this->packLabel($titleList),
            'action_list'            => $this->packLabel(XsCommodityAdmin::$actionList),
            'title_new'              => $this->packLabel(XsUserTitleConfigNew::getNewTitleLabel()),
            'coupon_type_list'       => $this->packLabel(XsCommodityAdmin::$couponType),
            //'gift_list'              => $giftList,
        ];
    }

    /**
     * 选择物品类型/优惠券类型
     * 下拉优惠券物品列表
     * coupon_type
     * @param $type
     * @param $couponType
     * @param $appId
     * @return array
     */
    public function getLoadExtId($type, $couponType, $appId): array
    {
        if (($type == 'coupon' && $couponType == 'title') || $type == 'title') {
            $data = XsUserTitleConfigNew::getAllTitleName();
        } elseif ($couponType == 'defend') {
            $data = XsCommodityAdmin::$defendList;
        } elseif ($couponType == 'radio-defend') {
            $data = XsCommodityAdmin::$radioDefendList;
        } elseif ($type == 'gift' || $couponType == 'gift') {
            $filter = [];
            $filter[] = ['display', '!=', 'mounts'];
            $filter[] = ['app_id', '=', $appId];
            $data = XsGift::getListByWhere($filter, 'id,name', 'id desc');
            $data = array_column($data, 'name', 'id');
        } elseif ($type == 'mounts') {
            $filter = [];
            $filter[] = ['display', '=', 'mounts'];
            $filter[] = ['app_id', '=', $appId];
            //$filter[] = ['deleted', '=', XsGift::DELETE_NO];
            $data = XsGift::getListByWhere($filter, 'id,name', 'id desc');
            $data = array_column($data, 'name', 'id');
        } elseif ($type == 'cq_experience_voucher') { //type = experience_voucher 时 传的sub_type
            $filter = [];
            $filter[] = ['sub_display', 'find_in_set', 'finger_guess'];
            $filter[] = ['app_id', '=', $appId];
            $filter[] = ['deleted', '=', XsGift::DELETE_NO];
            $data = XsGift::getListByWhere($filter, 'id,name', 'id desc');
            $data = array_column($data, 'name', 'id');
        } elseif ($type == 'magic_experience_voucher') { //type = experience_voucher 时 传的sub_type
            $filter = [];
            $filter[] = ['tag_ids', 'like', '13'];
            $filter[] = ['app_id', '=', $appId];
            $filter[] = ['deleted', '=', XsGift::DELETE_NO];
            $data = XsGift::getListByWhere($filter, 'id,name', 'id desc');
            $data = array_column($data, 'name', 'id');
        } else {
            $data = [];
        }

        foreach ($data as $key => &$val) {
            $val = $key . ', ' . $val;
        }

        return $this->packLabel($data);
    }

    public function getLoadExtIdName($extId, $type, $couponType): string
    {
        if (!$extId) {
            return '~';
        }
        if (($type == 'coupon' && $couponType == 'title') || $type == 'title') {
            return $extId . '-' . array_get(XsUserTitleConfigNew::getAllTitleName(), $extId, '~');
        } elseif ($couponType == 'defend') {
            return $extId . '-' . array_get(XsCommodityAdmin::$defendList, $extId, '~');
        } elseif ($couponType == 'radio-defend') {
            return $extId . '-' . array_get(XsCommodityAdmin::$radioDefendList, $extId, '~');
        } elseif (in_array($type, ['gift', 'mounts']) || $couponType == 'gift') {
            return $extId . '-' . array_get(XsGift::findOne($extId), 'name', '~');
        } else {
            return $extId . '- ~';
        }
    }

    /**
     * 根据物品类型获取相关下拉属性
     * @param $type
     * @return array
     */
    public function getOptinsByType($type): array
    {
        //控制 是否在礼物面板显示
        $showOnPanel = 0;
        if (in_array($type, ['header', 'mounts', 'effect', 'decorate', 'bubble', 'ring'])) {
            $showOnPanel = 1;
        }

        //控制 是否在礼物面板显示
        $subtypes = XsCommodityAdmin::getSubType($type);
        return [
            'show_on_panel' => $showOnPanel,
            'sub_type'      => $this->packLabel($subtypes)
        ];
    }

    public function getDisabledByType(string $type): array
    {
        return XsCommodityAdmin::getDisabledMap($type);
    }

    public function getInfoOption(int $cid): array
    {
        $data = $this->getEditInfo($cid);
        if (!$data) {
            return [];
        }

        $options = $this->getOptinsByType($data['type']);
        $couponTypeData = $this->getCouponTypeList($data['type']);

        $loadExtType = $data['type'];
        if ($data['sub_type'] == 'cq_experience_voucher' || $data['sub_type'] == 'magic_experience_voucher') {
            $loadExtType = $data['sub_type'];
        }
        $extIdData = $this->getLoadExtId($loadExtType, $data['coupon_type'] ?: 'none', APP_ID);

        return [
            'data'             => $data,
            'sub_type'         => $options['sub_type'],
            'show_on_panel'    => $options['show_on_panel'],
            'coupon_type_data' => $couponTypeData,
            'ext_id_data'      => $extIdData,
            'group_list'       => $this->getGroupList($data['group_id']),
        ];
    }

    /**
     * 物品详情-修改
     * @param $cid
     * @return array[]
     */
    public function getEditInfo($cid): array
    {
        $commodityInfo = XsCommodityAdmin::findOne($cid);
        if (!$commodityInfo) {
            return [];
        }
        $extra = [];
        if ($commodityInfo['extra']) {
            $extra = json_decode($commodityInfo['extra'], true);
            if (isset($extra['bubble_front_color'])) {
                $commodityInfo['color'] = $extra['bubble_front_color'];
            }
            if (isset($extra['effect_front_color'])) {
                $commodityInfo['color'] = $extra['effect_front_color'];
            }
        }
        //tag
        if (!empty($commodityInfo['tag_ids'])) {
            $commodityInfo['tag_ids'] = explode(':', $commodityInfo['tag_ids'])[0];
        }
        //property
        $propertyInfo = XsCommodityPropertyAdmin::findOne($cid);
        if ($propertyInfo) {
            if ($propertyInfo['grant_limit_range']) {
                $grantLimitRange = explode(':', $propertyInfo['grant_limit_range']);
                $propertyInfo['limit_start'] = $grantLimitRange[0] ? intval($grantLimitRange[0]) : 0;
                $propertyInfo['limit_end'] = isset($grantLimitRange[1]) ? intval($grantLimitRange[1]) : 0;
                $propertyInfo['title_new'] = isset($grantLimitRange[2]) ? intval($grantLimitRange[2]) : 0;
            }

            $grantLimit = array_get($propertyInfo, 'grant_limit');

            if (in_array($grantLimit, ['vip', 'ka', 'title'])) {
                $propertyInfo['limit_start'] = $grantLimitRange[0];
                $propertyInfo['limit_end'] = $grantLimitRange[1] ?? '';
            }
        }

        $nameFiledList = $this->getNameFiledList();
        $descFiledList = $this->getDescriptionFiledList();
        $imageFiledList = $this->getImageFiledList();

        $nameList = [];
        foreach ($nameFiledList as $key => $val) {
            $nameList[$key] = $commodityInfo[$key];
        }

        $descList = [];
        foreach ($descFiledList as $key => $val) {
            $descList[$key] = $commodityInfo[$key] ?? '';
        }

        $imageList = [];
        foreach ($imageFiledList as $key => $val) {
            $imageList[$key] = $commodityInfo[$key];
        }

        $baseInfo = [
            'cid'         => $cid,
            'ocid'        => $commodityInfo['ocid'],
            'group_id'    => $commodityInfo['group_id'],
            'type'        => $commodityInfo['type'],
            'sub_type'    => $commodityInfo['sub_type'],
            'price'       => $commodityInfo['price'],
            'state'       => (string)$commodityInfo['state'],
            'mark'        => $commodityInfo['mark'],
            'color'       => $commodityInfo['color'] ?? '',
            'panel_image' => $extra['panel_image'] ?? '',
            'image_bg'    => $commodityInfo['image_bg'] ?? '',
            'action'      => $extra['action'] ?? '',
            'excludes'    => $commodityInfo['excludes'] ? explode(',', $commodityInfo['excludes']) : [],
        ];

        $baseInfo2 = [
            'tag_ids'       => $commodityInfo['tag_ids'],
            'period'        => $commodityInfo['period'],
            'period_hour'   => $commodityInfo['period_hour'],
            'only_newpay'   => (string)$commodityInfo['only_newpay'],
            'coupon_type'   => $commodityInfo['coupon_type'],//优惠券类型
            'duction_money' => $commodityInfo['duction_money'],//优惠券金额（分）
            //类型为礼物优惠券时可使用的物品id
            //类型为守护、电台守护、爵位优惠券时表示物品的等级
            //类型为爵位时表示爵位的等级
            //类型为守护、电台守护时表示守护的等级
            //类型为礼物时表示礼物的id
            'ext_id'        => (string)$commodityInfo['ext_id'],//优惠券物品id
            'ext_name'      => $commodityInfo['ext_name'],//优惠券物品名称
        ];

        $extInfo = [
            'jump_page'         => trim($commodityInfo['jump_page'], "'"),
            'is_continue'       => (string)$commodityInfo['is_continue'],
            'can_opened_by_box' => (string)$commodityInfo['can_opened_by_box'],
            'saling_on_shop'    => (string)$commodityInfo['saling_on_shop'],
            'name_series'       => $extra['name_series'] ?? '',
            'weight'            => $propertyInfo['weight'] ?? '',
            'grant_limit'       => $propertyInfo['grant_limit'] ?? '',//购买限制
            'grant_limit_range' => $propertyInfo['grant_limit_range'] ?? '',//购买/发放限制区间
            'grant_way'         => $propertyInfo['grant_way'] ?? '',//获取方式
            'duction_rate'      => $propertyInfo['duction_rate'] ?? '',//折扣率(80表示8折)
            'duction_limit_min' => $propertyInfo['duction_limit_min'] ?? '',//折扣起售数量
            'duction_limit_max' => $propertyInfo['duction_limit_max'] ?? '',//折扣起售数量
            'show_on_panel'     => (string)($propertyInfo['show_on_panel'] ?? 0),//是否在礼物面板显示
            'startline'         => $commodityInfo['startline'] ? date('Y-m-d H:i:s', $commodityInfo['startline']) : '',
            'endline'           => $commodityInfo['endline'] ? date('Y-m-d H:i:s', $commodityInfo['endline']) : '',
            'can_give'          => (string)$commodityInfo['can_give'],
            'money_type'        => $commodityInfo['money_type'],
            'poolid'            => (string)($extra['poolid'] ?? ''),//联盟宝箱必须选择奖池 $type == 'union_box'
            'title_new'         => (string)($propertyInfo['title_new'] ?? ''),//爵位
            'limit_start'       => $propertyInfo['limit_start'] ?? '',
            'limit_end'         => $propertyInfo['limit_end'] ?? '',//指定范围
        ];

        return array_merge($imageList, $nameList, $descList, $baseInfo, $baseInfo2, $extInfo);
    }

    /**
     * 物品详情-详情/审核
     * @param $cid
     * @param string $type
     * @return array[]
     */
    public function getInfo($cid, string $type = 'detail'): array
    {
        $commodityInfo = XsCommodityAdmin::findOne($cid);
        if (!$commodityInfo) {
            return [];
        }
        $extra = [];
        if ($commodityInfo['extra']) {
            $extra = json_decode($commodityInfo['extra'], true);
            if (isset($extra['bubble_front_color'])) {
                $commodityInfo['color'] = $extra['bubble_front_color'];
            }
            if (isset($extra['effect_front_color'])) {
                $commodityInfo['color'] = $extra['effect_front_color'];
            }
        }
        //spu
        if (!empty($commodityInfo['group_id'])) {
            $groupInfo = XsCommodityGroup::findOne($commodityInfo['group_id']);
        }
        //tag
        if (!empty($commodityInfo['tag_ids'])) {
            $tagIds = explode(':', $commodityInfo['tag_ids']);
            $tagList = XsCommodityTag::findByIds($tagIds);
            $commodityInfo['tag_ids'] = $tagList ? array_column($tagList, 'name') : '';
        }
        //property
        $propertyInfo = XsCommodityPropertyAdmin::findOne($cid);
        if ($propertyInfo) {
            $grantLimitRange = explode(':', $propertyInfo['grant_limit_range']);
            if ($propertyInfo['grant_limit_range'] && $propertyInfo['grant_limit'] != 'level') {
                if (isset($grantLimitRange[2])) {
                    $propertyInfo['title'] = XsUserTitleConfigNew::findOne($grantLimitRange[2])['name'] ?? '';
                } else {
                    $propertyInfo['title'] = '';
                }
            }
        }

        $nameFiledList = $this->getNameFiledList();
        $descFiledList = $this->getDescriptionFiledList();
        $imageFiledList = $this->getImageFiledList();

        $groupList = [];
        if (!empty($groupInfo)) {
            foreach ($nameFiledList as $key => $val) {
                $kk = 'group_' . $key;
                $groupList[$val] = $groupInfo[$kk] ?? '';
            }
        }

        $nameList = [];
        foreach ($nameFiledList as $key => $val) {
            $nameList[$val] = $commodityInfo[$key];
        }

        $descList = [];
        foreach ($descFiledList as $key => $val) {
            $descList[$val] = $commodityInfo[$key] ?? '';
        }

        $imageList = [];
        foreach ($imageFiledList as $key => $val) {
            $imageList[$val] = Helper::getHeadUrl($commodityInfo[$key]);
        }

        $baseInfo = [
            'cid'         => $cid,
            'ocid'        => $commodityInfo['ocid'],
            'name_list'   => $this->packLabelKv($nameList),
            'desc_list'   => $this->packLabelKv($descList),
            'image_list'  => $this->packLabelKv($imageList),
            'group_id'    => $commodityInfo['group_id'],
            'group_list'  => $groupList ? $this->packLabelKv($groupList) : null,
            'type'        => XsCommodityAdmin::$typeMap[$commodityInfo['type']] ?? $commodityInfo['type'],
            'sub_type'    => $this->getCommoditySubTypeName($commodityInfo['type'], $commodityInfo['sub_type']),
            'price'       => $commodityInfo['price'] / 100,
            'state_name'  => XsCommodityAdmin::$state[$commodityInfo['state']] ?? $commodityInfo['state'],
            'state'       => (string)$commodityInfo['state'],
            'mark'        => $commodityInfo['mark'],
            'color'       => $commodityInfo['color'] ?? '',
            'panel_image' => Helper::getHeadUrl($extra['panel_image'] ?? ''),
            'image_bg'    => Helper::getHeadUrl($extra['image_bg'] ?? ''),
        ];

        $baseInfo2 = [
            'tag_ids'       => $commodityInfo['tag_ids'] ?? [],
            'period'        => $commodityInfo['period'],
            'period_hour'   => $commodityInfo['period_hour'],
            'only_newpay'   => XsCommodityAdmin::$isOnlyNewpay[$commodityInfo['only_newpay']],
            'coupon_type'   => XsCommodityAdmin::$couponType[$commodityInfo['coupon_type']] ?? $commodityInfo['coupon_type'],//优惠券类型
            'duction_money' => $commodityInfo['duction_money'],//优惠券金额（分）
            //类型为礼物优惠券时可使用的物品id
            //类型为守护、电台守护、爵位优惠券时表示物品的等级
            //类型为爵位时表示爵位的等级
            //类型为守护、电台守护时表示守护的等级
            //类型为礼物时表示礼物的id
            'ext_id'        => $commodityInfo['ext_id'],//优惠券物品id
            'ext_name'      => $commodityInfo['ext_name'],//优惠券物品名称
        ];

        $pooList = $this->getPoolList();
        $pooList = array_column($pooList, 'pool_name', 'id');

        $commodityInfo['excludes'] = $commodityInfo['excludes'] ? explode(',', $commodityInfo['excludes']) : [];
        $languageMap = $this->getLanguageList();
        $excludes = array_map(function ($lan) use ($languageMap) {
            return $languageMap[$lan] ?? $lan;
        }, $commodityInfo['excludes']);

        $extInfo = [
            'jump_page'         => trim($commodityInfo['jump_page'], "'"),
            'is_continue'       => XsCommodityAdmin::$isContinue[$commodityInfo['is_continue']],
            'can_opened_by_box' => XsCommodityAdmin::$canOpenedByBox[$commodityInfo['can_opened_by_box']],
            'saling_on_shop'    => XsCommodityAdmin::$salingShop[$commodityInfo['saling_on_shop']] ?? $commodityInfo['saling_on_shop'],
            'name_series'       => XsCommodityAdmin::getNameSeries()[$extra['name_series'] ?? ''] ?? '',
            'weight'            => $propertyInfo['weight'] ?? '',
            'grant_limit'       => XsCommodityAdmin::$grantLimit[$propertyInfo['grant_limit'] ?? ''] ?? '',//购买限制
            'grant_limit_range' => $propertyInfo['grant_limit_range'] ?? '',//购买/发放限制区间
            'grant_way'         => XsCommodityAdmin::$grantWay[$propertyInfo['grant_way'] ?? ''] ?? '',//获取方式
            'ka_uid'            => $propertyInfo['ka_uid'] ?? '',//指定uid
            'duction_rate'      => ($propertyInfo['duction_rate'] ?? 0) / 10,//折扣率(80表示8折)
            'duction_limit_min' => $propertyInfo['duction_limit_min'] ?? '',//折扣起售数量
            'duction_limit_max' => $propertyInfo['duction_limit_max'] ?? '',//折扣起售数量
            'show_on_panel'     => XsCommodityAdmin::$showOnPanel[$propertyInfo['show_on_panel'] ?? 0] ?? '',//是否在礼物面板显示
            'startline'         => $commodityInfo['startline'] ? date('Y-m-d H:i:s', $commodityInfo['startline']) : '',
            'endline'           => $commodityInfo['endline'] ? date('Y-m-d H:i:s', $commodityInfo['endline']) : '',
            'can_give'          => XsCommodityAdmin::$canGive[$commodityInfo['can_give']] ?? $commodityInfo['can_give'],
            'money_type'        => XsCommodityAdmin::$moneyType[$commodityInfo['money_type']] ?? $commodityInfo['money_type'],
            'poolid'            => $pooList[$extra['poolid'] ?? 0] ?? '',//联盟宝箱必须选择奖池 $type == 'union_box'
            'title'             => $propertyInfo['title'] ?? '',//爵位
            'excludes'          => implode(',', $excludes),
            'dateline'          => Helper::now($commodityInfo['dateline']),
        ];

        if ($commodityInfo['type'] == 'gift' && $commodityInfo['coupon_type'] == 'gift' && $commodityInfo['ext_id'] > 0 ||
            in_array($commodityInfo['type'], ['mounts', 'decorate', 'cq_experience_voucher', 'magic_experience_voucher'])
        ) {
            $gift = XsGift::findOneByWhere([['id', '=', $commodityInfo['ext_id']]], 'id,video_one,video_two');
            if ($gift) {
                $webpUrl = $gift['video_one'] ?: ('static/gift_big/' . $commodityInfo['ext_id'] . '.webp');
                $mp4Url = $gift['video_two'] ?: ('static/gift_big/' . $commodityInfo['ext_id'] . '.mp4');
                $extInfo['gift_webp'] = Helper::getHeadUrl($webpUrl) . '?time=' . time();
                $extInfo['gift_mp4'] = Helper::getHeadUrl($mp4Url) . '?time=' . time();
            }
        }

        $result = [
            'base_info'  => $baseInfo,
            'base_info2' => $baseInfo2,
            'ext_info'   => $extInfo,
        ];

        if ($type == 'review') {
            $result['data_diff'] = $this->getDataDiff($cid);
        }

        return $result;
    }

    /**
     * 审核对比数据
     * @param $cid
     * @return array
     */
    private function getDataDiff($cid): array
    {
        $data = [];
        $data['log'][0]['data'] = '更改数据';
        $data['log'][1]['data'] = '线上数据';
        $data['log'][0]['diff'] = '无';
        $data['log'][1]['diff'] = '无';
        $commodityAdmin = XsCommodityAdmin::findOne($cid);
        if (!empty($commodityAdmin)) {
            $commodity = XsCommodity::findOne($commodityAdmin['ocid']);
            if (!empty($commodity)) {
                $str = '';
                $strAdmin = '';
                foreach ($commodityAdmin as $k => $v) {
                    if (isset($commodity[$k]) && $v != $commodity[$k]) {
                        if ($k == 'cid') {
                            continue;
                        }
                        $strAdmin .= "{$k} = {$v}；";
                        $str .= "{$k} = {$commodity[$k]}；";
                    }
                }
                if (!empty($str)) {
                    $data['log'][0]['diff'] = $strAdmin;
                    $data['log'][1]['diff'] = $str;
                }
            }
        }
        $propertyAdmin = XsCommodityPropertyAdmin::findOne($cid);
        if (!empty($propertyAdmin)) {
            $property = XsCommodityProperty::findOne($propertyAdmin['ocid']);
            if (!empty($property)) {
                $str = '';
                $strAdmin = '';
                foreach ($propertyAdmin as $k => $v) {
                    if ($k == 'cid') {
                        continue;
                    }
                    if (isset($property[$k]) && $v != $property[$k]) {
                        $strAdmin .= "{$k} = {$v}；";
                        $str .= "{$k} = {$property[$k]}；";
                    }
                }
                if (!empty($str)) {
                    $data['log'][0]['diff'] .= $strAdmin;
                    $data['log'][1]['diff'] .= $str;
                }
            }
        }
        return $data;
    }

    /**
     * 奖品池
     * @param int $appid
     * @param  $status
     * @return array
     */
    public function getPoolList(int $appid = APP_ID, $status = null): array
    {
        $filter = [];
        if ($status !== null) {
            $filter[] = ['status', '=', $status];
        }
        //$filter[] = ['app_id', '=', $appid];
        return BbcBenefitPool::getListByWhere($filter, 'id,pool_name');
    }

    /**
     * 物品操作记录列表
     * @param $params
     * @param string $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getLogListAndTotal($params, string $order = '', int $page = 0, int $pageSize = 0): array
    {
        $filter = [];
        //物品cid对应审核表cid
        if (!empty($params['cid'])) {
            $filter[] = ['cid', '=', $params['cid']];
        }
        if (!empty($params['type'])) {
            $filter[] = ['type', '=', $params['type']];
        }
        $result = XsstCommodityOperationLog::getListAndTotal($filter, '*', $order, $page, $pageSize);
        $adminIds = array_column($result['data'], 'admin');
        $adminIds = array_unique($adminIds);
        $adminIds = array_values($adminIds);
        $adminList = CmsUser::getAdminUserBatch($adminIds);
        foreach ($result['data'] as &$val) {
            $val['admin_name'] = $adminList[$val['admin']]['user_name'] ?? '';
            $val['type_name'] = XsstCommodityOperationLog::$typeList[$val['type']] ?? $val['type'];
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i:s', $val['dateline']) : '';
        }
        return $result;
    }

    /**
     * 物品审核
     * @param $cid
     * @param $state
     * @param $adminId
     * @return array
     */
    public function review($cid, $state, $adminId): array
    {
        $rec = XsCommodityAdmin::findOne($cid, true);
        if (!$rec) {
            return [false, '当前数据不存在，请确认!'];
        }

        if ($rec['state'] == $state) {
            return [false, '状态没改!'];
        }

        // 经验值、礼盒、水晶、守护、电台守护、优惠券需要添加白名单后进行审核
        if (in_array($rec['type'], ['exp', 'box', 'key', 'defend', 'radio-defend', 'coupon'])) {
            $whitelist = BmsCommodityVerify::findOneByWhere([['cid', '=', $cid], ['expire_time', '>', time()]]);
            if (empty($whitelist)) {
                return [false, $rec['type'] . '类型的物品不支持审核,若需要,请联系负责人单独处理'];
            }
        }

        $ocid = $rec['ocid'];
        $updateCommodityAdmin = ['state' => $state];
        $commodityLog = [];
        $propertyLog = [];

        //后台表删除前端表没有到字段
        $recArr = $rec;
        unset($recArr['cid']);
        $recArr['cid'] = $recArr["ocid"];
        unset($recArr['ocid']);
        unset($recArr['state']);

        //审核通过
        if ($state == XsCommodityAdmin::STATE_PASS) {
            if (ENV != 'dev' && $recArr['admin'] == $adminId) {
                //return [false, '自己不能审核自己修改到物品！'];
            }

            $recArr['ext_id_more'] = $recArr['ext_id'];
            if ($recArr['type'] == 'coupon' && $recArr['coupon_type'] == 'key') {
                unset($recArr['ext_id_more']);
            }
            if ($recArr['cid'] > 0) {
                $ocData = XsCommodity::findOne($recArr['cid'], true);
                if (!$ocData) {
                    return [false, '数据错误: XsCommodity 无cid:' . $ocid];
                }
                //更新前台物品表
                $updateCommodity = [];
                foreach ($ocData as $k => $v) {
                    if (isset($recArr[$k]) && $recArr[$k] != $v) {
                        $updateCommodity[$k] = $recArr[$k];
                    }
                }
                if ($updateCommodity) {
                    list($result, $msg) = XsCommodity::edit($recArr['cid'], $updateCommodity);
                    if (!$result) {
                        return [false, $msg];
                    }
                }

                $commodityLog = $updateCommodity;
            } else {
                list($result, $ocid) = XsCommodity::add($recArr);
                if (!$result) {
                    return [false, $ocid];
                }
                //更新ocid
                $updateCommodityAdmin['ocid'] = $ocid;
                $recArr['cid'] = $ocid;
                $commodityLog = $rec;
            }

            //更新物品折扣率审核
            $propertyAdminRec = XsCommodityPropertyAdmin::findOne($cid, true);
            if ($propertyAdminRec) {
                $propertyAdminRec['cid'] = $propertyAdminRec['ocid'];
                unset($propertyAdminRec['ocid']);
                if ($propertyAdminRec['cid'] > 0) {
                    $propertyRec = XsCommodityProperty::findOne($propertyAdminRec['cid'], true);
                    if (!$propertyRec) {
                        return [false, '前台物品折扣表不存在物品id：' . $propertyAdminRec['cid']];
                    }

                    $updateCommodityProperty = [];
                    foreach ($propertyRec as $k => $v) {
                        if (isset($propertyAdminRec[$k]) && $propertyAdminRec[$k] != $v) {
                            $updateCommodityProperty[$k] = $propertyAdminRec[$k];
                        }
                    }
                    if ($updateCommodityProperty) {
                        list($result, $msg) = XsCommodityProperty::edit($propertyAdminRec['cid'], $updateCommodityProperty);
                        if (!$result) {
                            return [false, $msg];
                        }
                        $propertyLog = $updateCommodityProperty;
                    }
                } else {
                    //添加折扣
                    $propertyAdminRec['cid'] = $ocid;
                    list($result, $ocidPropertyId) = XsCommodityProperty::add($propertyAdminRec);
                    if (!$result) {
                        return [false, $ocidPropertyId];
                    }

                    //更新管理表
                    list($result, $msg) = XsCommodityPropertyAdmin::edit($cid, ['ocid' => $ocid]);
                    if (!$result) {
                        return [false, $msg];
                    }

                    $propertyLog = $propertyAdminRec;
                }
            }
        }

        //更新后台物品表
        list($result, $msg) = XsCommodityAdmin::edit($cid, $updateCommodityAdmin);
        if (!$result) {
            return [false, $msg];
        }

        //XsCommodity.cid 需要放后面才能获取到
        if ($state == XsCommodityAdmin::STATE_PASS && $ocid) {
            XsCommoditySend::setFailToVerify($cid, $adminId);
        }

        //日志类型
        switch ($state) {
            case XsCommodityAdmin::STATE_PASS:
                $type = XsstCommodityOperationLog::TYPE_REVIEW_PASS;
                break;
            case XsCommodityAdmin::STATE_FAIL:
                $type = XsstCommodityOperationLog::TYPE_REVIEW_FAIL;
                break;
            default:
                $type = XsstCommodityOperationLog::TYPE_REVIEW_WAIT;
        }
        list($result, $msg) = $this->addLog($cid, ['commodity' => $commodityLog, 'property' => $propertyLog], $type, $adminId);
        if (!$result) {
            return [false, 'XsstCommodityOperationLog ' . $msg];
        }
        return [true, ''];
    }

    /**
     * 批量审核通过
     * @param $params
     * @return array
     */
    public function reviewPassBatch($params): array
    {
        $adminId = array_get($params, 'admin_id', 0);
        $cidArr = array_get($params, 'cid_arr', 0);
        $lists = XsCommodityAdmin::getListByWhere([['cid', 'in', $cidArr], ['state', '=', XsCommodityAdmin::STATE_WAIT]], 'cid');
        $reviewCidArr = array_column($lists, 'cid');
        if (count($reviewCidArr) > 50) {
            return [false, '批量审核数量过多，请分批提交'];
        }

        $message = '';
        foreach ($reviewCidArr as $cid) {
            list($flg, $msg) = $this->review($cid, XsCommodityAdmin::STATE_PASS, $adminId);
            if (!$flg) {
                $message .= sprintf("记录id【%d】审核失败：%s" . PHP_EOL, $cid, $msg);
            }
        }

        return $message ? [false, $message] : [true, ''];
    }

    public function getNameFiledList(): array
    {
        return XsCommodityAdmin::$nameBigarea;
    }

    public function getDescriptionFiledList(): array
    {
        return XsCommodityAdmin::$descriptionBigarea;
    }

    public function getImageFiledList(): array
    {
        return XsCommodityAdmin::$imageBigarea;
    }

    public function getLanguageList(): array
    {
        $map = XsBigarea::getLanguageArr();
        unset($map['pt']);
        $map['mz'] = '马来华语';

        return $map;
    }

    /**
     * gift 映射 name
     * @param $giftList
     * @return array
     */
    private function packGiftList($giftList): array
    {
        $data = [];
        foreach ($giftList as $rec) {
            $list = [];
            foreach (XsCommodityAdmin::$nameBigarea as $k => $v) {
                $list[$k] = $rec[$k] ?? '';
            }
            $list['price'] = (string)(array_get($rec, 'price', 0) * 100);//填充价格
            $list['image'] = 'static/gift_big/' . $rec['id'] . '.png';//默认礼物的png图片
            $data[] = ['label' => $rec['id'] . '_' . $rec['name'], 'value' => $rec['id'], 'list' => $list];
        }

        return $data;
    }

    private function getCommoditySubTypeName($type, $subType): string
    {
        return XsCommodityAdmin::$subTypeList[$subType] ?? $subType;
    }

    public function exportSql($params)
    {
        $uid = Helper::getSystemUid();
        $idArr = trim(array_get($params, 'id_arr', ''));
        $idArr = str_replace('，', ',', $idArr);
        $pattern = '/[^,\d]/';
        $idArr = preg_replace($pattern, '', $idArr);

        $cidArr = [];
        $ocidArr = [];
        $groupIdArr = [];
        $gidArr = [];
        if (!empty($idArr)) {
            $cidArr = explode(',', $idArr);

            $ocidArr = XsCommodityAdmin::getListByWhere([['cid', 'in', $cidArr]], 'ocid, ext_id');
            $ocidArr = array_column($ocidArr, 'ocid');

            $gidArr = array_values(array_unique(array_column($ocidArr, 'ext_id')));

            $groupIdArr = XsCommodity::getListByWhere([['cid', 'in', $ocidArr]], 'group_id');
            $groupIdArr = array_column($groupIdArr, 'group_id');

        }

        $whereXsCommodity = !empty($ocidArr) ? "cid IN (" . implode(',', $ocidArr) . ")" : "";
        $whereXsCommodityGroup = !empty($groupIdArr) ? "group_id IN (" . implode(',', $groupIdArr) . ")" : "";
        $whereXsCommodityProperty = !empty($cidArr) ? "cid IN (" . implode(',', $ocidArr) . ")" : "";
        $whereXsCommodityAdmin = !empty($cidArr) ? "cid IN (" . implode(',', $cidArr) . ")" : "";
        $whereXsCommodityPropertyAdmin = !empty($cidArr) ? "cid IN (" . implode(',', $cidArr) . ")" : "";

        $fileName = 'commodity_export_sql_' . $uid . time() . '.sql';
        $tables = [
            ['schema' => XsCommodity::SCHEMA_READ, 'table' => 'xs_commodity', 'id' => 'cid', 'where' => $whereXsCommodity],
            ['schema' => XsCommodityGroup::SCHEMA_READ, 'table' => 'xs_commodity_group', 'id' => 'group_id', 'where' => $whereXsCommodityGroup],
            ['schema' => XsCommodityProperty::SCHEMA_READ, 'table' => 'xs_commodity_property', 'id' => 'cid', 'where' => $whereXsCommodityProperty],
            ['schema' => XsCommodityAdmin::SCHEMA_READ, 'table' => 'xs_commodity_admin', 'id' => 'cid', 'where' => $whereXsCommodityAdmin],
            ['schema' => XsCommodityPropertyAdmin::SCHEMA_READ, 'table' => 'xs_commodity_property_admin', 'id' => 'cid', 'where' => $whereXsCommodityPropertyAdmin],
            ['schema' => XsCommodityTag::SCHEMA_READ, 'table' => 'xs_commodity_tag', 'id' => 'id', 'where' => ''],
        ];

        if (count($gidArr) || empty($idArr)) {
            $whereXsGift = !empty($ocidArr) ? "id IN (" . implode(',', $gidArr) . ")" : "";
            $tables[] = ['schema' => XsGift::SCHEMA_READ, 'table' => 'xs_gift', 'id' => 'id', 'where' => $whereXsGift];
        }

        set_time_limit(60);
        $file = Helper::exportSql($fileName, $tables, empty($idArr));
        Helper::downLoadFile($file, 'commodityExportSql', 'sql');
    }
}