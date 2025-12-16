<?php

namespace Imee\Service\Operate\Activity;

use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfig;
use Imee\Models\Xs\XsGift;
use Imee\Service\Helper;

class ScoreSetService
{
    public function getList(array $params): array
    {
        $conditions = [
            ['button_list_id', '=', $params['button_list_id'] ?? 0]
        ];
        $list = BbcRankScoreConfig::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $giftIdArr = array_column($list['data'], 'gift_id');
        $giftList = XsGift::getBatchCommon($giftIdArr, ['id', 'name']);
        $buttonList = BbcRankButtonList::findOne($list['data'][0]['button_list_id']);
        $now = time();
        foreach ($list['data'] as &$item) {
            $item['hide_score'] = $buttonList['hide_score'];
            $item['recharge_channels'] = explode(',', $item['recharge_channels']);
            if ($item['recharge_channels']) {
                $rechargeChannels = '';
                foreach ($item['recharge_channels'] as $channel) {
                    $rechargeChannels .= BbcRankScoreConfig::$rechargeChannelsMap[$channel] ?? '';
                    $rechargeChannels .= '/';
                }
                $item['recharge_channels'] = ltrim($rechargeChannels, '/');
            }
            $item['is_check_pk_valid'] = (string)$item['is_check_pk_valid'];
            if ($item['is_check_pk_valid']) {
                $pkValidExtend = json_decode($item['pk_valid_extend'], true);
                $item['pk_valid_type'] = strval($pkValidExtend['pk_valid_type'] ?? 0);
                $item['pk_time'] = intval($pkValidExtend['pk_time'] ?? 0);
                $item['pk_gift'] = intval($pkValidExtend['pk_gift'] ?? 0);
            } else {
                $item['pk_valid_type'] = '0';
                $item['pk_time'] = 0;
            }
            $item['dateline'] = $item['dateline'] > 0 ? Helper::now($item['dateline']) : ' - ';
            $item['admin'] = Helper::getAdminName($item['admin_id']);
            $item['gift_name'] = $giftList[$item['gift_id']]['name'] ?? '';
            $item['gift_png'] = $item['gift_id'] ? Helper::getHeadUrl('static/gift_big/' . $item['gift_id'] . '.png') . '?time=' . $now : '';
        }

        return $list;
    }

    private function valid(array $params): array
    {
        $id = $params['id'] ?? 0;
        $buttonListId = $params['button_list_id'] ?? 0;
        $type = $params['type'] ?? 0;
        $score = round($params['score'] ?? 0, 2);
        $giftId = $params['gift_id'] ?? '';
        $rechargeChannels = $params['recharge_channels'] ?? [];

        if (empty($buttonListId) || empty($type) || empty($score)) {
            return [false, 'list_id、积分类型、分值均为必填项'];
        }
        // 游戏类型
        if (in_array($type, [21, 22, 25, 26, 27, 28, 29, 30, 31])) {
            if (!preg_match('/^[1-9]\d*$/', $score)) {
                return [false, '游戏类型下分值均必须为正整数'];
            }
            $info = BbcRankScoreConfig::findOneByWhere([
                ['button_list_id', '=', $buttonListId],
                ['type', 'IN', [21, 22, 25, 26, 27, 28, 29, 30, 31]],
                ['id', '<>', $params['id'] ?? 0]
            ]);
            if ($info) {
                return [false, 'button_list只允许配置一条游戏积分规则'];
            }
        }
        if (in_array($type, [8, 10]) && !preg_match('/^[1-9]\d*$/', $score)) {
            return [false, '胜利场次和完成场次下分值均必须为正整数'];
        }
        if (in_array($type, [2, 3, 5, 6]) && empty($giftId)) {
            return [false, '礼物id必须填写'];
        }
        // type为充值钻石时，充值渠道必填
        if ($type == BbcRankScoreConfig::TYPE_TOP_UP_DIAMOND && empty($rechargeChannels)) {
            return [false, '充值渠道必填'];
        }
        if ($giftId) {
            list($res, $msg) = $this->handleGiftId($giftId);
            if (!$res) {
                return [false, $msg];
            }
            $giftId = $id ? $msg[0] : $msg;
        }
        $buttonList = BbcRankButtonList::findOne($buttonListId);

        if (empty($buttonList) || empty($buttonList['act_id']) || empty($buttonList['button_tag_id'])) {
            return [false, '活动button_list信息有误'];
        }

        $buttonTag = BbcRankButtonTag::findOne($buttonList['button_tag_id']);

        $isCheckPkValid = intval($params['is_check_pk_valid'] ?? 0);
        $pkValidType = intval($params['pk_valid_type'] ?? -1);
        $pkTime = $params['pk_time'] ?? 0;
        $pkGift = $params['pk_gift'] ?? 0;

        if ($buttonTag['rank_object'] == BbcRankButtonTag::RANK_OBJECT_ROOM && in_array($type, [8, 10])) {
            // 房间贡献榜中，配置胜利场次和完成场次积分时，必须配置有效场次要求
            $isCheckPkValid = 1;
            if ($pkValidType < 0) {
                return [false, '有效场次要求为必填项'];
            }
            switch ($pkValidType) {
                case 0:
                    if ($pkTime < 1 || !preg_match('/^\d+$/', $pkTime)) {
                        return [false, '单场pk时长为正整数'];
                    }
                    $pkGift = 0;
                    break;
                case 1:
                    if ($pkGift < 1 || !preg_match('/^\d+$/', $pkGift)) {
                        return [false, '房间内收礼为正整数'];
                    }
                    $pkTime = 0;
                    break;
                case 2:
                    if ($pkTime < 1 || !preg_match('/^\d+$/', $pkTime)) {
                        return [false, '单场pk时长为正整数'];
                    }
                    if ($pkGift < 1 || !preg_match('/^\d+$/', $pkGift)) {
                        return [false, '房间内收礼为正整数'];
                    }
                    break;
                case 3:
                    // 验证单场pk时长｜房间内收礼 必填其中一个值 且为正整数
                    if (($pkTime < 1 || !preg_match('/^\d+$/', $pkTime))
                        && ($pkGift < 1 || !preg_match('/^\d+$/', $pkGift))) {
                        return [false, '单场pk时长｜房间内收礼 必填其中一个值 且为正整数'];
                    }
                    break;
            }
        } else {
            $isCheckPkValid = $pkValidType = $pkTime = $pkGift = 0;
        }

        if (!in_array($type, [2, 3, 5, 6])) {
            $scoreInfo = BbcRankScoreConfig::findOneByWhere([
                ['type', '=', $type],
                ['button_list_id', '=', $buttonListId],
                ['id', '<>', $id]
            ]);
            if ($scoreInfo) {
                return [false, "button_list_id:{$buttonListId} 已经设置过此类型的积分配置了"];
            }
            $giftId = 0;
        }

        $pkValidExtend = '';

        if ($isCheckPkValid) {
            $pkValidExtend = json_encode([
                'pk_valid_type' => (int)$pkValidType,
                'pk_time'       => (int)$pkTime,
                'pk_gift'       => (int)$pkGift
            ]);
        }

        // 收付费礼物 与 单场pk 类型 不能同时存在
        if ($buttonTag['rank_object'] == BbcRankButtonTag::RANK_OBJECT_ROOM && in_array($type, [4, 8, 10])) {
            $types = $type == 4 ? [8, 10] : [4];
            $scoreInfo = BbcRankScoreConfig::findOneByWhere([
                ['type', 'IN', $types],
                ['button_list_id', '=', $buttonListId],
                ['id', '<>', $id]
            ]);
            if ($scoreInfo) {
                return [false, "pk场数和收付费礼物不支持同时配置"];
            }
        }

        return [true, [
            'button_list_id'    => $buttonListId,
            'act_id'            => $buttonList['act_id'],
            'score'             => $score,
            'type'              => $type,
            'gift_id'           => $giftId,
            'recharge_channels' => implode(',', $rechargeChannels),
            'admin_id'          => Helper::getSystemUid(),
            'dateline'          => time(),
            'is_check_pk_valid' => $isCheckPkValid,
            'pk_valid_extend'   => $pkValidExtend,
        ]];
    }

