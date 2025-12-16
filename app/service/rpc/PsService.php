<?php

/**
 * ps业务方提供的接口服务
 */

namespace Imee\Service\Rpc;

use Imee\Comp\Common\Log\LoggerProxy;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Service\Helper;
use Imee\Service\Operate\TeamPkRecordService;

class PsService
{
    /** @var PsRpc $psRpc */
    private $psRpc;

    public function __construct()
    {
        $this->psRpc = new PsRpc();
    }

    public function updateGiftStatus(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_UPDATE_GIFT_STATUS, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg']];
    }

    public function getListUserExp(array $uids): array
    {
        $data = [];
        $data['page'] = ['page_index' => 1, 'page_size' => count($uids)];
        $data['query'] = ['app_id' => APP_ID, 'uid' => $uids];

        list($data, $_) = $this->psRpc->call(PsRpc::API_PRICE_LEVEL, ['json' => $data]);
        if (empty($data['exp'])) {
            return [];
        }
        return array_column($data['exp'], 'lv', 'uid');
    }


    /**
     * 获取视频直播信息
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Libs\Rpc\InvalidApiNameException
     */
    public function getVideoInfo(array $data)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_QUERY_VIDEO_INFO, ['json' => $data]);
        return $res;
    }

    /**
     * 设置Slot大区开关
     * @param array $postData
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Libs\Rpc\InvalidApiNameException
     */
    public function setSlotSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_SWITCH, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];

    }

    public function sendUserEmoticons(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_CARD_SEND, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function expireUserEmoticons(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_CARD_EXPIRE, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function auditUserEmoticons(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_CARD_ADUIT, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftSwitch(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_SWITCH, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftDividedAdd(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_DIVIDED_ADD, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['data']['id']];
        }
        return [false, $res['common']['msg'], 0];
    }

    public function luckyGiftDividedEdit(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_DIVIDED_EDIT, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftDividedDelete(int $id): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_DIVIDED_DEL, ['json' => [
            'id' => $id
        ]]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftRateAdjustAdd(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_RATE_ADJUST_ADD, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['data']['id']];
        }
        return [false, $res['common']['msg'], 0];
    }

    public function luckyGiftRateAdjustEdit(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_RATE_ADJUST_EDIT, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftRateAdjustDelete(int $id): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_RATE_ADJUST_DEL, ['json' => [
            'id' => $id
        ]]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftRateAdd(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_RATE_ADD, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['data']['id']];
        }
        return [false, $res['common']['msg'], 0];
    }

    public function luckyGiftRateEdit(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_RATE_EDIT, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function luckyGiftRateDelete(int $id): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_GIFT_RATE_DEL, ['json' => [
            'id' => $id
        ]]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function getLuckyFruitsLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['config_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function luckyGiftLimitConfigAdd(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_LIMIT_CONFIG_ADD, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', isset($res['data']['id']) ? $res['data']['id'] : 0];
        }
        return [false, isset($res['common']['msg']) ? $res['common']['msg'] : '访问失败', 0];
    }

    public function luckyGiftLimitConfigEdit(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_LIMIT_CONFIG_MODIFY, ['json' => $postData]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, isset($res['common']['msg']) ? $res['common']['msg'] : '访问失败'];
    }

    public function luckyGiftLimitConfigDelete(array $postData): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_LIMIT_CONFIG_DEL, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, isset($res['common']['msg']) ? $res['common']['msg'] : '访问失败'];
    }

    /**
     * 设置SicBo大区开关（Dice玩法）
     * @param array $postData
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Libs\Rpc\InvalidApiNameException
     */
    public function setSicDoSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SIC_BO_SWITCH, ['json' => $postData]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? 'rpc: 错误'];
    }

    public function setSicBoConfig(array $postData): array
    {
        $data = [
            'config' => $postData
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_SIC_BO_CONFIG, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function userMedalTimeLess(array $params): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_USER_MEDAL_LESS_TIME, ['json' => $params]);
        if (!isset($res['success']) || !$res['success']) {
            return [false, $res['msg'], []];
        }
        return [true, '', $res['data']];
    }

    public function userMedalUpdateConfig(array $params): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_USER_MEDAL_UPDATE_CONFIG, ['json' => $params]);

        if (!isset($res['success']) || !$res['success']) {
            return [false, $res['msg'], []];
        }
        return [true, '', $res['data'] ?? []];
    }

    public function setRedPacketConfig(array $data): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_RED_PACKET_CONFIG, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    // 测试修改大区
    public function getRedPacketConfig(int $id): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_RED_PACKET_CONFIG_INFO, [
            'json' => ['big_area_id' => $id]
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function activeAddBlackList($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACTIVE_ADD_BLACK_LIST, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function activeDelBlackList($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACTIVE_DEL_BLACK_LIST, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function getTeamPkRecordList(array $params)
    {
        $data = [
            'page'      => $params['page'] ?? 0,
            'page_size' => $params['limit'] ?? 0,
        ];

        foreach (['bigarea', 'uid'] as $value) {
            if (isset($params[$value]) && !empty($params[$value])) {
                $data[$value] = (int)$params[$value];
            }
        }

        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $data['begin_time'] = strtotime($params['dateline_sdate']);
        }

        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $data['end_time'] = strtotime($params['dateline_edate']);
        }

        [$res, $_] = $this->psRpc->call(PsRpc::API_GET_TEAM_PK_RECORD, [
            'json' => $data
        ]);

        $list = ['total' => 0, 'data' => []];

        if (isset($res['common']) && $res['common']['err_code'] == 0) {

            $list = [
                'total' => $res['count'] ?? 0,
                'data'  => $res['data'] ?? []
            ];

            $list['data'] = TeamPkRecordService::formatList($list['data']);

            return [true, '', $list];
        }

        return [false, $res['common']['msg'], $list];
    }

    public function getTeamPkDiamondRecordList(array $params): array
    {
        $data = [
            'pk_id'     => $params['pk_id'],
            'rank_type' => (int)$params['type'],
            'page'      => $params['page'] ?? 1,
            'page_size' => $params['limit'] ?? 15,
        ];

        [$res, $_] = $this->psRpc->call(PsRpc::API_GET_TEAM_PK_DIAMOND_RECORD, [
            'json' => $data
        ]);

        $list = [
            'total' => $res['count'] ?? 0,
            'data'  => $res['data'] ?? []
        ];

        return [true, '', $list];
    }

    public function modifyLuckyWheel(array $data): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_LUCKY_WHEEL_MODIFY, [
            'json' => $data
        ]);
        if (isset($res['msg']) == 'success' && $res['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function getProbabilityGameSwitch(): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_PROBABILITY_GAME_SWITCH_LIST);

        $list = [
            'total' => count($res['probability_game_switch'] ?? []) ?? 0,
            'data'  => $res['probability_game_switch'] ?? []
        ];

        return $list;
    }

    public function chatroomMaterialList(array $params): array
    {
        $query = [];
        if (isset($params['mid']) && !empty($params['mid'])) {
            $query['mid'] = (int)$params['mid'];
        }
        if (isset($params['name']) && !empty($params['name'])) {
            $query['name'] = $params['name'];
        }
        $data = [
            'page' => [
                'page_index' => (int)($params['page'] ?? 1),
                'page_size'  => (int)($params['limit'] ?? 10),
            ],
        ];

        if (!empty($query)) {
            $data['query'] = $query;
        }
        list($res, $code) = $this->psRpc->call(PsRpc::API_CHATROOM_MATERIAL_LIST, [
            'json' => $data
        ]);

        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['list'] ?? []
        ];

        return $list;
    }

    public function editProbabilityGameSwitch(array $params): array
    {
        $data = [
            'probability_game_switch' => [
                'game_center_id'       => (int)$params['game_center_id'],
                'first_recharge_limit' => (bool)$params['first_recharge_limit'],
            ],
        ];

        [$res, $_] = $this->psRpc->call(PsRpc::API_PROBABILITY_GAME_SWITCH_MODIFY, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function addChatroomMaterial(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CHATROOM_MATERIAL_ADD, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg']];
    }

    public function editChatroomMaterial(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CHATROOM_MATERIAL_EDIT, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function delChatroomMaterial(int $mid): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CHATROOM_MATERIAL_DELETE, ['json' => [
            'mid' => (int)$mid
        ]]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function chatroomBackgroundMallList(array $params): array
    {
        $query = [
            'is_free' => $params['is_free'],
            'source'  => $params['source']
        ];

        if (isset($params['mid']) && !empty($params['mid'])) {
            $query['mid'] = (int)$params['mid'];
        }
        if (isset($params['bg_id']) && !empty($params['bg_id'])) {
            $query['bg_id'] = (int)$params['bg_id'];
        }
        if (isset($params['big_area']) && !empty($params['big_area'])) {
            $query['big_area'] = $params['big_area'];
        }
        if (isset($params['state'])) {
            $query['state'] = (int)$params['state'];
        }
        $data = [
            'page'  => [
                'page_index' => (int)($params['page'] ?? 1),
                'page_size'  => (int)($params['limit'] ?? 10),
            ],
            'query' => $query
        ];
        list($res, $code) = $this->psRpc->call(PsRpc::API_CHATROOM_BACKGROUND_LIST, [
            'json' => $data
        ]);

        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['list'] ?? []
        ];

        return $list;
    }

    public function addRoomBackground(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CHATROOM_BACKGROUND_ADD, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg']];
    }

    public function editRoomBackground(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CHATROOM_BACKGROUND_EDIT, ['json' => $data]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function delRoomBackground(int $bgId): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CHATROOM_BACKGROUND_DELETE, ['json' => [
            'bg_id' => (int)$bgId
        ]]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function userChatroomBackgroundList(array $params): array
    {
        $query = [];
        if (isset($params['uid']) && !empty($params['uid'])) {
            $query['uid'] = (int)$params['uid'];
        }
        if (isset($params['bg_id']) && !empty($params['bg_id'])) {
            $query['bg_id'] = (int)$params['bg_id'];
        }
        if (isset($params['big_area']) && !empty($params['big_area'])) {
            $query['big_area'] = $params['big_area'];
        }
        $data = [
            'page' => [
                'page_index' => (int)($params['page'] ?? 1),
                'page_size'  => (int)($params['limit'] ?? 10),
            ],
        ];
        if (!empty($query)) {
            $data['query'] = $query;
        }
        list($res, $code) = $this->psRpc->call(PsRpc::API_USER_CHATROOM_BACKGROUND_LIST, [
            'json' => $data
        ]);
        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['list'] ?? []
        ];

        return $list;
    }

    public function editUserRoomBackground(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_CHATROOM_BACKGROUND_EDIT, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function dropRoomBackground(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_DROP_ROOM_BACKGROUND, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg']];
    }

    public function mDropRoomBackground(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_M_DROP_ROOM_BACKGROUND, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg']];
    }

    public function giveCertificationSign(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GIVE_CERTIFICATION_SIGN, ['json' => $data]);
        if (isset($res['common']['err_code'])) {
            return [false, $res['common']['msg'] ?? '接口错误'];
        }
        return [true, ''];
    }

    public function updateCertificationSign(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_UPDATE_CERTIFICATION_SIGN, ['json' => $data]);
        if (isset($res['common']['err_code'])) {
            return [false, $res['common']['msg'] ?? '接口错误'];
        }
        return [true, ''];
    }

    public function updateQuickGiftConfig(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_QUICK_GIFT_UPDATE, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function setTeenPattiSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TEEN_PATTI_SWITCH, ['json' => $postData]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? 'rpc: 错误'];
    }

    public function setGreedySwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_SWITCH, [
            'json' => $postData
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function setGreedyEngine(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_ENGINE, [
            'json' => $postData
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function setGreedyGlobalRankSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_GLOBAL_RANK_SWITCH, [
            'json' => $postData
        ]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误'];
    }

    public function setGreedyMeta(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_CONFIG, [
            'json' => ['config' => $postData, 'engine_id' => $postData['engine_id'] ?? 0]
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    // 大区宝箱配置
    public function setBigAreaGreedyBox(int $bigAreaId, array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_CONFIG_BIG_AREA_BOX, [
            'json' => [
                'big_area_id' => $bigAreaId,
                'config'      => $postData
            ]
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function setGreedyBoxSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_BOX_SWITCH, [
            'json' => $postData
        ]);
        if (isset($res['success']) && $res['success']) {
            return [true, ''];
        }
        return [false, $res['msg'] ?? '接口错误'];
    }

    public function addLoginRegisterWhiteList(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LOGIN_REGISTER_WHITE_LIST_ADD, ['json' => [
            'data' => $data
        ]]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['last_insert_id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }


    public function prettyUserCustomizeCreate(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PRETTY_USER_CUSTOMIZE_CREATE, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误'];
    }

    public function prettyUserCustomizeModify(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PRETTY_USER_CUSTOMIZE_MODIFY, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误'];
    }

    public function prettyCommodityCreate(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PRETTY_COMMODITY_CREATE, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', isset($res['id']) ? $res['id'] : 0];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误', 0];
    }

    public function prettyCommodityModify(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PRETTY_COMMODITY_MODIFY, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误'];
    }

    public function prettyCommodityShelf(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PRETTY_COMMODITY_SHELF, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误'];
    }

    public function givePrettyUid(array $data): array
    {

        list($res, $_) = $this->psRpc->call(PsRpc::API_GIVE_PRETTY_UID_CREATE, ['json' => $data]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', isset($res['data']['id']) ? $res['data']['id'] : 0];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误', 0];
    }

    public function givePrettyUidModify(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GIVE_PRETTY_UID_MODIFY, ['json' => $data]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ? '接口错误' . $res['common']['msg'] : '接口错误'];
    }

    public function setRoomRocketSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_ROCKET_SWITCH, [
            'json' => $postData
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function roomRocketConfigList(array $params): array
    {
        $data = [
            'page'      => $params['page'] ?? 1,
            'page_size' => $params['limit'] ?? 15,
        ];

        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_ROCKET_CONFIG_LIST, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['data'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function getRoomRocketBigAreaInfo(int $id): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_ROCKET_CONFIG_INFO, ['json' => [
            'big_area_id' => $id
        ]]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['configs' => $res['configs'] ?? [], 'version' => $res['version'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function getRoomRocketBigAreaAwardInfo(int $id): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_ROCKET_AWARD_CONFIG, ['json' => [
            'big_area_id' => $id
        ]]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['configs'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function setRoomRocketBigAreaConfig(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_ROCKET_CONFIG_EDIT, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function setRoomRocketAwardConfig(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_ROCKET_AWARD_CONFIG_EDIT, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function addPopupsConfig(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_POPUPS_CONFIG_ADD, [
            'json' => ['data' => $data]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['last_insert_id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }

    public function userPayPassAndEmailReset(array $uids, string $api): array
    {
        list($res, $_) = $this->psRpc->call($api, [
            'json' => [
                'uid_list' => $uids
            ]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function userEmailModify(int $uid, string $email): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_MODIFY_EMAIL, [
            'json' => [
                'uid'   => $uid,
                'email' => $email,
            ]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // 获取自定义房间背景卡片大区开关列表
    public function customBgcCardSwitchList(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_ROOM_BACKGROUND_CARD_SWITCH, []);

        $data = [
            'data'  => [],
            'total' => 0
        ];
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            $data['data'] = $res['list'] ?? [];
            $data['total'] = count($data['data']);
        }

        return $data;
    }

    public function saveCustomBgcCardSwitch(array $data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_ROOM_BACKGROUND_CARD_SWITCH_EDIT, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // 获取自定义房间背景卡片列表
    public function customBgcCardLogList(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_ROOM_BACKGROUND_CARD_LOG, [
            'json' => $params
        ]);

        return [
            'data'  => $res['list'] ?? [],
            'total' => $res['total'] ?? 0
        ];
    }

    public function customBgcCardSend(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_ROOM_BACKGROUND_CARD_LOG_ADD, [
            'json' => ['cards' => $params]
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            if (isset($res['failed_uids']) && !empty($res['failed_uids'])) {
                $uids = implode(',', $res['failed_uids']);
                return [false, "{$uids} 发送失败，以上用户绑定大区开关未开启或其他原因请找服务端研发确认"];
            }
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // 获取自定义房间背景卡片列表
    public function customBgcCardpackList(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_ROOM_BACKGROUND_CARD_PACK, [
            'json' => $params
        ]);

        return [
            'data'  => $res['list'] ?? [],
            'total' => $res['total'] ?? 0
        ];
    }

    public function customBgcCardpackDel(int $id)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_ROOM_BACKGROUND_CARD_PACK_DEL, [
            'json' => [
                'id' => $id
            ]
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getProbabilityGameBigAreaConfig(int $gameId)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROBABILITY_GAME_BIG_AREA_CONFIG_LIST, [
            'json' => [
                'game_id' => $gameId
            ]
        ]);

        $list = $res['game_config_list'] ?? [];

        if ($list) {
            $list = array_column($list, null, 'big_area');
        }

        return $list;
    }

    public function editProbabilityGameBigAreaConfig(array $params)
    {
        $data = [
            'game_id'     => (int)$params['game_id'],
            'game_config' => [
                'big_area'    => (int)$params['bigarea_id'],
                'limit_level' => (int)$params['level']
            ]
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROBABILITY_GAME_BIG_AREA_CONFIG_EDIT, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createWelcomgiftbag(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTBAG_CREATE, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function modifyWelcomgiftbag(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTBAG_MODIFY, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }


    public function updateAgencyHunterGiftBagStatus(int $id, int $status): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_AGENCY_HUNTER_GIFT_BAG_STATUS_MODIFY, [
            'json' => [
                'id'     => $id,
                'status' => $status,
            ]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误：更新礼包下发审核状态失败'];
    }

    public function modifyWelcombigarea(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_BIGAREA_MODIFY, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createWelcomgifthunter(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTHUNTER_CREATE, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createWelcomgifthunterBatch(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTHUNTER_CREATE_BATCH, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function updateBatchAgencyHunterGiftBag(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTHUNTER_UPDATE_BATCH, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createWelcomgifthunterBatchByCondition(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTHUNTER_CREATE_BATCH_CONDITION, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function modifyWelcomgifthunter(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_WELCOME_GIFTHUNTER_MODIFY, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getGameCouponList(array $params): array
    {
        $_query = ['type' => 1, 'page_size' => 1, 'page_num' => 1];
        list($res,) = $this->psRpc->call(PsRpc::GAME_COUPON_CONFIG_LIST, ['json' => array_merge($_query, $params)]);

        if (isset($res['common']['err_code']) && $res['common']['err_code']) {
            return ['data' => [], 'total' => 0];
        }

        return ['data' => $res['list'], 'total' => $res['total']];
    }

    public function getGameCouponAllList($type = 1): array
    {
        list($res,) = $this->psRpc->call(PsRpc::GAME_COUPON_CONFIG_ALL_LIST, ['json' => ['type' => $type]]);

        if (isset($res['common']['err_code']) && $res['common']['err_code']) {
            return [];
        }
        $list = $res['list'];
        $data = [];
        foreach ($list as $rec) {
            $data[$rec['id']] = [
                'id'     => $rec['id'],
                'name'   => array_get($rec['name'], 'cn', ''),
                'amount' => $rec['amount'],
            ];
        }
        return $data;
    }

    public function gameCouponCreate(array $params): array
    {
        $_query = ['type' => 1];
        list($res, $_) = $this->psRpc->call(PsRpc::GAME_COUPON_CONFIG_CREATE, ['json' => array_merge($_query, $params)]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function gameCouponModify(array $params): array
    {
        $_query = ['type' => 1];
        list($res, $_) = $this->psRpc->call(PsRpc::GAME_COUPON_CONFIG_MODIFY, ['json' => array_merge($_query, $params)]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getGameCouponAmount($type = 1): array
    {
        list($res,) = $this->psRpc->call(PsRpc::GAME_COUPON_CONFIG_AMOUNT, ['json' => ['type' => $type]]);
        if (isset($res['common']['err_code']) && $res['common']['err_code']) {
            return [];
        }
        return $res['amounts'];
    }

    public function getGameCouponScene($type = 1): array
    {
        list($res,) = $this->psRpc->call(PsRpc::GAME_COUPON_CONFIG_SCENE, ['json' => ['type' => $type]]);
        if (isset($res['common']['err_code']) && $res['common']['err_code']) {
            return [];
        }
        return array_column($res['scenes'], 'name', 'id');
    }

    public function gameCouponIssuedOp(array $params): array
    {
        $_query = ['type' => 1];
        list($res, $_) = $this->psRpc->call(PsRpc::GAME_COUPON_ISSUED_OP, ['json' => array_merge($_query, $params)]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }


    public function batchAddVIP(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_VIP_SEND_BATCH_ADD, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * VIP发送检查
     * @param array $params
     * @return array
     */
    public function vipSendCheck(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_VIP_SEND_CHECK, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['data'] ?? []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function getStickerResourceList(array $params): array
    {
        $params = [
            'page'       => [
                'page_index' => (int)$params['page'],
                'page_size'  => (int)$params['limit'],
            ],
            'sticker_id' => (int)($params['id'] ?? 0),
            'name'       => $params['name'] ?? ''
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_STICKER_RESOURCE_LIST, [
            'json' => $params
        ]);
        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['sticker_list'] ?? [],
        ];

        return $list;
    }

    public function stickerResourceAdd(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_STICKER_RESOURCE_ADD, [
            'json' => ['sticker' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }

    public function stickerResourceEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_STICKER_RESOURCE_EDIT, [
            'json' => ['sticker' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function setDragonTigerSwitch(array $postData): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_DRAGON_TIGER_SWITCH, [
            'json' => $postData
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createRoomSkin(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_SKIN_CREATE, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['skin_id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function delRoomSkin(int $id)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_SKIN_DELETE, [
            'json' => ['skin_id' => (int)$id]
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getStickerManageList(array $params): array
    {
        $params = [
            'page'        => [
                'page_index' => (int)$params['page'],
                'page_size'  => (int)$params['limit'],
            ],
            'sticker_id'  => (int)($params['sticker_id'] ?? 0),
            'big_area_id' => (int)($params['big_area_id'] ?? 0),
            'status'      => (int)($params['status'] ?? 0),
            'name'        => $params['sticker_name'] ?? ''
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_STICKER_MANAGE_LIST, [
            'json' => $params
        ]);
        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['sticker_list'] ?? [],
        ];
        return $list;
    }

    public function stickerManageAdd(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_STICKER_MANAGE_ADD, [
            'json' => ['sticker_manage' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['id']];
        }

        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }

    public function stickerManageEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_STICKER_MANAGE_EDIT, [
            'json' => ['sticker_manage' => $params]]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function setDragonTigerConfig(array $postData): array
    {
        $data = [
            'config' => $postData
        ];

        list($res, $_) = $this->psRpc->call(PsRpc::API_DRAGON_TIGER_CONFIG, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function sendUserRoomSkin(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_SKIN_SEND, [
            'json' => ['skin_term_list' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function recoveryUserRoomSkin(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_SKIN_RECOVERY, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function delOnepkObj(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ONE_PK_OBJECT_DEL, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function updateRegisterLoginConfig(array $params)
    {
        $data = [
            'big_area_id'           => (int)$params['id'],
            'register_login_config' => [
                'device_register_num_limit'       => (int)$params['device_register_num_limit'],
                'device_daily_register_num_limit' => (int)$params['device_daily_register_num_limit'],
                'device_daily_login_num_limit'    => (int)$params['device_daily_login_num_limit'],
                'device_weekly_login_num_limit'   => (int)$params['device_weekly_login_num_limit'],
            ],
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_UPDATE_REGISTER_LOGIN_CONFIG, [
            'json' => $data
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setHorseRaceSwitch(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HORSE_RACE_SWITCH, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setCustomGiftSwitch(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_GIFT_SWITCH_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setHorseRaceConfig(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HORSE_RACE_CONFIG, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function createOpenScreen(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_OPEN_SCREEN_CREATE, [
            'json'    => ['data' => $params],
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function editOpenScreen(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_OPEN_SCREEN_EDIT, [
            'json'    => ['data' => $params],
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setHorseRaceEngine(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HORSE_RACE_ENGINE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function disableOpenScreen(int $id)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_OPEN_SCREEN_DISABLE, [
            'json'    => ['id' => $id],
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function faceStatus(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_FACE_STATUS, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function faceChange(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_FACE_CHANGE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function createRoomTopCard(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_CARD_CREATE, [
            'json'    => $params,
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function updateRoomTopCard(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_CARD_UPDATE, [
            'json'    => $params,
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function deleteRoomTopCard(int $id)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_CARD_DELETE, [
            'json'    => ['id' => $id],
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function getCustomStickerResourceList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_RESOURCE_LIST, [
            'json' => $params
        ]);
        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['custom_sticker_list'] ?? [],
        ];

        return $list;
    }

    public function customStickerResourceAdd(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_RESOURCE_ADD, [
            'json' => ['custom_sticker' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }

    public function customStickerResourceEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_RESOURCE_EDIT, [
            'json' => ['custom_sticker' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function sendRoomTopCard(array $params, string $admin)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_CARD_SEND, [
            'json'    => [
                'list'     => $params,
                'operator' => $admin
            ],
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['list'] ?? []];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function recoverRoomTopCard(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_CARD_REMOVE, [
            'json'    => $params,
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getCustomStickerManageList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_MANAGE_LIST, [
            'json' => $params
        ]);
        $list = [
            'total' => $res['page']['total_count'] ?? 0,
            'data'  => $res['sticker_list'] ?? [],
        ];
        return $list;
    }

    public function getEmoticonsRewardList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_REWARD_LIST, [
            'json' => $params
        ]);

        $list = [
            'total' => $res['total'] ?? 0,
            'data'  => $res['reward_emoticons'] ?? [],
        ];
        return $list;
    }

    public function getEmoticonsRewardSearchList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_REWARD_SEARCH_LIST, [
            'json' => $params
        ]);

        $list = [
            'total' => $res['total'] ?? 0,
            'data'  => $res['reward_operations'] ?? [],
        ];
        return $list;
    }

    public function addEmoticonsReward(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_REWARD_ADD, [
            'json' => ['data' => $params]
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['ids']];
        }

        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }

    public function reduceEmoticonsReward(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_REWARD_REDUCE, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function customStickerManageAdd(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_MANAGE_ADD, [
            'json' => ['sticker_manage' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['id']];
        }

        return [false, $res['common']['msg'] ?? '接口错误', 0];
    }

    public function customStickerManageEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_MANAGE_EDIT, [
            'json' => ['sticker_manage' => $params]]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg']];
    }

    public function setCustomStickerSwitch(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CUSTOM_STICKER_SWITCH, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function faceDelete(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_FACE_DELETE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function createGameBlackList(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_BLACK_LIST_CREATE, [
            'json' => [
                'list'     => $params,
                'operator' => Helper::getAdminName(Helper::getSystemUid()),
            ]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $params];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function updateGameBlackList(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_BLACK_LIST_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function faceReplace(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_FACE_REPLACE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function deleteGameBlackList(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_BLACK_LIST_DELETE, [
            'json' => ['ids' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setLuckyFruitsSwitch(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_FRUIT_SWITCH, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包tag添加
    public function createEmoticonsTag(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_TAG_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包tag修改
    public function updateEmoticonsTag(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_TAG_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setLuckyFruitsConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_FRUIT_CONFIG, [
            'json' => ['config' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包tag删除
    public function deletedEmoticonsTag(int $id): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_TAG_DELETE, [
            'json' => ['id' => (int)$id]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function getLuckyFruitsWeight(int $tabId): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_FRUIT_WEIGHT_LIST, [
            'json' => ['category_id' => (int)$tabId]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['list'] ?? []];
        }

        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function initLuckyFruitsWeight(): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_FRUIT_WEIGHT_INIT, [
            'json' => []
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['category_id'] ?? 0];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包素材添加
    public function createEmoticonsMeta(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_META_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包素材修改
    public function updateEmoticonsMeta(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_META_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包素材删除
    public function deletedEmoticonsMeta(int $id): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_META_DELETE, [
            'json' => ['id' => (int)$id]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包创建
    public function createEmoticons(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['ids'] ?? []];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包编辑
    public function updateEmoticons(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function editLuckyFruitsWeight(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_FRUIT_WEIGHT_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包上下架
    public function upOrDownEmoticons(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_DOWN, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function deleteLuckyFruitsWeight(int $tabId): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LUCKY_FRUIT_WEIGHT_DELETE, [
            'json' => ['category_id' => (int)$tabId]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 定制表情包删除
    public function deletedEmoticons(int $id): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_EMOTICONS_DELETE, [
            'json' => ['id' => (int)$id]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 点赞素材创建
    public function createLikeIcon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LIKE_ICON_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 点赞素材编辑
    public function editLikeIcon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_LIKE_ICON_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 站外链接跳转大区开关修改
    public function setUrlLinkSwitch(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SET_URL_LINK_SWITCH, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getGiftWallConfig(array $params): array
    {
        $filter = [
            'type' => $params['type']
        ];
        if (isset($params['status']) && !empty($params['status'])) {
            $filter['status'] = (int)$params['status'];
        }
        if (isset($params['big_area']) && !empty($params['big_area'])) {
            $filter['big_area'] = (int)$params['big_area'];
        }
        if (isset($params['date_sdate']) && !empty($params['date_sdate'])) {
            $filter['date_begin'] = strtotime($params['date_sdate']);
        }
        if (isset($params['date_edate']) && !empty($params['date_edate'])) {
            $filter['date_end'] = strtotime($params['date_edate']);
        }
        [$res, $_] = $this->psRpc->call(PsRpc::API_GET_GIFT_WALL_CONFIG, [
            'json' => $filter
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['gift_wall_config']];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setGiftWallConfig(array $params): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_SET_GIFT_WALL_CONFIG, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['config_id']];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function getGiftWallWeekConfig(array $params): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_GET_GIFT_WALL_WEEK_CONFIG, [
            'json' => []
        ]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['gift_collect']];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function setGiftWallWeekConfig(array $params): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_SET_GIFT_WALL_WEEK_CONFIG, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // 火箭crash-大区开关编辑
    public function setRocketCrashSwitch(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_SWITCH, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // 火箭crash-编辑参数配置
    public function setRocketCrashConfig(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_CONFIG, [
            'json' => ['meta_data' => $params]
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getRocketCrashLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['config_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    // 火箭crash-新增调控配置参数
    public function addRocketCrashLimitConfig(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_LIMIT_CONFIG_ADD, [
            'json' => ['config' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // 火箭crash-编辑调控配置参数
    public function editRocketCrashLimitConfig(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_LIMIT_CONFIG_EDIT, [
            'json' => ['config' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getRocketCrashTimeOdds(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_CONFIG_TIME_ODDS_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['info_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    // 火箭crash-新增爆炸预期表数据
    public function addRocketCrashTimeOdds(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_CONFIG_TIME_ODDS_ADD, [
            'json' => ['info_list' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // 火箭crash-新增爆炸预期表数据
    public function editRocketCrashTimeOdds(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROCKET_CRASH_CONFIG_TIME_ODDS_EDIT, [
            'json' => ['info_list' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 更新活动1v1对战状态
     * @param int $id
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Imee\Comp\Common\Rpc\InvalidApiNameException
     */
    public function updateOnepkObj(int $id): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_UPDATE_ONEPK_OBJ, [
            'json' => ['act_id' => $id]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getKv(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GET_KV, [
            'json' => $params
        ]);
        $list = ['total' => 0, 'data' => []];
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            $list = ['total' => $res['total'] ?? 0, 'data' => $res['kvs'] ?? []];
            return [true, '', $list];
        }
        return [false, $res['common']['msg'] ?? '接口错误', $list];
    }

    public function setKv(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SET_KV, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getKvHistory(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GET_KV_HISTORY, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['kvs'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }


    // tarot odds 列表
    public function tarotOddsList()
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_TAROT_ODDS_LIST, ['json' => []]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['data'] ?? []];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    // tarot odds 编辑
    public function editTarotOdds(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_ODDS_EDIT, [
            'json' => ['tarot_odds_list' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getTarotTotalLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_TOTAL_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['info_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    // tarot total 新增
    public function addTarotTotalLimitConfig(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_TOTAL_LIMIT_CONFIG_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // tarot total 编辑
    public function editTarotTotalLimitConfig(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_TOTAL_LIMIT_CONFIG_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // tarot value 列表
    public function getTarotContributionLimit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_CONTRIBUTION_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['info_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    // tarot value 新增
    public function addTarotContributionLimit(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_CONTRIBUTION_LIMIT_CONFIG_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    // tarot value 编辑
    public function editTarotContributionLimit(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_TAROT_CONTRIBUTION_LIMIT_CONFIG_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function actWheelLotterySetAward(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACT_WHEEL_LOTTERY_SET_AWARD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function actWheelLotteryGetWeightInfo(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACT_WHEEL_LOTTERY_GET_WEIGHT_INFO, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['info'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function queryResourceList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_H5_RESOURCE_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['ResourceList'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function createH5Resource(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_H5_RESOURCE_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editH5Resource(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_H5_RESOURCE_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function propCardAdd(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, array_get($res, 'ids', [])];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function propCardEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function propCardConfigAdd(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_CONFIG_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, array_get($res, 'id', 0)];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function propCardConfigEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_CONFIG_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function reportList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_REPORT_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['report_details'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function getHorseValueList(array $params): array
    {
        $query = [
            'game_id'   => XsGlobalConfig::GAME_CENTER_ID_HORSE_RACE,
            'page_num'  => intval($params['page'] ?? 1),
            'page_size' => intval($params['limit'] ?? 15),
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_HORSE_VALUE_LIST, [
            'json' => $query
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['config_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function banUser(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_BAN_USER, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function addHorseValue(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HORSE_VALUE_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function editHorseValue(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HORSE_VALUE_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function banLog(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_BAN_LOG, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['ban_log'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function getUserDeviceInfo(int $uid): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GET_USER_DEVICE_INFO, [
            'json' => ['uid' => $uid]
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['users'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }


    public function forbiddenUser(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_FORBIDDEN_USER, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }


    public function getUserVip(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_VIP_LEVEL, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [
                true, [
                    'level'                 => intval($res['level'] ?? 0),
                    'forbidden_delay_hours' => intval($res['forbidden_delay_hours'] ?? 0),
                ]
            ];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function queryGameTotalLimitConfigList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_TOTAL_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['config_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function addGameTotalLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_TOTAL_LIMIT_CONFIG_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editGameTotalLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_TOTAL_LIMIT_CONFIG_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function queryGameContributionLimitConfigList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_CONTRIBUTION_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['config_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function addGameContributionLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_CONTRIBUTION_LIMIT_CONFIG_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editGameContributionLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GAME_CONTRIBUTION_LIMIT_CONFIG_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function sendCommodity($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_COMMODITY_SEND, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg']];
    }

    public function editIntimateRelationPayConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_INTIMATE_RELATION_PAY_CONFIG_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getDiamondList($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_DIAMOND_LIST, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['list']];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function modifyDiamondList($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_DIAMOND_LIST_MODIFY, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function actSendDiamondAward($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SEND_DIAMOND_AWARD, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        // 特殊code 文案需要单独处理
        // 1000013 表示余额不足
        if ($res['common']['err_code'] == '1000013') {
            $res['common']['msg'] = "大区的活动账户余额不足！<br /><br />大区的活动账户余额不足以发放本次钻石，请先给大区的活动账户申请预算，再来发放奖励。";
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function sendPropCard(array $params, string $admin): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_SEND, [
            'json' => [
                'list' => $params,
                'operator' => $admin
            ],
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['list'] ?? []];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function recyclePropCard(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_REMOVE, [
            'json'    => $params,
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function loginSmsCode($uid): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CMS_SMS_CODE, [
            'json' => [
                'uid' => $uid
            ]
        ]);

        if ($res['success'] && isset($res['sms_code'])) {
            return [true, '', $res];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function inviteCode($uid): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_INVITE_CODE, [
            'json' => [
                'uid' => $uid
            ]
        ]);

        if ($res['success'] && isset($res['code'])) {
            return [true, '', $res];
        }

        return [false, $res['msg'] ?? '接口错误', []];
    }

    public function removeFamilyMember($params)
    {
        $data = [
            'fid' => (int)$params['fid'],
            'member' => (int)$params['uid']
        ];
        list($result, $code) = $this->psRpc->call(PsRpc::API_REMOVE_FAMILY_MEMBER, [
                'json' => $data]
        );
        if (!isset($result['success']) || !$result['success']) {
            return [false, $result['msg']];
        }
        return [true, ''];
    }

    public function modifyFamily($params)
    {
        $data = [
            'fid' => (int) $params['fid'],
            'name' => $params['name'],
            'badge' => $params['badge'],
            'announcement' => $params['announcement'],
        ];
        if (!empty($params['uid'])) {
            $data['uid'] = (int)$params['uid'];
        }

        list($result, $code) = $this->psRpc->call(PsRpc::API_MODIFY_FAMILY, [
                'json' => $data]
        );
        if (!isset($result['success']) || !$result['success']) {
            return [false, $result['msg']];
        }
        return [true, ''];
    }

    public function setFamilyLv(array $params): array
    {
        list($result, $code) = $this->psRpc->call(PsRpc::API_SET_FAMILY_LV, ['json' => $params]);
        if (!isset($result['success']) || !$result['success']) {
            return [false, $result['msg']];
        }
        return [true, ''];
    }

    public function dismissFamily($params)
    {
        $data = [
            'fid' => (int)$params['fid'],
        ];
        list($result, $code) = $this->psRpc->call(PsRpc::API_DISMISS_FAMILY, [
                'json' => $data]
        );
        if (!isset($result['success']) || !$result['success']) {
            return [false, $result['msg']];
        }
        return [true, ''];
    }

    public function batchAgencyHunterGiftBagByUserType(array $params)
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_AGENCY_HUNTER_GIFT_BAG_BY_USER_TYPE_BATCH, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function addSlotContributionLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_ADD, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editSlotContributionLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_EDIT, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $params['id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function simulateBet(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_TEST, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }



    public function querySlotContributionLimitConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['config_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function queryGreedySkinList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_SKIN_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['skin_list'] ?? [], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', ['data' => [], 'total' => 0]];
    }

    public function querySlotItemWeight(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_ITEM_WEIGHT_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['item_list'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function editSlotItemWeight(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_SLOT_ITEM_WEIGHT_EDIT, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createGreedySkin(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_SKIN_ADD, [
            'json' => ['skin_info' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['skin_id'] ?? 0];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editGreedySkin(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_SKIN_EDIT, [
            'json' => ['skin_info' => $params]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function delGreedySkin(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_SKIN_DEL, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function expireGreedySkin(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_SKIN_EXPIRE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function queryMultiAnchorAwardConfigList(): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_MULTI_ANCHOR_AWARD_CONFIG_LIST, [
            'json' => []
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['list'] ?? []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function updateMultiAnchorAwardConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_MULTI_ANCHOR_AWARD_CONFIG_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function queryGameItemOddsList(array $params): array
    {
        [$res, $_] = $this->psRpc->call(PsRpc::API_GREEDY_BOX_ODDS_LIST, ['json' => $params]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['item_list'] ?? []];
        }
        return [false, '接口错误：' . $res['common']['msg'] ?? '接口错误'];
    }

    public function editGameItemOdds(array $params, int $gameId): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_GREEDY_BOX_ODDS_EDIT, [
            'json' => ['item_list' => $params, 'game_id' => $gameId]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function getMultiAnchorAwardConfig(int $bigAreaId): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_MULTI_ANCHOR_AWARD_CONFIG_GET, [
            'json' => ['big_area_id' => $bigAreaId]
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['config'] ?? []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    public function createRoomTop(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP, [
            'json' => $params
        ]);

        if ($res['data'] && $res['success'] === true) {
            return [true, $res['data']['id'] ?? ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function editRoomTop(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_MODIFY, [
            'json' => $params
        ]);

        if ($res['success'] === true) {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function cancelRoomTop(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_TOP_CANCEL, [
            'json' => $params
        ]);
        if ($res['success'] === true) {
            return [true, ''];
        }

        return [false, $res['msg'] ?? '接口错误'];
    }

    public function videoLiveStopModify(array $params): array
    {
        list($result, $code) = $this->psRpc->call(PsRpc::API_VIDEO_LIVE_STOP, [
                'json' => $params]
        );
        if (!isset($result['success']) || !$result['success']) {
            return [false, $result['msg'] ?? '接口错误'];
        }
        return [true, ''];
    }

    public function forbidApi($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_FORBID_ROOM, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function unforbidApi($data): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_UNFORBID_ROOM, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    public function createActHonourWall(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACT_HONOUR_WALL_ADD, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editActHonourWall(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACT_HONOUR_WALL_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function delActHonourWall(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ACT_HONOUR_WALL_DEL, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function roomBottomConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_BOTTOM_CONFIG, [
            'json' => $params
        ]);

        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }


    public function reLocateUserCountry(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_UPDATE_USER_COUNTRY, [
            'json' => $params,
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function setUserVipTime(array $params): array
    {
        $adminId = $params['admin_id'] ?: Helper::getSystemUid();
        $data = [
            'uid' => (int) $params['uid'],
            'vip_level' => (int) $params['level'],
            'validity_value' => (int) $params['day'],
            'remark' => $params['reason'],
            'operator' => Helper::getAdminName($adminId),
        ];
        list($result, $_) = $this->psRpc->call(PsRpc::API_OPERATE_USER_VIP, ['json' => $data]);
        if (!isset($result['err_code']) && $result['err_code'] != 0) {
            return [false, $result['msg']];
        }
        return [true, ''];
    }


    public function bindMobile(int $uid, string $mobile)
    {
        $data = [
            'uid' => (int) $uid,
            'mobile' => $mobile,
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_BIND_MOBILE, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['password'] ?? ''];
        }
        return [false, '接口错误：' . $res['common']['msg'], ''];
    }

    // 聊天室封面修改
    public function roomCoverChange(int $rid): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_COVER_CHANGE, [
            'json' => ['rid' => $rid],
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    // 关闭聊天室
    public function closeRoom(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_CLOSE, [
            'json' => $params,
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }

        return [false, '接口错误：' . $res['common']['msg'] ?? ''];
    }

    public function updatePkPropCardFirstGiftConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PK_PROP_CARD_FIRST_GIFT_CONFIG_UPDATE, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['config'] ?? []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }


    /**
     * 设置炫彩配置
     * @param array $params
     * @return array
     */
    public function setNameIdLightingConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_NAME_ID_LIGHTING_CONFIG_SET, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['config'] ?? []];
        }
        if(isset($res['common']) && empty($res['common'])){
            return [true, '', []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 查询炫彩配置
     * @param array $params
     * @return array
     */
    public function listNameIdLightingConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_NAME_ID_LIGHTING_CONFIG_LIST, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => isset($res['data']) ? $res['data'] : [], 'total' => isset($res['page']['total_count']) ? $res['page']['total_count'] : 0]];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 查询炫彩下发记录
     * @param array $params
     * @return array
     */
    public function listNameIdLightingLog(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_NAME_ID_LIGHTING_LOG_LIST, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => isset($res['data']) ? $res['data'] : [], 'total' => isset($res['page']['total_count']) ? $res['page']['total_count'] : 0]];
        }

        $msg = $res['common']['msg'] ?? '接口错误';
        isset($res['message']) && $msg = $res['message'];
        return [false, $msg, []];
    }

    /**
     * 炫彩下发
     * @param array $params
     * @return array
     */
    public function addNameIdLighting(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_NAME_ID_LIGHTING_ADD, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        if(isset($res['common']) && empty($res['common'])){
            return [true, '', []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 查询用户炫彩资源
     * @param array $params
     * @return array
     */
    public function listUserNameIdLighting(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_NAME_ID_LIGHTING_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => isset($res['data']) ? $res['data'] : [], 'total' => isset($res['page']['total_count']) ? $res['page']['total_count'] : 0]];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 失效用户炫彩资源
     * @param array $params
     * @return array
     */
    public function invalidUserNameIdLighting(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_USER_NAME_ID_LIGHTING_INVALID, [
            'json' => $params
        ]);

        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        if(isset($res['common']) && empty($res['common'])){
            return [true, '', []];
        }

        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 荣誉等级配置列表
     * @param array $params
     * @return array
     */
    public function honorLevelConfigList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['list'], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 荣誉等级创建
     * @param array $params
     * @return array
     */
    public function honorLevelConfigCreate(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_CONFIG_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 荣誉等级配置更新
     * @param array $params
     * @return array
     */
    public function honorLevelConfigUpdate(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_CONFIG_UPDATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 根据入参等级获取对应的荣誉等级配置
     * @param array $params
     * @return array
     */
    public function honorLevelGetConfig(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_CONFIG_GET, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res['data'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 荣誉等级下发记录列表
     * @param array $params
     * @return array
     */
    public function honorLevelSendList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_SEND_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['list'], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 荣誉等级下发接口(单个)
     * @param array $params
     * @return array
     */
    public function honorLevelSend(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_SEND, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 荣誉等级批量下发
     * @param array $params
     * @return array
     */
    public function honorLevelBatchSend(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_SEND_BATCH, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 用户荣誉等级管理列表
     * @param array $params
     * @return array
     */
    public function userHonorLevelManageList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_MANAGE_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['list'], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 失效用户的荣誉等级
     * @param array $params
     * @return array
     */
    public function userHonorLevelDisable(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_HONOR_LEVEL_DISABLE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', []];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 关系增值道具购买记录
     * @param array $params
     * @return array
     */
    public function propCardBuyRecordList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_PROP_CARD_BUY_RECORD_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['list'], 'total' => $res['page']['total_count'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 推荐位配置列表/搜索
     * @param array $params
     * @return array
     */
    public function getRecommendConfigList(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_RECOMMEND_CONFIG_LIST, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', ['data' => $res['list'], 'total' => $res['total'] ?? 0]];
        }
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 推荐位配置添加
     * @param array $params
     * @return array
     */
    public function recommendConfigCreate(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_RECOMMEND_CONFIG_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 推荐位配置编辑
     * @param array $params
     * @return array
     */
    public function recommendConfigModify(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_RECOMMEND_CONFIG_MODIFY, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 推荐位配置删除
     * @param array $params
     * @return array
     */
    public function recommendConfigDelete(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_RECOMMEND_CONFIG_DELETE, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 推荐位配置详情
     * @param array $params
     * @return array
     */
    public function recommendConfigDetail(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_RECOMMEND_CONFIG_DETAIL, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['data'] ?? []];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createUploadLogInstruction(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CREATE_UPLOAD_LOG_INSTRUCTION, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 创建定制表情
     * @param array $params
     * @return array
     */
    public function createCustomizedEmoticon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CREATE_CUSTOMIZED_EMOTICON, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 更新定制表情
     * @param array $params
     * @return array
     */
    public function updateCustomizedEmoticon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_UPDATE_CUSTOMIZED_EMOTICON, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 查询定制表情
     * @param array $params
     * @return array
     */
    public function queryCustomizedEmoticon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_QUERY_CUSTOMIZED_EMOTICON, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res];
        }
        
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 创建定制表情发放记录
     * @param array $params
     * @return array
     */
    public function createCustomizedEmoticonReward(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_CREATE_CUSTOMIZED_EMOTICON_REWARD, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    /**
     * 查询定制表情发放记录
     * @param array $params
     * @return array
     */
    public function queryCustomizedEmoticonReward(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_QUERY_CUSTOMIZED_EMOTICON_REWARD, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, '', $res];
        }
        
        return [false, $res['common']['msg'] ?? '接口错误', []];
    }

    /**
     * 失效定制表情发放记录
     * @param array $params
     * @return array
     */
    public function deactivateCustomizedEmoticonReward(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_DEACTIVATE_CUSTOMIZED_EMOTICON_REWARD, [
            'json' => $params
        ]);
        if (isset($res['common']['err_code']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function opActScore(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_OP_ACT_SCORE, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function createInteractiveEmoticon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_INTERACTIVE_EMOTICON_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function editInteractiveEmoticon(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_INTERACTIVE_EMOTICON_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function opInteractiveEmoticonListed(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_INTERACTIVE_EMOTICON_LISTED, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function soundEffectCreate(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_BACKGROUND_SOUND_EFFECT_CREATE, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function soundEffectEdit(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_BACKGROUND_SOUND_EFFECT_EDIT, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, $res['id'] ?? 0];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

    public function soundEffectDel(array $params): array
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_ROOM_BACKGROUND_SOUND_EFFECT_DEL, [
            'json' => $params
        ]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        return [false, $res['common']['msg'] ?? '接口错误'];
    }

}