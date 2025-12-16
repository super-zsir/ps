<?php

namespace Imee\Service\Operate\Activity;

use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsChatroomMaterial;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Service\Helper;

class AwardService
{
    public function info(int $id): array
    {
        $info = BbcRankAward::findOne($id);
        $extend = json_decode($info['award_extend_info'], true);
        $info['award_object_type'] = (string) $info['award_object_type'];
        $info['award_type'] = (string) $info['award_type'];
        $info['rank_award_type'] = (string) $info['rank_award_type'];
        $info['can_transfer'] = (string) $info['can_transfer'];
        $info['award_object'] = (string) $info['award_object'];
        $info['content'] = $extend['content'] ?? '';
        $info['icon'] = $extend['icon'] ?? '';
        $info['give_type'] = strval($extend['extend_type'] ?? '');
        if ($info['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_BG_CARD) {
            $info['day'] = $info['exp_days'];
            $extendInfo = $extend['room_bg_card_extend'] ?? [];
            $info['card_type'] = strval($extendInfo['card_type'] ?? '');
            unset($info['exp_days']);
        } elseif (in_array($info['award_type'], [
            BbcRankAward::AWARD_TYPE_MEDAL, BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD,
            BbcRankAward::AWARD_TYPE_ROOM_SKIN, BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON, BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING,
            BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
        ])) {
            $info['day'] = $info['num'];
            unset($info['num']);
        }  elseif ($info['award_type'] == BbcRankAward::AWARD_TYPE_VIP) {
            $info['vip'] = (string) $info['cid'];
            $info['day'] = (string) $info['num'];
            $info['num'] = (string) $extend['send_num'] ?? '';
            unset($info['cid']);
        }  elseif ($info['award_type'] == BbcRankAward::AWARD_TYPE_GAME_COUPON) {
            $info['day_select'] = (string) $info['exp_days'];
        } elseif ($info['award_type'] == BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD) {
            $info['hours'] = (string) $extend['days'] ?? '';
            $info['exp_time'] = $info['exp_days'] ? Helper::now($info['exp_days']) : '';
            $extendInfo = $extend['open_screen_card_extend'] ?? [];
            $info['card_type'] = strval($extendInfo['card_type'] ?? '');
            unset($info['exp_days']);
        } elseif ($info['award_type'] == BbcRankAward::AWARD_TYPE_PROP_CARD) {
            $info['hours'] = (string) $info['exp_days'] ?? '';
        } else if ($info['award_type'] == BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
            $info['exp_time'] = $info['exp_days'] ? Helper::now($info['exp_days']) : '';
            $info['valid_day'] = (string) ($extend['days'] ?? '');
            unset($info['exp_days']);
        }

        if (in_array($info['award_type'], [BbcRankAward::AWARD_TYPE_VIP, BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD,
            BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING, BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD])) {
            $info['num'] = $extend['send_num'] ?? '';
        }
        $info['cid'] = (string) $info['cid'];
        // 历史数据默认处理门槛要求为处于区间, score_max 新版最大值为  4294967295
        if (in_array($info['rank_award_type'], [BbcRankAward::RANK_AWARD_TYPE_SCORE, BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE, BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS])) {
            $info['score_type'] = $info['score_max'] == BbcRankAward::SCORE_MAX_NEW ? '2' : '1';
        }
        return $info;
    }

    public function modify(array $params): array
    {
        list($validRes, $data) = $this->validation($params);
        if (!$validRes) {
            return [$validRes, $data];
        }
        list($res, $msg) = BbcRankAward::edit($params['id'], $data);
        if (!$res) {
            return [$res, $msg];
        }
        return [true, ''];
    }

    private function validation(array $params)
    {
        if (!empty($params['num']) && !preg_match('/^[1-9]\d*$/', $params['num'])) {
            return [false, '数量必须为正整数'];
        }
        if (!empty($params['hours']) && !preg_match('/^[1-9]\d*$/', $params['hours'])) {
            return [false, '有效小时必须为正整数'];
        }
        if (!empty($params['day']) && !preg_match('/^[1-9]\d*$/', $params['day'])) {
            return [false, '天数少必须为正整数'];
        }
        if (!empty($params['exp_days']) && !preg_match('/^[1-9]\d*$/', $params['exp_days'])) {
            return [false, '资格使用天数必须为正整数'];
        }
        $buttonListId = intval($params['button_list_id'] ?? 0);
        if (empty($buttonListId)) {
            return [false, 'button_list_id为必填'];
        }
        $rankAwardType = intval($params['rank_award_type'] ?? -1);
        if ($rankAwardType < 0) {
            return [false, '发放对象必填'];
        }
        $id = intval($params['id'] ?? 0);
        $rank = $params['rank'] ?? 0;
        $scoreMin = $params['score_min'] ?? 0;
        $scoreMax = $params['score_max'] ?? 0;
        $scoreType = $params['score_type'] ?? 0;

        // 累胜玩法设置默认值
        if ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS) {
            $scoreType = 2;
        }

        // 门槛要求处于 >= 时 score_max 默认为 4294967295
        if ($scoreType == 2) {
            $scoreMax = BbcRankAward::SCORE_MAX_NEW;
        }

        $awardObjectType = intval($params['award_object_type'] ?? 0);
        $extendMin = $params['extend_rank_min'] ?? 0;
        $extendMax = $params['extend_rank_max'] ?? 0;
        if (BbcRankAward::isExtendRank($awardObjectType)) {
            if (!preg_match("/^\d+$/",$extendMin) || !preg_match("/^\d+$/",$extendMax)) {
                return [false, '成员名次上限下限必须为正整数'];
            }
            if ($extendMin < 1 || $extendMax < 1 || $extendMin < $extendMax) {
                return [false, '成员名次上限下限区间错误'];
            }
        } else {
            $extendMin = $extendMax = 0;
        }
        $awardType = $params['award_type'] ?? 0;
        if (empty($awardType)) {
            return [false, '奖励类型必选'];
        }
        if ($awardType == BbcRankAward::AWARD_TYPE_PRIZE_POOL) {
            if ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_SCORE) {
                return [false, '发放条件为门槛时，不支持配置奖池类型奖励'];
            }
        }
        if (self::isCidAwardType($awardType)) {
            $params['cid'] = intval($params['cid'] ?? 0);
            if (empty($params['cid'])) {
                return [false, '奖励ID必填'];
            }
        }