    public function create(array $params): array
    {
        list($res, $data) = $this->valid($params);
        if (!$res) {
            return [false, $data];
        }
        $insert = [];
        if ($data['gift_id'] && is_array($data['gift_id'])) {
            foreach ($data['gift_id'] as $id) {
                $insert[] = array_merge($data, ['gift_id' => $id]);
            }
        } else {
            $insert = [$data];
        }

        return BbcRankScoreConfig::addBatch($insert);
    }

    public function modify(array $params): array
    {
        list($res, $data) = $this->valid($params);
        if (!$res) {
            return [false, $data];
        }
        return BbcRankScoreConfig::edit($params['id'], $data);
    }

    public function deleteBatch(array $params): array
    {
        $ids = $params['ids'] ?? '';
        if (empty($ids)) {
            return [true, ''];
        }

        return BbcRankScoreConfig::deleteByWhere([
            ['id', 'IN', Helper::formatUid($ids)]
        ]);
    }

    public function info(int $id): array
    {
        $info = BbcRankScoreConfig::findOne($id);
        if (empty($info)) {
            return [false, '当前积分配置不存在'];
        }
        $info['type'] = strval($info['type']);
        if ($info['recharge_channels']) {
            $info['recharge_channels'] = explode(',', $info['recharge_channels']);
            foreach ($info['recharge_channels'] as $key => $channels) {
                $info["recharge_channels[{$channels}]"] = 1;
            }
            unset($info['recharge_channels']);
        }
        $info['is_check_pk_valid'] = (string)$info['is_check_pk_valid'];

        if ($info['is_check_pk_valid']) {
            $pkValidExtend = json_decode($info['pk_valid_extend'], true);
            $info['pk_valid_type'] = strval($pkValidExtend['pk_valid_type'] ?? 0);
            $info['pk_time'] = $pkValidExtend['pk_time'] ?? 0;
            $info['pk_gift'] = $pkValidExtend['pk_gift'] ?? 0;
        } else {
            $info['pk_valid_type'] = '0';
            $info['pk_time'] = $info['pk_gift'] = '';
        }
        return [true, $info];
    }

    private function handleGiftId(string $giftId, int $limit = 20): array
    {
        $giftArr = explode("\n", $giftId);
        $giftArr = Helper::handleIds($giftArr);
        if (count($giftArr) > $limit) {
            return [false, '当前礼物只允许配置20个'];
        }
        $list = XsGift::getListByWhere([
            ['id', 'IN', $giftArr],
            ['gift_type', '<>', 'coin'],
            ['price', '>', 0]
        ], 'id');
        $list = $list ? array_column($list, 'id') : [];
        $diff = array_diff($giftArr, $list);
        if ($diff) {
            return [false, '以下礼物ID不存在或无效' . implode(',', $diff)];
        }
        return [true, $list];
    }
}