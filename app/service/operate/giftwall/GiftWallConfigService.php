<?php

namespace Imee\Service\Operate\Giftwall;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsChatroomBackground;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsEmoticonsGroup;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use phpDocumentor\Reflection\Types\Self_;

class GiftWallConfigService
{
    const TYPE_LIMITED_TIME = 2;

    public static $typeMap = [
        self::TYPE_MANUAL  => '限时系列',
        self::TYPE_RESERVA => '珍藏系列',
    ];

    const REWARD_HEADER = 'header';
    const REWARD_MOUNTS = 'mounts';
    const REWARD_BUBBLE = 'bubble';
    const REWARD_EFFECT = 'effect';
    const REWARD_DECORATE = 'decorate';
    const REWARD_RING = 'ring';

    public static $commodityList = [
        self::REWARD_HEADER,
        self::REWARD_BUBBLE,
        self::REWARD_MOUNTS,
        self::REWARD_EFFECT,
        self::REWARD_DECORATE,
        self::REWARD_RING,
    ];

    const STATUS_YES = 1;
    const STATUS_NO = 2;

    const TYPE_WEEK = 1;  // 周打卡
    const TYPE_MANUAL = 2; // 手动打卡
    const TYPE_RESERVA = 3; //珍藏系列

    public static $statusMap = [
        self::STATUS_YES => '有效',
        self::STATUS_NO  => '无效'
    ];

    const AWARD_TYPE_COMMODITY = 1;
    const AWARD_TYPE_CHATROOM_BACKGROUND = 3;
    const AWARD_TYPE_EMOTICONS = 19;

    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    /**
     * 获取限时礼物手动配置列表
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function getList(array $params): array
    {
        list($res, $list) = $this->rpcService->getGiftWallConfig($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $list);
        }
        $logs = BmsOperateLog::getFirstLogList('giftwallconfig', Helper::arrayFilter($list, 'config_id'));
        $now = time();
        $bigAreaList = XsBigarea::getAllNewBigArea();
        foreach ($list as &$item) {
            $item['card_name'] = @json_decode($item['card_name'], true) ?? [];
            $item['gift_collect'] = $this->getGiftCollect($item['gift_collect']);

            $item['award_list'] = $this->getGiftAward($item['award_list'] ?? []);
            $item['award_list_txt'] = $this->getGiftAwardTxt($item['award_list']);

            $item['big_area_name'] = $bigAreaList[$item['big_area']] ?? '';
            $item['gift_type'] = $item['type'];
            $cardName = $this->getCardName($item['card_name']);
            $item['gift_num'] = [
                'type'     => 'manMadeModal',
                'modal_id' => 'gift_wall_num',
                'title'    => count($item['gift_collect']),
                'value'    => count($item['gift_collect']),
                'params'   => [
                    'gift_collect' => $item['gift_collect']
                ]
            ];
            $item['award_num'] = count($item['award_list']);
            $item['card_name'] = $item['card_name']['zh_cn'] ?? '';
            $item['collect_day'] = $item['type'] == self::TYPE_RESERVA ? '永久有效' : $this->getCollectDay($item['end_time'], $now);
            $item['status'] = $item['type'] == self::TYPE_RESERVA ? '有效' : $this->getStatus($item['end_time'], $now);
            $item['dateline'] = isset($logs[$item['config_id']]['created_time']) ? Helper::now($logs[$item['config_id']]['created_time']) : '';
            $item['date'] = date('Y.m.d', $item['begin_time']) . '-' . date('Y.m.d', $item['end_time']);
            $poolNum = $this->getPoolNum($item['gift_collect']);
            $item = array_merge($item, $cardName);
            $item = array_merge($item, $poolNum);
        }

        return $list;
    }

    /**
     * 格式化礼物配置
     * @param array $giftCollect
     * @return array
     */
    private function getGiftCollect(array $giftCollect): array
    {
        $data = [];
        foreach ($giftCollect as $collect) {
            $collect['id'] = (string)$collect['id'];
            $data[] = $collect;
        }
        return $data;
    }

    private function getGiftAward(array $giftCollect): array
    {
        $data = [];
        $types = [self::AWARD_TYPE_CHATROOM_BACKGROUND];
        foreach ($giftCollect as $collect) {

            if ($collect['type'] == self::AWARD_TYPE_COMMODITY) {
                $commodity = XsCommodity::findOne($collect['id']);
                $collect['type'] = $commodity['type'] ?? '';
            }

            $data[] = [
                'award_type_txt' => $this->getAwardTypeMap($collect['type']),
                'award_type'     => (string)$collect['type'],
                'cid'            => $collect['id'],
                'award_num'      => in_array($collect['type'], $types) ? $collect['days'] : $collect['num'],
                'field'          => in_array($collect['type'], $types) ? '有效期day' : '数量',
            ];
        }
        return $data;
    }