        switch ($rankAwardType) {
            case BbcRankAward::RANK_AWARD_TYPE_RANK:
                if (!preg_match("/^\d+$/",$rank)) {
                    return [false, '名次必须为正整数'];
                }
                if ($rank < 0) {
                    return [false, '发放条件为名次时名次必填'];
                }
                $scoreMin = $scoreMax = 0;
                break;
            case BbcRankAward::RANK_AWARD_TYPE_SCORE:
            case BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS:
                $rank = 0;
                if (!preg_match("/^\d+$/",$scoreMin) || !preg_match("/^\d+$/", $scoreMax)) {
                    return [false, '门槛上下限必须为正整数'];
                }
                if ($scoreMin > $scoreMax || $scoreMin < 0 || $scoreMax < 0) {
                    return [false, '发放条件为门槛时，门槛上下限必填且门槛上限必须大于门槛下限'];
                }
                [$res, $msg] = $this->validAwardScore($buttonListId, $rank, $awardObjectType, $rankAwardType, $scoreMin, $scoreMax, true, $id);
                if (!$res) {
                    return [false, $msg];
                }
                break;
            case BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE:
                if (!preg_match("/^\d+$/",$rank)) {
                    return [false, '名次必须为正整数'];
                }
                if (!preg_match("/^\d+$/",$scoreMin) || !preg_match("/^\d+$/", $scoreMax)) {
                    return [false, '门槛上下限必须为正整数'];
                }
                if ($rank < 0) {
                    return [false, '发放条件为名次且门槛时，名次必填'];
                }
                if ($scoreMin > $scoreMax || $scoreMin < 0 || $scoreMax < 0) {
                    return [false, '发放条件为门槛时，门槛上下限必填且门槛上限必须大于门槛下限'];
                }
                [$res, $msg] = AwardService::validAwardScore($buttonListId, $rank, $awardObjectType, BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE, $scoreMin, $scoreMax, true, $id);
                if (!$res) {
                    return [false, $msg];
                }
                break;
        }