    private function getGiftAwardTxt(array $awards): string
    {
        $str = '';
        foreach ($awards as $award) {
            if ($award['award_type'] == self::AWARD_TYPE_CHATROOM_BACKGROUND) {
                $str .= sprintf('<p>奖励类型：%s； 奖励ID：%s； %s：%s</p>', $award['award_type_txt'], $award['cid'], $award['field'], $award['award_num']);
            } else {
                $str .= sprintf('<p>奖励类型：%s； 奖励ID：%s</p>', $award['award_type_txt'], $award['cid']);
            }
        }
        return $str;
    }

    /**
     * 格式化礼物池id
     * @param array $giftCollect
     * @return array
     */
    private function getPoolNum(array $giftCollect): array
    {
        $data = [];
        foreach ($giftCollect as $collect) {
            $data['pool_num' . $collect['pool_num']] = $collect['id'];
        }
        return $data;
    }

    /**
     * 获取状态
     * @param int $expireAt
     * @param int $now
     * @return string
     */
    private function getStatus(int $expireAt, int $now): string
    {
        $status = self::STATUS_NO;

        if ($now < $expireAt) {
            $status = self::STATUS_YES;
        }

        return self::$statusMap[$status];
    }

    /**
     * 获取有效时长
     * @param int $expireAt
     * @param int $now
     * @return int
     */
    private function getCollectDay(int $expireAt, int $now): int
    {
        if ($expireAt <= $now) {
            return 0;
        }

        return ceil(($expireAt - $now) / 86400);
    }

    /**
     * 处理礼物名称
     * @param array $cardName
     * @return array
     */
    private function getCardName(array $cardName): array
    {
        $names = [];
        foreach ($cardName as $key => $name) {
            $names['name_' . $key] = $name;
        }
        return $names;
    }

    /**
     * 处理周礼物手动配置
     * @param array $params
     * @param bool $isModify
     * @return array
     * @throws ApiException
     */
    public function setConfig(array $params, bool $isModify = false): array
    {
        $this->validation($params);
        $data = $this->handleData($params, $isModify);
        //echo json_encode($data, JSON_PRETTY_PRINT);die;
        list($res, $id) = $this->rpcService->setGiftWallConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }
        $isModify && $id = $data['config_id'];

        return ['config_id' => $id, 'after_json' => $data];
    }

    /**
     * 处理礼物墙配置
     * @param array $params
     * @param bool $isModify
     * @return array
     */
    private function handleData(array $params, bool $isModify = false): array
    {
        $data = [
            'type'         => (int)$params['type'],
            'big_area'     => array_map('intval', $params['big_area']),
            'card_name'    => $this->setCardName($params),
            'comment'      => '',
            'operator'     => Helper::getAdminName($params['admin_uid']),
            'collect_day'  => (int)$params['collect_day'],
            'gift_collect' => $this->setGiftCollect($params),
            'award_list'   => $params['award_list'],
        ];

        $isModify && $data['config_id'] = (int)$params['config_id'];

        return $data;
    }

    /**
     * 格式化礼物池数据
     * @param array $giftConfig
     * @return array
     */
    private function setGiftCollect(array $params): array
    {
        $type = $params['type'];
        $suffix = $type == self::TYPE_RESERVA ? '_3' : '';
        $giftConfig = $params['gift_collect' . $suffix];

        $giftCollect = [];
        foreach ($giftConfig as $config) {
            $giftCollect[] = [
                'id'         => (int)$config['id' . $suffix],
                'target_num' => (int)$config['target_num' . $suffix],
            ];
        }

        return $giftCollect;
    }

    /**
     * 处理礼物名称
     * @param array $params
     * @return array
     */
    private function setCardName(array $params): array
    {
        $names = [];
        foreach ($params as $key => $value) {
            if (strstr($key, 'name_')) {
                $k = str_replace('name_', '', $key);
                $names[$k] = $value;
            }
        }

        return $names;
    }

    /**
     * 基础校验
     * @param array $params
     * @return void
     * @throws ApiException
     */
    private function validation(array &$params): void
    {
        $type = $params['type'];
        $suffix = $type == self::TYPE_RESERVA ? '_3' : '';

        $giftIds = array_column($params['gift_collect' . $suffix], 'id' . $suffix);
        $newGiftIds = Helper::arrayFilter($params['gift_collect' . $suffix], 'id' . $suffix);

        if (count($giftIds) != count($newGiftIds)) {
            throw new ApiException(ApiException::MSG_ERROR, '礼物id之间不能重复');
        }

        if (!is_array($params['big_area'])) {
            $params['big_area'] = [$params['big_area']];
        }

        $bigAreaCodeList = XsBigarea::getAllBigAreaCode();
        $bigAreaList = [];
        foreach ($params['big_area'] as $bigArea) {
            $code = $bigAreaCodeList[$bigArea];
            // 中文大区需要特殊处理
            if ($code == 'cn') {
                $code = 'zh_cn';
            }
            $bigAreaList[] = $code;
        }

        $giftList = XsGift::getBatchCommon($newGiftIds, ['id', 'excludes', 'is_secret_gift']);

        // 限制当前礼物是否在使用大区中生效
        foreach ($giftList as $gift) {
            if ($gift['is_secret_gift'] == 1) {
                throw new ApiException(ApiException::MSG_ERROR, '该礼物已配置成私密礼物，无法配置成礼物墙');
            }
            $excludes = explode(',', $gift['excludes']);
            $intersect = array_intersect($bigAreaList, $excludes);
            if ($intersect) {
                throw new ApiException(ApiException::MSG_ERROR, '礼物' . $gift['id'] . '不支持' . implode(',', $intersect) . '大区');
            }
        }

        $award = [];
        if ($params['type'] == self::TYPE_RESERVA) {
            $params['collect_day'] = 0;
            $awardList = $params['award_list'];
            $tmp = [];
            $types = [self::AWARD_TYPE_CHATROOM_BACKGROUND];

            foreach ($awardList as $item) {
                $tmp[$item['award_type']] = $tmp[$item['award_type']] ?? [];
                if (in_array($item['cid'], $tmp[$item['award_type']])) {
                    throw new ApiException(ApiException::MSG_ERROR, '相同奖励类型下，奖励ID 不能重复');
                }
                $tmp[$item['award_type']][] = $item['cid'];

                $award[] = [
                    'type' => in_array($item['award_type'], self::$commodityList) ? self::AWARD_TYPE_COMMODITY : (int)$item['award_type'],
                    'id'   => (int)$item['cid'],
                    'num'  => 1,
                    'days' => in_array($item['award_type'], $types) ? (int)$item['award_num'] : 0,
                ];
            }

            $backgroundMap = XsChatroomBackgroundMall::getOptions();

            foreach ($tmp as $type => $ids) {
                if (in_array($type, self::$commodityList)) {
                    $res = XsCommodity::getListByWhere([['cid', 'in', $ids], ['type', '=', $type]], 'cid');
                    if ($diff = array_diff($ids, array_column($res, 'cid'))) {
                        throw new ApiException(ApiException::MSG_ERROR, '奖励类型为' . XsCommodityAdmin::$typeMap[$type] . '时，该奖励ID不存在：' . implode(',', $diff));
                    }
                } elseif ($type == self::AWARD_TYPE_CHATROOM_BACKGROUND) {
                    if ($diff = array_diff($ids, array_keys($backgroundMap))) {
                        throw new ApiException(ApiException::MSG_ERROR, '奖励类型为房间背景时，该奖励ID不存在：' . implode(',', $diff));
                    }
                } elseif ($type == self::AWARD_TYPE_EMOTICONS) {
                    $res = XsEmoticons::findByIds($ids, 'id,bigarea_id,status');
                    if ($diff = array_diff($ids, array_column($res, 'id'))) {
                        throw new ApiException(ApiException::MSG_ERROR, '奖励类型为表情包时，该奖励ID不存在：' . implode(',', $diff));
                    }
                    foreach ($res as $v) {
                        if ($v['status'] != XsEmoticons::LISTED_STATUS) {
                            throw new ApiException(ApiException::MSG_ERROR, '该表情包ID未上架：' . $v['id']);
                        }
                        if (!in_array($v['bigarea_id'], $params['big_area'])) {
                            throw new ApiException(ApiException::MSG_ERROR, '该表情包ID【id: ' . $v['id'] . ' 大区：' . $bigAreaCodeList[$v['bigarea_id']] . '】不支持大区');
                        }
                    }
                }
            }
        }

        $params['award_list'] = $award;
    }

    /**
     * 获取限时礼物自动配置列表
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function getWeekList(array $params): array
    {
        list($res, $list) = $this->rpcService->getGiftWallWeekConfig($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $list);
        }

        foreach ($list as &$item) {
            $item['model_id'] = $item['pool_num'];
            $item['price'] = $item['price_low'] . '-' . $item['price_high'];
            $item['price_start'] = $item['price_low'];
            $item['price_end'] = $item['price_high'];
        }

        return $list;
    }

    /**
     * 设置周礼物自动配置
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function setWeekConfig(array $params): array
    {
        list($res, $list) = $this->rpcService->getGiftWallWeekConfig($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $list);
        }

        $config = array_column($list, null, 'pool_num');
        $config = $config[$params['pool_num']] ?? [];
        unset($config['id']);

        $data = [
            'pool_num'   => (int)$params['pool_num'],
            'target_num' => (int)$params['target_num'],
            'price_low'  => (int)$params['price_start'],
            'price_high' => (int)$params['price_end'],
        ];

        if ($data['price_low'] > $data['price_high']) {
            throw new ApiException(ApiException::MSG_ERROR, '价格区间错误');
        }

        list($res, $msg) = $this->rpcService->setGiftWallWeekConfig($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['pool_num' => $data['pool_num'], 'before_json' => $config, 'after_json' => $data];
    }

    /**
     * 礼物标签枚举
     * @return array
     */
    public function getGiftTagMap(): array
    {
        $map = [];
        foreach (self::$typeMap as $key => $name) {
            $map[] = [
                'label' => $name,
                'value' => $key
            ];
        }

        return $map;
    }

    public function getAwardTypeMap($value = null)
    {
        $commodityType = self::$commodityList;
        $map = [];

        foreach ($commodityType as $type) {
            $map[$type] = XsCommodityAdmin::$typeMap[$type];
        }

        $map[self::AWARD_TYPE_CHATROOM_BACKGROUND] = '房间背景';
        $map[self::AWARD_TYPE_EMOTICONS] = '表情包';

        if ($value !== null) {
            return $map[$value] ?? $value;
        }

        return StatusService::formatMap($map);
    }

    /**
     * 状态枚举
     * @return array
     */
    public function getStatusMap(): array
    {
        $map = [];
        foreach (self::$statusMap as $key => $name) {
            $map[] = [
                'label' => $name,
                'value' => $key
            ];
        }

        return $map;
    }

    /**
     * 礼物枚举
     * @return array
     */
    public function getGiftMap(): array
    {
        $map = [];

        $list = XsGift::getListByWhere([
            ['deleted', '=', XsGift::DELETE_NO],
            ['is_lucky', '=', 0],
        ], 'id, name');

        foreach ($list as $item) {
            $id = $item['id'];
            $name = $item['name'];
            $map[] = [
                'label' => "【{$id}】{$name}",
                'value' => $id
            ];
        }

        return $map;
    }

    /**
     * 礼物枚举
     * @return array
     */
    public function getGiftAllMap(): array
    {
        $map = [];

        $list = XsGift::getListByWhere([
            ['is_lucky', '=', 0],
        ], 'id, name');

        foreach ($list as $item) {
            $id = $item['id'];
            $name = $item['name'];
            $map[] = [
                'label' => "【{$id}】{$name}",
                'value' => $id
            ];
        }

        return $map;
    }

    public function getAwardList(string $type): array
    {
        $types = array_column($this->getAwardTypeMap(), 'value');
        if (!in_array($type, $types)) {
            return [];
        }
        $data = [];

        if (in_array($type, self::$commodityList)) {
            $data = XsCommodity::getListByWhere([['type', '=', $type]], 'cid,name');
            $data = array_map(function ($item) {
                $item['name'] = $item['cid'] . ' - ' . $item['name'];
                return $item;
            }, $data);
            $data = array_column($data, 'name', 'cid');
        } elseif ($type == self::AWARD_TYPE_CHATROOM_BACKGROUND) {
            $data = XsChatroomBackgroundMall::getOptions();
        } elseif ($type == self::AWARD_TYPE_EMOTICONS) {
            $data = XsEmoticons::getListByWhere([['status', '=', XsEmoticons::LISTED_STATUS]], 'id,group_id');
            $groupIds = XsEmoticonsGroup::findByIds(array_column($data, 'group_id'), 'id,name');
            $groupIds = array_column($groupIds, 'name', 'id');

            foreach ($data as &$v) {
                $v['name'] = "{$v['id']} -【ID:{$v['group_id']}】 " . ($groupIds[$v['group_id']] ?? '');
            }

            $data = array_column($data, 'name', 'id');
        }

        return StatusService::formatMap($data);
    }
}