        $update = [
            'rank'              => $rank,
            'rank_award_type'   => $rankAwardType,
            'score_min'         => $scoreMin,
            'score_max'         => $scoreMax,
            'award_object_type' => $awardObjectType,
            'extend_rank_min'   => $extendMin,
            'extend_rank_max'   => $extendMax,
            'num'               => 0,
            'cid'               => 0,
            'exp_days'          => 0,
            'can_transfer'      => 0,
            'diamond_proportion'=> 0,
            'award_extend_info' => '',
        ];
        list($res, $msg, $data) = $this->validateAwardConfig($params);
        if (!$res) {
            return [$res, $msg];
        }

        return [true, array_merge($update, $data)];
    }

    public function validateAwardConfig(array $params)
    {
        if ($params['award_type'] == BbcRankAward::AWARD_TYPE_COMMODITY) {
            if (empty($params['cid']) || empty($params['num']) || empty($params['exp_days'])) {
                return [false, '物品类型必须输入物品id和数量和资格使用天数', []];
            }
            $cInfo = XsCommodity::findFirst($params['cid']);
            if (!$cInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的物品ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['num'], 'cid' => $params['cid'], 'exp_days' => $params['exp_days']];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_MEDAL) {
            if (empty($params['cid']) || empty($params['day'])) {
                return [false, '勋章类型必须输入勋章id和天数', []];
            }
            $mInfo = XsMedalResource::findOneByWhere([
                ['id', '=', $params['cid']],
                ['type', '=', XsMedalResource::HONOR_MEDAL]
            ]);
            if (!$mInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的勋章ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['day'], 'cid' => $params['cid']];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_GAME_COUPON) {
            if (!isset($params['day_select']) && isset($params['exp_days'])) {
                $params['day_select'] = intval($params['exp_days']);
            }
            if (empty($params['cid']) || empty($params['num']) || empty($params['day_select'])) {
                return [false, "游戏优惠券必须填写：奖励ID,数量，有效期", []];
            }
            $sInfo = XsCoupon::findOne($params['cid']);
            if (!$sInfo) {
                return [false, sprintf("奖励%d:%d为不正确的优惠券ID,请重新填写", $params['i'], $params['cid']), []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['num'], 'exp_days' => $params['day_select']];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_VIP) {
            if (empty($params['vip']) || empty($params['give_type'])) {
                return [false, 'VIP类型下必选VIP等级、天数、是否可赠送', []];
            }
            // 直接生效时，发放数量不允许填写
            if ($params['give_type'] == BbcRankAward::GIVE_TYPE_AUTO_EFFECT) {
                $params['num'] = 1;
                if (empty($params['day'])) {
                    return [false, sprintf("奖励%d:是否可赠送为直接生效时, 天数必填", $params['i']), []];
                }
            } else {
                $params['day'] = 30;
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['day'], 'cid' => $params['vip'], 'award_extend_info' => json_encode(['extend_type' => (int) $params['give_type'], 'send_num' => (int) $params['num']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND) {
            if (empty($params['cid']) && empty($params['day'])) {
                return [false, '房间背景类型下必选房间背景id即房间背景天数', []];
            }
            $bInfo = XsChatroomBackgroundMall::findOneByWhere([
                ['bg_id', '=', $params['cid']]
            ]);
            $mInfo = XsChatroomMaterial::findOneByWhere([
                ['mid', '=', $bInfo['mid'] ?? 0],
                ['source', '=', 0]
            ]);
            if (!$bInfo || !$mInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的房间背景ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['day'], 'cid' => $params['cid']];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_BG_CARD) {
            if (empty($params['day']) || empty($params['num'])) {
                return [false, '自定义房间背景卡类型必须输入数量和天数', []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['num'], 'exp_days' => $params['day'], 'can_transfer' => $params['can_transfer']];
            $params['card_type'] > -1 && $award['award_extend_info'] = json_encode(['room_bg_card_extend' => ['card_type' => (int) $params['card_type']]]);
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD) {
            if (empty($params['cid']) || empty($params['day']) || empty($params['exp_days'])) {
                return [false, '自选靓号卡类型必须输入靓号ID和天数、资格使用天数', []];
            }
            $pInfo = XsCustomizePrettyStyle::findOne($params['cid']);
            if (!$pInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的靓号ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['day'], 'exp_days' => $params['exp_days'], 'can_transfer' => $params['can_transfer'], 'award_extend_info' => json_encode(['send_num' => (int) $params['num']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD) {
            if (empty($params['cid']) || empty($params['num']) || empty($params['exp_days'])) {
                return [false, '房间置顶卡类型必须输入置顶卡ID和数量、资格使用天数', []];
            }
            $cInfo = XsRoomTopCard::findOneByWhere([
                ['id', '=', $params['cid']],
                ['is_delete', '=', XsRoomTopCard::DELETE_NO]
            ]);
            if (!$cInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的置顶卡ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['num'], 'exp_days' => $params['exp_days']];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_DIAMOND) {
            if (!empty($params['diamond_proportion']) && !empty($params['num'])) {
                return [false, '返钻比例和数量只允许填写其中一个', []];
            }
            if ($params['diamond_proportion'] && !preg_match('/^\d+(\.\d)?$/', $params['diamond_proportion'])) {
                return [false, '返钻比例为正数（整数和精确到小数点后一位的小数）', []];
            }
            if ($params['num'] && !preg_match('/^\d+$/', $params['num'])) {
                return [false, '数量必须为正整数', []];
            }
            $award = ['award_type' => $params['award_type'], 'diamond_proportion' => round($params['diamond_proportion'], 1), 'num' => intval($params['num'])];
        }  elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_SKIN) {
            if (empty($params['cid']) || empty($params['day'])) {
                return [false, '房间皮肤类型必须输入皮肤id和天数', []];
            }
            $mInfo = XsRoomSkin::findOneByWhere([
                ['id', '=', $params['cid']],
                ['status', '=', XsRoomSkin::SUPPORT_SEND_STATUS]
            ]);
            if (!$mInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的皮肤ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['day'], 'cid' => $params['cid']];
        }  elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON) {
            if (empty($params['cid']) || empty($params['day']) || empty($params['content'])) {
                return [false, '认证图标类型必须输入图标id、天数、文案', []];
            }
            $sInfo = XsCertificationSign::findOne($params['cid']);
            if (!$sInfo) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的认证图标ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['day'], 'cid' => $params['cid'], 'award_extend_info' => json_encode(['content' => $params['content']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_PRIZE_POOL) {
            if (empty($params['diamond_proportion'])) {
                return [false, '奖池类型下瓜分比例为必填项', []];
            }
            if (!preg_match('/^\d+(\.\d)?$/', $params['diamond_proportion']) || $params['diamond_proportion'] <= 0 || $params['diamond_proportion'] > 100) {
                return [false, '瓜分比例为0~100之间的数，支持有一位小数', []];
            }
            $award = ['award_type' => $params['award_type'], 'diamond_proportion' => round($params['diamond_proportion'], 1)];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_CUSTOMIZATION) {
            if (empty($params['num']) || empty($params['icon']) || empty($params['content'])) {
                return [false, '自定义奖励类型必须输入数量、预览图、自定义描述', []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['num'], 'award_extend_info' => json_encode(['content' => $params['content'], 'icon' => $params['icon']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING) {
            if (empty($params['cid']) || empty($params['day']) || empty($params['exp_days'])) {
                return [false, '炫彩资源类型必须输入资源ID和天数、资格使用天数', []];
            }
            $nameIdLighting = XsNameIdLightingGroup::findOne($params['cid']);
            if (!$nameIdLighting) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的资源ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['day'], 'exp_days' => $params['exp_days'], 'can_transfer' => $params['can_transfer'], 'award_extend_info' => json_encode(['send_num' => (int) $params['num']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_ITEM_CARD) {
            if (empty($params['cid']) || empty($params['day']) || empty($params['exp_days'])) {
                return [false, 'mini卡装扮类型必须输入卡ID和天数、资格使用天数', []];
            }
            $miniCard = XsItemCard::findOne($params['cid']);
            if (!$miniCard || $miniCard['type'] != XsItemCard::TYPE_MINI) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的卡ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['day'], 'exp_days' => $params['exp_days'], 'can_transfer' => $params['can_transfer'], 'award_extend_info' => json_encode(['send_num' => (int) $params['num']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_PROP_CARD) {
            if (empty($params['cid']) || empty($params['num']) || empty($params['hours'])) {
                return [false, 'pk道具卡类型必须输入道具ID和数量、有效小时', []];
            }
            $propCard = XsPropCard::findOne($params['cid']);
            if (!$propCard) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的道具ID,请重新填写", []];
            }
            $propCardConfig = XsPropCardConfig::findOneByWhere([
                ['id', '=', $propCard['prop_card_config_id']],
                ['type', 'IN', [5, 6]]
            ]);
            if (!$propCardConfig) {
                return [false, "奖励{$params['i']}: {$params['cid']} 道具卡的类型不支持，请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['num'], 'exp_days' => intval($params['hours'] ?? 0), 'award_extend_info' => json_encode(['extend_type' => (int) $propCardConfig['type'] ?? 0])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD) {
            if (empty($params['num']) || empty($params['hours']) || empty($params['exp_time'])) {
                return [false, 'pk道具卡类型必须输入开屏卡ID和数量、有效小时、是否赠送、过期时间', []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['num'], 'exp_days' => strtotime($params['exp_time']), 'can_transfer' => $params['can_transfer'], 'award_extend_info' => ['days' => (int) $params['hours'] ?? 0]];
            $params['card_type'] > 0 && $award['award_extend_info']['open_screen_card_extend'] = ['card_type' => (int) $params['card_type']];
            $award['award_extend_info'] = json_encode($award['award_extend_info']);
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD) {
            if (empty($params['cid']) || empty($params['day']) || empty($params['exp_days'])) {
                return [false, '个人主页装扮卡装扮类型必须输入卡ID和天数、资格使用天数', []];
            }
            $homepageCard = XsItemCard::findOne($params['cid']);
            if (!$homepageCard || $homepageCard['type'] != XsItemCard::TYPE_HOMEPAGE) {
                return [false, "奖励{$params['i']}: {$params['cid']} 为不正确的卡ID,请重新填写", []];
            }
            $award = ['award_type' => $params['award_type'], 'cid' => $params['cid'], 'num' => $params['day'], 'exp_days' => $params['exp_days'], 'can_transfer' => $params['can_transfer'], 'award_extend_info' => json_encode(['send_num' => (int) $params['num']])];
        } elseif ($params['award_type'] == BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
            if (empty($params['num']) || empty($params['exp_time']) || empty($params['valid_day'])) {
                return [false, '定制表情卡类型必须输入数量、过期时间、生效时间', []];
            }
            $award = ['award_type' => $params['award_type'], 'num' => $params['num'], 'exp_days' => strtotime($params['exp_time']), 'can_transfer' => $params['can_transfer'], 'award_extend_info' => json_encode(['days' => (int) $params['valid_day']])];
        } else {
            $award = ['award_type' => $params['award_type'], 'num' => $params['num']];
        }

        return [true, '', $award];
    }

    public function addData($data)
    {
        $query = [
            ['act_id', '=', $data['act_id']],
            ['button_list_id', '=', $data['button_list_id']],
            ['rank', '=', $data['rank']],
            ['award_type', '=', $data['award_type']],
            ['cid', '=', $data['cid']],
            ['score_min', '=', $data['score_min']],
            ['score_max', '=', $data['score_max']],
            ['award_object_type', '=', $data['award_object_type']],
            ['extend_rank_min', '=', $data['extend_rank_min']],
            ['extend_rank_max', '=', $data['extend_rank_max']],
            ['can_transfer', '=', $data['can_transfer']],
            ['exp_days', '=', $data['exp_days']],
            ['award_extend_info', '=', $data['award_extend_info']],
        ];
        $info = BbcRankAward::findOneByWhere($query, true);
        $newAwardExtendInfo = json_decode($data['award_extend_info'], true);
        if ($info) {
            $awardExtendInfo = json_decode($info['award_extend_info'], true);
            $update = [
                'diamond_proportion' => $info['diamond_proportion'] + $data['diamond_proportion'],
                'admin_id' => $data['admin_id'],
                'dateline' => $data['dateline'],
            ];
            switch ($data['award_type']) {
                case BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING:
                case BbcRankAward::AWARD_TYPE_ITEM_CARD:
                case BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD:
                    $update['award_extend_info'] = json_encode(['send_num' => $awardExtendInfo['send_num'] + $newAwardExtendInfo['send_num']]);
                    break;
                default:
                    $update['num'] = $info['num'] + $data['num'];
                    break;
            }
            BbcRankAward::edit($info['id'], $update);
        } else {
            BbcRankAward::add($data);
        }
    }

    /**
     * 判断是否发奖
     * 活动下是否存在需要发奖的榜单（is_award = 1）
     * 奖励中存在钻石类型（award_type = 1）
     * @param int $actId
     * @return bool
     */
    public function isAward(int $actId): bool
    {
        $buttonList = BbcRankButtonList::getListByWhere([['act_id', '=', $actId], ['is_award', '=', BbcRankButtonList::IS_AWARD_YES]]);
        if (empty($buttonList)) {
            return false;
        }
        $rankAward = BbcRankAward::findOneByWhere([['award_type', '=', BbcRankAward::AWARD_TYPE_DIAMOND], ['button_list_id', 'IN', Helper::arrayFilter($buttonList, 'id')]]);

        return !!$rankAward;
    }

    /**
     * 是否需要输入cid奖励类型
     * @param int $awardType
     * @return bool
     */
    public static function isCidAwardType(int $awardType): bool
    {
        if (in_array($awardType, [
            BbcRankAward::AWARD_TYPE_COMMODITY, BbcRankAward::AWARD_TYPE_MEDAL, BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND,
            BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD, BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD, BbcRankAward::AWARD_TYPE_ROOM_SKIN,
            BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON,  BbcRankAward::AWARD_TYPE_GAME_COUPON, BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING,
            BbcRankAward::AWARD_TYPE_ITEM_CARD, BbcRankAward::AWARD_TYPE_PROP_CARD, BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD
        ])) {
            return true;
        }

        return false;
    }

    /**
     * 验证cid是否重复
     * @param string $cid
     * @param int $awardType
     * @param int $i
     * @return array
     */
    public function validCid(string $cid, int $awardType, int $i = 1): array
    {
        // 奖励类型为物品、勋章、房间背景时同个奖励中不允许出现重复奖励ID
        if (!self::isCidAwardType($awardType)) {
            return [true, []];
        }
        $cidArr = explode("\n", $cid);
        $newCidArr = Helper::handleIds($cidArr);

        if (count($cidArr) != count($newCidArr)) {
            return [false, "奖励{$i}：同一个奖励不允许有重复的奖励ID"];
        }

        return [true, $newCidArr];
    }

    /**
     * 校验当前发放对象是否已经配置钻石奖励，已配置则校验是否会导致该对象同时配置钻石数量奖励与积分返钻比例奖励
     * @param int $buttonListId
     * @param int $id
     * @param string $field
     * @return bool
     */
    public function validateDiamondAward(int $buttonListId, int $id = 0, string $field = 'num'): bool
    {
        $award = BbcRankAward::findOneByWhere([
            ['award_type', '=', BbcRankAward::AWARD_TYPE_DIAMOND],
            ['button_list_id', '=', $buttonListId],
            ['id', '<>', $id],
            [$field, '<>', 0]
        ]);

        return (bool)$award;
    }

    /**
     * 验证积分门槛
     * @param int $buttonListId
     * @param int $rank
     * @param int $awardObjectType
     * @param int $rankAwardType
     * @param int $scoreMin
     * @param int $scoreMax
     * @param bool $isCutOff
     * @param int $id
     * @return array
     */
    public function validAwardScore(int $buttonListId, int $rank, int $awardObjectType, int $rankAwardType, int $scoreMin, int $scoreMax, bool $isCutOff = false, int $id = 0): array
    {
        // 累胜玩法不需要校验重复
        if ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS) {
            return [true, ''];
        }
        $scoreList = BbcRankAward::getListByRankAndObjectAndType($id, $buttonListId, $rank, $awardObjectType, $rankAwardType);
        if (!$this->validAwardScoreOverlap($scoreList, $scoreMin, $scoreMax)) {
            return [false, '同一名次的门槛不允许重合'];
        }

        if ($isCutOff === false) {
            return [true, ''];
        }
        $scoreList[] = ['score_min' => $scoreMin, 'score_max' => $scoreMax];

        if (!$this->validAwardScoreCutOff($scoreList)) {
            return [false, '同一名次的门槛不允许存在断档'];
        }

        return [true, ''];
    }

    /**
     * 验证积分门槛是否重叠
     * @param array $scoreList
     * @param int $scoreMin
     * @param int $scoreMax
     * @return bool
     */
    public function validAwardScoreOverlap(array $scoreList, int $scoreMin, int $scoreMax): bool
    {
        if (empty($scoreList)) {
            return true;
        }
        foreach ($scoreList as $score) {
            $existMin = $score['score_min'];
            $existMax = $score['score_max'];

            // 判断是否相同
            if ($existMin == $scoreMin && $existMax == $scoreMax) {
                return true;
            }

            // 判断是否部分重叠
            if (
                ($scoreMin < $existMax && $scoreMax > $existMin) || // 相交
                ($scoreMin == $existMax || $scoreMax == $existMin)  // 紧邻
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * 验证积分门槛是否断层
     * @param array $scoreList
     * @return bool
     */
    public function validAwardScoreCutOff(array $scoreList): bool
    {
        // 单个区间无断层
        if (count($scoreList) <= 1) {
            return true;
        }

        // 按 score_min 排序
        usort($scoreList, function($a, $b) {
            return $a['score_min'] <=> $b['score_min'];
        });


        // 举例：已存在 91-100，那么新增的区间必须为 101-xx || xx - 90
        foreach ($scoreList as $i => $range) {
            if ($i === 0) continue;

            $prev = $scoreList[$i - 1];
            if ($range['score_min'] > $prev['score_max'] + 1) {
                return false; // 出现断层
            }
        }

        return true;
    }

    /**
     * 验证删除数据是否断档
     * @param array $idArr
     * @return array
     */
    public function validAwardBatchDelete(array $idArr): array
    {
        $award = BbcRankAward::findOne($idArr[0]);
        $buttonListId = $award['button_list_id'];

        $awardList = BbcRankAward::getListByWhere([
            ['button_list_id', '=', $buttonListId],
            ['rank_award_type', '<>', BbcRankAward::RANK_AWARD_TYPE_RANK]
        ]);
        // 要删除的数据中发放对象只有名次，直接删除即可
        $filterAwardList = array_filter($awardList, function($award) use ($idArr) {
            return in_array($award['id'], $idArr);
        });
        if (empty($filterAwardList)) {
            return [true, ''];
        }

        // 取出删除后还保留的奖励配置
        $waitCheckAwardArray = [];
        foreach ($awardList as $award) {
            // 存在要删除的id直接跳过
            if (in_array($award['id'], $idArr)) {
                continue;
            }
            $uniqueKey = 'uk_' . $award['rank'] . $award['award_object_type'] . '_' . $award['rank_award_type'];

            if (isset($waitCheckAwardArray[$uniqueKey])) {
                $waitCheckAwardArray[$uniqueKey][] = $award;
            } else {
                $waitCheckAwardArray[$uniqueKey] = [$award];
            }
        }

        // 删除后没有其他奖励的话，可以直接删除
        if (empty($waitCheckAwardArray)) {
            return [true, ''];
        }

        foreach ($waitCheckAwardArray as $awardArray) {
            if (!$this->validAwardScoreCutOff($awardArray)) {
                return [false, '当删除的数据中，删除后会存在积分门槛断层情况。请检查'];
            }
        }

        return [true, ''];
    }
}