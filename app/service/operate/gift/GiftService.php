<?php


namespace Imee\Service\Operate\Gift;


use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGiftProperty;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstGiftOperationLog;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCommodityTag;
use Imee\Models\Xs\XsDiyGiftConfig;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsGiftWall;
use Imee\Models\Xs\XsQuickGiftConfig;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstGiftUpload;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use OSS\OssClient;
use OSS\OssUpload;

class GiftService
{
    public function getListAndTotal(array $params, string $order, int $page, int $limit): array
    {
        $data = XsGift::getListAndTotal($this->getCondition($params), '*', $order, $page, $limit);

        if (empty($data['data'])) {
            return [];
        }

        $this->formatList($data['data']);

        return $data;
    }

    public function getLogList(int $id, int $page, int $limit): array
    {
        $data = XsstGiftOperationLog::getListAndTotal([['cid', '=', $id]], '*', 'id desc', $page, $limit);
        if (empty($data['data'])) {
            return [];
        }

        foreach ($data['data'] as &$item) {
            $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
            $item['admin'] = Helper::getAdminName($item['admin']);
            $item['type'] = $item['type'] == XsstGiftOperationLog::TYPE_ADD ? '新增' : '修改';
            $item['content'] = json_encode(json_decode($item['content'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return $data;
    }

    public function initGift(array $data, bool $update = false): array
    {
        if (!empty($data['is_diy']) && $data['is_diy'] == 1) {
            if (!empty($data['diy_type']) && $data['diy_type'] == 1) {
                $data['unity_sign_direction'] = 1; //保存时存0
                $data['unity_android_size'] = 0;
                $data['unity_ios_size'] = 0;
            }
        } else { // mock无效数据，为了过validate验证
            $data['diy_type'] = 1;
            $data['diy_group'] = 1;
            $data['preview_size'] = 0;
            $data['bg_size'] = 0;
            $data['icon_num'] = 1;
            $data['unity_sign_direction'] = 1;
            $data['unity_android_size'] = 0;
            $data['unity_ios_size'] = 0;
        }

        if ($update) {
            $id = $data['id'] ?? 0;
            if ($id < 1 || empty($rec = XsGift::findOne($id))) {
                throw new ApiException(ApiException::MSG_ERROR, '数据提交有误');
            }

            //以下是非编辑数据
            $data['with_end'] = $rec['with_end'];
            $data['gift_info'] = $rec;
        }

        $tabId = $data['tab_id'] ?? 0;
        if ($tabId != 5) {
            $data['is_customized'] = 0;
            $data['customized_gift_uid'] = 0;
        }
        if ($tabId == 11) {
            $data['is_secret_gift'] = 1;
        }

        if ($data['is_customized'] == 0) {
            $data['customized_gift_uid'] = 0;
        }

        if ($data['is_skin'] == 0) {
            $data['gid_basic'] = 0;
            $data['num_to_unlock'] = 0;
        }

        if ($data['is_privilege'] == 0) {
            $data['privilege_type'] = 0; //特权类型
            $data['family_lv'] = 0; //可用家族等级
        }

        return $data;
    }

    public function modifyTab(int $tabId, array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        $ids = array_unique($ids);
        $ids = array_values($ids);

        if (!in_array($tabId, array_keys(XsGift::$tabIdMap))) {
            throw new ApiException(ApiException::MSG_ERROR, 'tab err');
        }

        if (count($ids) > 2000) {
            throw new ApiException(ApiException::MSG_ERROR, '最多一次修改2000条礼物数据');
        }

        $msg = $this->checkGiftIds($ids);
        if ($msg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        [$res, $msg, $_] = XsGift::updateByWhere([
            ['id', 'IN', $ids]
        ], [
            'tab_id' => $tabId
        ]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $baseLog = [
            'content'  => json_encode(['tab_id' => $tabId]),
            'type'     => XsstGiftOperationLog::TYPE_UPDATE,
            'admin'    => Helper::getSystemUid(),
            'dateline' => time(),
        ];
        $insertLog = [];
        foreach ($ids as $id) {
            $insertLog[] = array_merge($baseLog, ['cid' => $id]);
        }
        XsstGiftOperationLog::addBatch($insertLog);
        return [];
    }

    private function checkGiftIds(array $gids): string
    {
        $tips = "%s,无法修改，请检查当前礼物id是否存在，是否属于当前app";

        $data = XsGift::getListByWhere([
            ['id', 'IN', $gids],
            ['app_id', '=', APP_ID],
        ], 'id');

        $diffIdsStr = '';

        if (empty($data)) {
            $diffIdsStr = implode(',', $gids);
            return sprintf($tips, $diffIdsStr);
        } else {
            $existIds = array_column($data, 'id');
            $diffIdsArr = array_diff($gids, $existIds);
            if ($diffIdsArr) {
                $diffIdsStr = implode(',', $diffIdsArr);
                return sprintf($tips, $diffIdsStr);
            } else {
                return $diffIdsStr;
            }
        }
    }

    public function getPropertyInfo(int $id): array
    {
        $gift = XsGift::findOne($id);
        if (!$gift) {
            return [];
        }
        $data = XsGiftProperty::getListByWhere([['gift_id', '=', $id]]);
        return ['name' => $gift['name'], 'data' => $data];
    }

    public function property(array $params): array
    {
        if (empty($params['id']) || empty($params['data'])) {
            throw new ApiException(ApiException::MSG_ERROR, '提交内容不能为空');
        }

        $data = $params['data'];
        $list = [];
        $areas = [];

        foreach ($data as $item) {
            $area = $item['bigarea'];
            if (in_array($area, $areas)) {
                throw new ApiException(ApiException::MSG_ERROR, '运营大区不能重复');
            }
            $areas[] = $area;

            $item['description'] = trim($item['description'] ?? '');
            $item['jump_page'] = trim($item['jump_page'] ?? '');

            $list[] = [
                'description' => $item['description'],
                'jump_page'   => $item['jump_page'],
                'bigarea'     => $area,
            ];
        }

        $data = [
            'gift_id' => (int)$params['id'],
            'list'    => $list,
        ];
        list($res, $_) = (new PsRpc())->call(PsRpc::API_EDIT_GIFT_DESC, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return ['id' => $data['gift_id'], 'after_json' => $list];
        }
        throw new ApiException(ApiException::MSG_ERROR, '接口错误：' . $res['common']['msg']);
    }

    public function getShowInfo(int $id): array
    {
        $gift = $this->getInfo($id);

        $gift['price'] = $gift['price_origin'];
        $gift['deleted'] = XsGift::$deletedMap[$gift['deleted']] ?? $gift['deleted'];

        $gift['display'] = is_array($gift['display']) ? $gift['display'] : explode(',', $gift['display']);
        $gift['tab_id'] = XsGift::$tabIdMap[$gift['tab_id']] ?? '';

        $gift['display'] = array_map(function ($v) {
            return XsGift::$displays[$v] ?? $v;
        }, $gift['display']);
        $gift['display'] = implode(',', $gift['display']);

        $gift['sub_display'] = array_map(function ($v) {
            return XsGift::$subDisplays[$v] ?? $v;
        }, $gift['sub_display']);
        $gift['sub_display'] = implode(',', $gift['sub_display']);

        $gift['type'] = XsGift::$types[$gift['type']] ?? $gift['type'];
        $gift['gift_type'] = XsGift::$giftTypes[$gift['gift_type']] ?? $gift['gift_type'];
        $gift['income_type'] = XsGift::$incomeTypes[$gift['income_type']] ?? $gift['income_type'];

        $gift['tag_url'] = $gift['tag_url'] ? Helper::getHeadUrl($gift['tag_url']) : '';
        if ($gift['tag_ids']) {
            $tags = explode(':', $gift['tag_ids']);
            $tags = XsCommodityTag::findByIds($tags, 'name');
            $gift['tag_name'] = implode(',', array_column($tags, 'name'));
        }

        $gift['vap_type'] = XsGift::$vapTypeMap[$gift['vap_type']] ?? $gift['vap_type'];

        if ($gift['is_diy'] == 1) {
            $gift['diy_type_txt'] = XsGift::$diyTypeMap[$gift['diy_type']] ?? $gift['diy_type'];
            if ($gift['diy_type'] == 2) {
                $gift['unity_sign_direction'] = XsGift::$unitySignMap[$gift['unity_sign_direction']] ?? '';
            }
        }

        if ($gift['is_privilege'] == 1) {
            $gift['privilege_type'] = XsGift::$privilegeType[$gift['privilege_type']] ?? $gift['privilege_type'];
        }

        if ($gift['is_interact_gift'] == 1) {
            $gift['png_url'] = Helper::getHeadUrl($gift['cover']);
            $gift['cover_start'] = Helper::getHeadUrl($gift['cover_start']);
            if (strstr($gift['cover_start'], '.webp')) {
                $gift['stype'] = 'webp';
            } elseif (strstr($gift['cover_start'], '.mp4')) {
                $gift['stype'] = 'mp4';
            } else {
                $gift['stype'] = 'png';
            }
            $gift['cover_end'] = Helper::getHeadUrl($gift['cover_end']);
            if (strstr($gift['cover_end'], '.webp')) {
                $gift['etype'] = 'webp';
            } elseif (strstr($gift['cover_end'], '.mp4')) {
                $gift['etype'] = 'mp4';
            } else {
                $gift['etype'] = 'png';
            }
            $gift['webp_url'] = Helper::getHeadUrl($gift['video_one']);
            $gift['mp4_url'] = Helper::getHeadUrl($gift['video_two']);
        } else {
            // 解决列表图片缓存问题
            $gift['png_url'] = Helper::getHeadUrl('static/gift_big/' . $gift['id'] . '.png');
            $gift['webp_url'] = Helper::getHeadUrl('static/gift_big/' . $gift['id'] . '.webp');
            $gift['mp4_url'] = Helper::getHeadUrl('static/gift_big/' . $gift['id'] . '.mp4');
            $gift['cover_start'] = Helper::getHeadUrl('static/gift_big/' . $gift['id'] . '.s.png');
            $gift['cover_end'] = Helper::getHeadUrl('static/gift_big/' . $gift['id'] . '.e.png');
        }
        $this->formatBlindGift($gift);

        return $gift;
    }

    public function getUploadInfo($gid): array
    {
        $list = XsstGiftUpload::getListByWhere([['gid', '=', $gid]]);
        return array_column($list, 'path', 'type');
    }

    public function getInfo(int $id, string $ac = ''): array
    {
        $gift = XsGift::findOne($id);
        if (!$gift) {
            return [];
        }

        $gift['excludes'] = empty($gift['excludes']) ? [] : explode(',', $gift['excludes']);
        $gift['sub_display'] = empty($gift['sub_display']) ? ['all'] : explode(',', $gift['sub_display']);
        $gift['display'] = empty($gift['display']) ? [] : explode(',', $gift['display']);

        $gift['price_origin'] = $gift['price'];
        $gift['price'] = intval($gift['price']);
        $gift['price2'] = bcmul(bcsub($gift['price_origin'], $gift['price'], 2), 100);

        $gift['tag_url'] = $gift['tag_url'] ? 1 : 0;

        $tags = $gift['tag_ids'] ? explode(':', $gift['tag_ids']) : [];
        $gift['tag1'] = $tags[0] ?? '';
        $gift['tag2'] = $tags[1] ?? '';

        $gift['xratio'] = isset($gift['xratio']) && $gift['xratio'] !== ''
            ? intval(round($gift['xratio'] * 100))
            : $gift['xratio'];

        $gift['is_diy'] = "0";

        if ($gift['category'] == 'diy') {
            $gift['is_diy'] = "1";
            $config = XsDiyGiftConfig::findOneByWhere([['gid', '=', $id]]);
            if ($config) {
                $gift['bg_mp4'] = $config['bg'];
                $gift['bg_url'] = Helper::getHeadUrl($config['bg']);
                $gift['bg_size'] = $config['bg_size'];
                $gift['preview_size'] = $config['preview_size'];
                $gift['icon_num'] = $config['icon_num'];
                $gift['diy_group'] = $config['group_id'];
                $gift['diy_type'] = $config['diy_type'];
                $gift['unity_sign_direction'] = $config['unity_sign_direction'];
                $gift['unity_android_size'] = $config['unity_android_size'];
                $gift['unity_ios_size'] = $config['unity_ios_size'];
            }
        }

        $gift['vap_type'] = $gift['vap_type'] ?? '';

        $fields = ['tab_id', 'is_relation_gift', 'is_skin', 'is_named', 'is_combo', 'is_feed_gift', 'is_customized', 'relation_type', 'relation_gift_lv', 'privilege_type'];
        foreach ($fields as $field) {
            isset($gift[$field]) && $gift[$field] = (string)$gift[$field];
        }
        $gift['is_interact'] = (string)$gift['is_interact_gift'];

        $extend = json_decode($gift['extend'], true);
        if ($gift['is_relation_gift'] == 1) {
            $gift['relation_type'] = strval($extend['relation_type'] ?? '');
            $gift['relation_gift_type'] = strval($extend['relation_gift_type'] ?? '');
            $gift['relation_gift_lv'] = strval($extend['relation_gift_lv'] ?? '');
            $gift['relation_gift_num'] = $extend['relation_gift_num'] ?? '';
        }
        $gift['is_privilege'] = '0';
        if ($gift['privilege_type'] > 0) {
            $gift['is_privilege'] = '1';
            $gift['family_lv'] = (string)($extend['family_lv'] ?? '');
        }
        if ($gift['is_blind_box'] == 1) {
            $blindGifts = $extend['gifts'] ?? [];
            foreach ($blindGifts as $k => &$item) {
                if ($item['rare_type'] == 2) {
                    $gift['super_gift_id'] = $item['gift_id'];
                    unset($blindGifts[$k]);
                } elseif ($item['rare_type'] == 3) {
                    $gift['jackpot_gift_id'] = $item['gift_id'];
                    unset($blindGifts[$k]);
                }
                $item['rare_type'] = (string)$item['rare_type'];
                $item['gift_id'] = (string)$item['gift_id'];
            }
            $gift['gifts'] = array_values($blindGifts);

            if ($ac == 'edit') {
                if (!empty($gift['super_gift_id'])) {
                    $tmpGift = XsGift::findOne($gift['super_gift_id']);
                    $gift['super_gift_id'] = ($tmpGift['name'] ?? '') . ',' . $gift['super_gift_id'];
                }
                if (!empty($gift['jackpot_gift_id'])) {
                    $tmpGift = XsGift::findOne($gift['jackpot_gift_id']);
                    $gift['jackpot_gift_id'] = ($tmpGift['name'] ?? '') . ',' . $gift['jackpot_gift_id'];
                }
            }
        }

        return $gift;
    }

    /**
     * 礼物创建（默认是下架）
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function create(array $params): array
    {
        $data = $this->validate($params);

        [$res, $id] = XsGift::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        if ($params['tag_url'] == 1) {
            $data['tag_url'] = str_replace('{gid}', $id, XsGift::$uploadTypeMap['hpng']);
            XsGift::edit($id, ['tag_url' => $data['tag_url']]);
        }

        if ($params['is_diy'] == 1) {
            $diy = [
                'gid'                  => $id,
                'diy_type'             => $params['diy_type'],
                'bg_size'              => $params['bg_size'] ?? 0,
                'preview_size'         => $params['preview_size'] ?? 0,
                'icon_num'             => $params['icon_num'],
                'group_id'             => $params['diy_group'],
                'unity_sign_direction' => $params['unity_sign_direction'] ?? 0,
                'unity_android_size'   => $params['unity_android_size'] ?? 0,
                'unity_ios_size'       => $params['unity_ios_size'] ?? 0,
            ];
            XsDiyGiftConfig::add($diy);
            $data[XsDiyGiftConfig::getTableName()] = $diy;
        }

        $rec = [
            'cid'     => $id,
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'type'    => XsstGiftOperationLog::TYPE_ADD,
            'admin'   => $params['admin_uid'],
        ];
        XsstGiftOperationLog::add($rec);

        return ['id' => $id, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->validate($params, true);

        $id = $params['id'];
        [$res, $msg] = XsGift::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $before = $params['gift_info'];

        if ($params['is_diy'] == 1) {
            if ($config = XsDiyGiftConfig::findOneByWhere([['gid', '=', $id]])) {
                $before[XsDiyGiftConfig::getTableName()] = $config;
            }
            $diy = [
                'diy_type'             => $params['diy_type'],
                'bg_size'              => $params['bg_size'] ?? 0,
                'preview_size'         => $params['preview_size'] ?? 0,
                'icon_num'             => $params['icon_num'],
                'group_id'             => $params['diy_group'],
                'unity_sign_direction' => $params['unity_sign_direction'] ?? 0,
                'unity_android_size'   => $params['unity_android_size'] ?? 0,
                'unity_ios_size'       => $params['unity_ios_size'] ?? 0,
            ];
            XsDiyGiftConfig::addRow($diy, ['gid' => $id]);
            $data[XsDiyGiftConfig::getTableName()] = $diy;
        }

        $rec = [
            'cid'     => $id,
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'type'    => XsstGiftOperationLog::TYPE_UPDATE,
            'admin'   => $params['admin_uid'],
        ];
        XsstGiftOperationLog::add($rec);

        return ['id' => $id, 'before_json' => $before, 'after_json' => $data];
    }

    public function review($gid, $deleted): array
    {
        if (!$gid) {
            return [false, 'gid is null'];
        }
        if (!in_array($deleted, [XsGift::DELETE_YES, XsGift::DELETE_NO])) {
            return [false, 'deleted is error'];
        }

        $info = XsGift::findOne($gid);
        if (!$info) {
            return [false, 'id is error'];
        }

        if ($info['deleted'] == $deleted) {
            return [false, '状态没有变化'];
        }

        $data = [
            'gid'     => $gid,
            'deleted' => $deleted,
        ];
        list($rpcRes, $rpcMsg) = (new PsService())->updateGiftStatus($data);
        if (!$rpcRes) {
            return [false, $rpcMsg];
        }

        $rec = [
            'cid'     => $gid,
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'type'    => XsstGiftOperationLog::TYPE_UPDATE,
            'admin'   => Helper::getSystemUid(),
        ];
        XsstGiftOperationLog::add($rec);

        return [true, ''];
    }

    private function validate(array $data, bool $update = false): array
    {
        $id = intval($data['id'] ?? 0);
        $isCustomized = intval($data['is_customized'] ?? 0);
        $relationGiftType = intval($data['relation_gift_type'] ?? 0);
        $relationGiftLv = intval($data['relation_gift_lv'] ?? 0);
        $isRelationGift = intval($data['is_relation_gift'] ?? 0);
        $relationType = intval($data['relation_type'] ?? 1);
        $incomeType = $data['income_type'] ?? '';
        $giftType = $data['gift_type'] ?? '';
        $type = $data['type'] ?? '';
        $isDiy = $data['is_diy'] ?? '';
        $isPrivilege = intval($data['is_privilege'] ?? 0);
        $isBlindBox = intval($data['is_blind_box'] ?? 0);

        $category = $isDiy == 1 ? 'diy' : 'normal';

        $relationGiftNum = $data['relation_gift_num'] ?? '';
        if ($relationGiftType == 1) {
            if (!filter_var($relationGiftNum, FILTER_VALIDATE_INT)) {
                throw new ApiException(ApiException::MSG_ERROR, '收到奖励需要的礼物数量,需输入大于0的正整数');
            }
            if (!in_array($relationGiftLv, [5, 6])) {
                throw new ApiException(ApiException::MSG_ERROR, '如果选择关系奖励礼物时，选项只能选择lv5和lv6');
            }
        }

        $extend = '';
        if ($isRelationGift) {
            $extend = [];
            $extend['relation_type'] = $relationType;
            $extend['relation_gift_type'] = $relationGiftType;
            $extend['relation_gift_lv'] = $relationGiftLv;
            $extend['relation_gift_num'] = $relationGiftType == 1 ? intval($relationGiftNum) : 0;
            $extend = json_encode($extend, JSON_UNESCAPED_UNICODE);
        } elseif ($isPrivilege == 1) {
            $extend = [
                'family_lv' => intval($data['family_lv'] ?? 0)
            ];
            $extend = json_encode($extend);
        } elseif ($isBlindBox == 1) {
            $gifts = $data['gifts'] ?? [];
            if (count($gifts) != 5) {
                throw new ApiException(ApiException::MSG_ERROR, '盲盒礼物配置数量只能是5个');
            }
            foreach ($gifts as &$v) {
                if (empty($v['gift_id']) || $v['gift_id'] < 1 || !isset($v['rare_type']) || !in_array($v['rare_type'], [0, 1])) {
                    throw new ApiException(ApiException::MSG_ERROR, '请完善 盲盒礼物配置');
                }
                $v['gift_id'] = (int)$v['gift_id'];
                $v['rare_type'] = (int)$v['rare_type'];
            }

            $gids = array_column($gifts, 'gift_id');
            if (count($gifts) != count(array_unique($gids)) || in_array($data['super_gift_id'], $gids) || in_array($data['jackpot_gift_id'], $gids) || $data['super_gift_id'] == $data['jackpot_gift_id']) {
                throw new ApiException(ApiException::MSG_ERROR, '盲盒礼物配置 不能重复礼物ID');
            }
            if (empty($data['super_gift_id']) || empty($data['jackpot_gift_id'])) {
                throw new ApiException(ApiException::MSG_ERROR, '盲盒礼物配置 不能缺少 超级稀缺礼物ID|jackpot礼物ID');
            }
            $tmpId = explode(',', $data['super_gift_id']);
            $data['super_gift_id'] = end($tmpId);
            $tmpId2 = explode(',', $data['jackpot_gift_id']);
            $data['jackpot_gift_id'] = end($tmpId2);

            if (!is_numeric($data['super_gift_id']) || !is_numeric($data['jackpot_gift_id'])) {
                throw new ApiException(ApiException::MSG_ERROR, '盲盒礼物配置 请选择正常的 超级稀缺礼物ID|jackpot礼物ID');
            }

            $gids[] = $data['super_gift_id'];
            $gids[] = $data['jackpot_gift_id'];
            if ($update && in_array($id, $gids)) {
                throw new ApiException(ApiException::MSG_ERROR, '盲盒礼物配置 礼物ID不能是自己');
            }

            $commodities = XsCommodity::getListByWhere([['type', '=', 'gift'], ['ext_id', 'in', $gids]], 'cid,ext_id');
            $commodities = array_column($commodities, 'cid', 'ext_id');
            $gifts[] = ['gift_id' => (int)$data['super_gift_id'], 'rare_type' => 2];
            $gifts[] = ['gift_id' => (int)$data['jackpot_gift_id'], 'rare_type' => 3];
            $gifts = array_map(function ($item) use ($commodities) {
                $item['commodity_id'] = $commodities[$item['gift_id']] ?? 0;
                return $item;
            }, $gifts);

            $extend = json_encode(['gifts' => $gifts]);
        }

        if (!in_array($giftType, array('normal', 'coin'))) {
            throw new ApiException(ApiException::MSG_ERROR, '新增礼物类型非法');
        }

        if (!in_array($incomeType, array('money', 'coin', 'union_gold', 'union_active'))) {
            throw new ApiException(ApiException::MSG_ERROR, '新增礼物类型非法');
        }

        $price = $data['price'] ?? 0;
        $price2 = $data['price2'] ?? 0;
        if (!$update) {
            if ($price < 0 || $price2 < 0) {
                throw new ApiException(ApiException::MSG_ERROR, '价格不允许为负数');
            }
        }

        if ($incomeType == 'money' && $giftType != 'normal') {
            throw new ApiException(ApiException::MSG_ERROR, '礼物财富类型填写出错');
        }

        if ($giftType == 'coin' && $incomeType != 'coin') {
            throw new ApiException(ApiException::MSG_ERROR, '礼物财富类型填写出错');
        }

        /*if ($type == 'normal' && ($data['with_end'] ?? 0) != 1) {
            throw new ApiException(ApiException::MSG_ERROR, 'normal情况下with_end必须为1');
        }*/

        if ($price2 > 0) {
            $decimalPart = bcdiv($price2, '100', 2);
            $price = bcadd($price, $decimalPart, 2);
            if (!$update) {
                $priceInCents = bcmul($price, '100', 2);
                $priceInCentsInt = bcdiv($priceInCents, '1', 0);
                if (bccomp($priceInCents, $priceInCentsInt, 2) !== 0) {
                    throw new ApiException(ApiException::MSG_ERROR, '该价格有精度损失问题，请重新输入：' . $price);
                }
            }
            $version = 1;
        } else {
            $version = 0;
        }

        if ($price <= 0) {
            throw new ApiException(ApiException::MSG_ERROR, '价格必须大于0');
        }

        if ($category == 'diy') {
            if (empty($data['diy_group'])) throw new ApiException(ApiException::MSG_ERROR, 'diy礼物请选择分组');
            if (($data['diy_type'] ?? 0) != 2) {
                if (empty($data['bg_mp4'])) throw new ApiException(ApiException::MSG_ERROR, 'diy礼物请上传预览动画背景mp4');
                if (empty($data['preview_size'])) throw new ApiException(ApiException::MSG_ERROR, 'diy礼物请输入礼物预览图大小');
                if (empty($data['bg_size'])) throw new ApiException(ApiException::MSG_ERROR, 'diy礼物输入请礼物背景大小');
            }
            if (empty($data['icon_num'])) throw new ApiException(ApiException::MSG_ERROR, 'diy礼物输入请支持头像个数');
        }

        $xratio = intval($data['xratio'] ?? 0);
        $xratio = $xratio / 100;
        if ($xratio == 0) {
            $xratio = '';
        }

        $customizedGiftUid = intval($data['customized_gift_uid'] ?? 0);
        $tabId = intval($data['tab_id'] ?? 0);
        $tabId != 5 && $isCustomized = 0;
        $isCustomized == 0 && $customizedGiftUid = 0;

        if ($isCustomized == 1 && empty(XsUserProfile::findOne($customizedGiftUid))) {
            throw new ApiException(ApiException::MSG_ERROR, '输入的礼物拥有者ID有误');
        }

        $display = $data['display'];
        $isInteractGift = intval($data['is_interact'] ?? 0);

        $isRoom = in_array('room', $display);
        // display为video或者chat时暂时不支持互动礼物
        if ($isInteractGift && (in_array('video', $display) || in_array('chat', $display))) {
            throw new ApiException(ApiException::MSG_ERROR, 'display为video或者chat时暂不支持互动礼物，请取消勾选再保存');
        }

        if ($tabId == 7 && empty($isInteractGift)) {
            throw new ApiException(ApiException::MSG_ERROR, 'tab为Interact时，是否麦位互动礼物必须选为是');
        }

        $isSkin = intval($data['is_skin'] ?? 0);
        $gidBasic = intval($data['gid_basic'] ?? 0);
        $numToUnlock = intval($data['num_to_unlock'] ?? 0);
        if ($isSkin) {
            if (!$gidBasic || !$numToUnlock) {
                throw new ApiException(ApiException::MSG_ERROR, '礼物皮肤参数缺少');
            }

            $gidGift = XsGift::findOne($gidBasic);
            $gidDisplay = !empty($gidGift['display']) ? explode(',', $gidGift['display']) : [];
            $notSame = !!array_diff($display, $gidDisplay) || !!array_diff($gidDisplay, $display);
            if (!$gidGift || $notSame) {
                throw new ApiException(ApiException::MSG_ERROR, '不存在关联礼物或者关联礼物跟当前类型不一致');
            }
        }

        $subDisplay = $data['sub_display'] ?? [];
        $isSend = in_array('finger_guess', $subDisplay);

        if ($isSend && !$isRoom) {
            throw new ApiException(ApiException::MSG_ERROR, '猜拳礼物的display字段必须包含room');
        }

        $subDisplay = implode(',', $subDisplay);
        if (empty($subDisplay)) {
            $subDisplay = 'all';
        }

        if (($data['tag_url'] ?? '') == 1) {
            $tagstr = 'static/gift_big/' . $id . '.h.png';
        } else {
            $tagstr = '';
        }

        $tagIds = [];
        if ($data['tag1'] ?? '') {
            $tagIds[] = $data['tag1'];
        }
        if ($data['tag2'] ?? '') {
            $tagIds[] = $data['tag2'];
        }

        $tagIds = implode(':', $tagIds);
        $isLucky = intval($data['is_lucky'] ?? 0);
        $isFeedGift = intval($data['is_feed_gift'] ?? 0);
        $isNamed = intval($data['is_named'] ?? 0);
        $isCombo = intval($data['is_combo'] ?? 0);
        $isSecretGift = intval($data['is_secret_gift'] ?? 0);

        if ($tabId == 11 && empty($isSecretGift)) {
            throw new ApiException(ApiException::MSG_ERROR, 'tab为私密礼物时，是否私密礼物必须选为是');
        }
        if ($isSecretGift && ($isBlindBox || $isLucky || $isSkin || $tabId == 3 || mb_strpos($data['name'] ?? '', '国旗') !== false)) {
            throw new ApiException(ApiException::MSG_ERROR, '私密礼物和【盲盒礼物】、【幸运礼物】、【皮肤礼物】、【国旗礼物】均互斥');
        }
        if ($isSecretGift == 1 && $update) {
            $this->checkSecretGift($id);
        }

        if ($isRelationGift && ($isLucky || $isFeedGift || $isInteractGift || $isCustomized || $isSkin || $isNamed)) {
            throw new ApiException(ApiException::MSG_ERROR, '关系礼物和【定制礼物】、【热门礼物】、【麦位互动礼物】【幸运礼物】【皮肤礼物】【冠名礼物】均互斥');
        }
        if ($isPrivilege && ($isRelationGift || $isLucky || $isFeedGift || $isInteractGift || $isCustomized || $isSkin || $isNamed)) {
            throw new ApiException(ApiException::MSG_ERROR, '特权礼物和【关系礼物】、【定制礼物】、【热门礼物】、【麦位互动礼物】【幸运礼物】【皮肤礼物】【冠名礼物】均互斥');
        }
        if ($isBlindBox && ($isPrivilege || $isRelationGift || $isLucky || $isFeedGift || $isInteractGift || $isCustomized || $isSkin || $isNamed || $isCombo)) {
            throw new ApiException(ApiException::MSG_ERROR, '盲盒礼物和【特权礼物】、【关系礼物】、【定制礼物】、【热门礼物】、【麦位互动礼物】、【幸运礼物】、【皮肤礼物】、【冠名礼物】、【连击礼物】均互斥');
        }

        $data = [
            'id'                  => $id,
            'name'                => $data['name'] ?? '',
            'name_zh_tw'          => $data['name_zh_tw'] ?? '',
            'name_en'             => $data['name_en'] ?? '',
            'name_ar'             => $data['name_ar'] ?? '',
            'name_ms'             => $data['name_ms'] ?? '',
            'name_th'             => $data['name_th'] ?? '',
            'name_id'             => $data['name_id'] ?? '',
            'name_vi'             => $data['name_vi'] ?? '',
            'name_ko'             => $data['name_ko'] ?? '',
            'name_tr'             => $data['name_tr'] ?? '',
            'name_ja'             => $data['name_ja'] ?? '',
            'name_hi'             => $data['name_hi'] ?? '',
            'name_bn'             => $data['name_bn'] ?? '',
            'name_ur'             => $data['name_ur'] ?? '',
            'name_tl'             => $data['name_tl'] ?? '',
            'price'               => $price,
            'display'             => implode(',', $display),
            'sub_display'         => $subDisplay,
            'type'                => $type,
            'with_end'            => intval($data['with_end'] ?? 1),
            'size'                => intval($data['size'] ?? 0),
            'size_big'            => intval($data['size_big'] ?? 0),
            'ordering'            => intval($data['ordering'] ?? 0),
            'deleted'             => 1,
            'title'               => intval($data['title'] ?? 0),
            'naming'              => $data['name'] ?? '',
            'xratio'              => $xratio,
            'xtype'               => $data['xtype'] ?? 'multiframe',
            'excludes'            => implode(',', $data['excludes'] ?? []),
            'tag_url'             => $tagstr,
            'gift_type'           => $giftType,
            'income_type'         => $incomeType,
            'version'             => $version,
            'vap_type'            => $data['vap_type'] ?? '',
            'vap_size'            => intval($data['vap_size'] ?? 0),
            'vap_header'          => intval($data['vap_header'] ?? 0),
            'vap_header_start'    => intval($data['vap_header_start'] ?? 0),
            'vap_header_end'      => intval($data['vap_header_end'] ?? 0),
            'tag_ids'             => $tagIds,
            'jump_page'           => $data['jump_page'] ?? '',
            'description'         => $data['description'] ?? '',
            'is_combo'            => $isCombo,
            'app_id'              => APP_ID,
            'category'            => $category,
            'is_skin'             => $isSkin,
            'is_named'            => $isNamed,
            'gid_basic'           => $gidBasic,
            'num_to_unlock'       => $numToUnlock,
            'price_ratio'         => -1,
            'is_lucky'            => $isLucky,
            'tab_id'              => $tabId,
            'is_customized'       => $isCustomized,
            'customized_gift_uid' => $customizedGiftUid,
            'is_app_public'       => 0,
            'icon'                => '',
            'delete_from'         => 0,
            'is_interact_gift'    => $isInteractGift,
            'is_feed_gift'        => $isFeedGift,
            'is_relation_gift'    => $isRelationGift,
            'extend'              => $extend,
            'privilege_type'      => $data['privilege_type'] ?? 0,
            'is_blind_box'        => $isBlindBox,
            'is_secret_gift'      => $isSecretGift,
        ];

        if ($update) {
            $fields = ['is_app_public', 'icon', 'app_id', 'delete_from', 'price_ratio', 'deleted', 'category', 'xtype', 'income_type', 'gift_type', 'title', 'size', 'size_big', 'price', 'sub_display', 'vap_size', 'vap_header', 'vap_header_start', 'vap_header_end'];
            foreach ($fields as $field) {
                unset($data[$field]);
            }

            if ($id < 1 || !($rec = XsGift::findOne($id))) {
                throw new ApiException(ApiException::MSG_ERROR, '当前数据不存在，请确认');
            }

            if ($rec['deleted'] == XsGift::DELETE_NO) {
                if ($rec['is_customized'] != $isCustomized) {
                    throw new ApiException(ApiException::MSG_ERROR, '定制礼物配置必须在下架后才能修改');
                }
            }
        }

        return $data;
    }

    private function checkSecretGift(int $gid): void
    {
        $data = XsGiftWall::getGeneratorListByWhere([], 'collect_config');
        foreach ($data as $collects) {
            foreach ($collects as $collect) {
                $gifts = @json_decode($collect['collect_config'], true);
                if (empty($gifts['GiftCollects'])) {
                    continue;
                }

                foreach ($gifts['GiftCollects'] as $gift) {
                    if (($gift['Id'] ?? 0) == $gid) {
                        throw new ApiException(ApiException::MSG_ERROR, '该礼物已在礼物墙中，无法配置成私密礼物');
                    }
                    if (($gift['id'] ?? 0) == $gid) {
                        throw new ApiException(ApiException::MSG_ERROR, '该礼物已在礼物墙中，无法配置成私密礼物');
                    }
                }
            }
        }

        $data = XsQuickGiftConfig::findOneByWhere([['gift_id', '=', $gid]], 'gift_id');
        if ($data) {
            throw new ApiException(ApiException::MSG_ERROR, '该礼物已在快捷礼物中，无法配置成私密礼物');
        }
    }

    private function formatList(array &$data): void
    {
        $ids = array_column($data, 'id');

        $diyConfig = XsDiyGiftConfig::getListByWhere([['gid', 'in', $ids]], 'gid,diy_type');
        $diyConfig = array_column($diyConfig, null, 'gid');

        $now = time();
        foreach ($data as &$item) {
            $item['tag_name'] = '';

            $extend = empty($item['extend']) ? [] : @json_decode($item['extend'], true);
            $item = array_merge($item, $extend);

            if ($item['tag_ids']) {
                $tags = explode(':', $item['tag_ids']);
                $tags = XsCommodityTag::findByIds($tags, 'name');
                $item['tag_name'] = implode(',', array_column($tags, 'name'));
            }

            if ($item['is_interact_gift'] == 1) {
                $item['png_url'] = Helper::getHeadUrl($item['cover']);
                $item['cover_start_url'] = $this->generateMediaHtml($item['cover_start']);
                $item['cover_end_url'] = $this->generateMediaHtml($item['cover_end']);
                $item['cover_start'] = Helper::getHeadUrl($item['cover_start']);
                $item['cover_end'] = Helper::getHeadUrl($item['cover_end']);
                $item['webp_url'] = Helper::getHeadUrl($item['video_one']);
                $item['mp4_url'] = Helper::getHeadUrl($item['video_two']);
            } else {
                // 解决列表图片缓存问题
                $item['png_url'] = Helper::getHeadUrl('static/gift_big/' . $item['id'] . '.png');
                $item['webp_url'] = Helper::getHeadUrl('static/gift_big/' . $item['id'] . '.webp');
                $item['mp4_url'] = Helper::getHeadUrl('static/gift_big/' . $item['id'] . '.mp4');
                $coverStartUrl = 'static/gift_big/' . $item['id'] . '.s.png';
                $coverEndUrl = 'static/gift_big/' . $item['id'] . '.e.png';
                $item['cover_start_url'] = $this->generateMediaHtml($coverStartUrl);
                $item['cover_end_url'] = $this->generateMediaHtml($coverEndUrl);
                $item['cover_start'] = Helper::getHeadUrl($coverStartUrl);
                $item['cover_end'] = Helper::getHeadUrl($coverEndUrl);
                
            }

            $item['png_url'] .= '?time=' . $now;
            $item['webp_url'] .= '?time=' . $now;
            $item['mp4_url'] .= '?time=' . $now;
            $item['cover_start'] .= '?time=' . $now;
            $item['cover_end'] .= '?time=' . $now;

            $item['is_diy'] = 0;
            if ($item['category'] == 'diy') {
                $item['is_diy'] = 1;
                $config = $diyConfig[$item['id']] ?? [];
                if ($config) {
                    $item['diy_type'] = $config['diy_type'];
                }
            }

            $item['display'] = explode(',', $item['display']);
            $item['sub_display'] = explode(',', $item['sub_display']);
            $item['is_interact'] = $item['is_interact_gift'] = (string)$item['is_interact_gift'];
            $item['excludes'] = $item['excludes'] ? explode(',', $item['excludes']) : [];

            $arr = [];
            if ($item['is_relation_gift'] == 1) {
                $arr[] = '是';
                $arr[] = XsGift::$relationType[$item['relation_type']] ?? $item['relation_type'];
                $arr[] = 'lv' . $item['relation_gift_lv'];
            } else {
                $arr[] = '否';
            }
            $item['is_relation_gift_txt'] = implode(',', $arr);

            $item['is_privilege'] = '0';
            if ($item['privilege_type'] > 0) {
                $item['is_privilege'] = '1';
            }

            if ($item['img_update_time'] > 0) {
                $item['img_update_time'] = date('Y-m-d H:i:s', $item['img_update_time']);
            } else {
                $item['img_update_time'] = '';
            }
            $this->formatBlindGift($item);
        }
    }

    private function formatBlindGift(array &$gift): void
    {
        if ($gift['is_blind_box'] == 1) {
            $extend = empty($gift['extend']) ? [] : @json_decode($gift['extend'], true);
            $gifts = $extend['gifts'] ?? [];
            $str = [];
            foreach ($gifts as $v) {
                if ($v['rare_type'] == 2) {
                    $gift['super_gift_id'] = (string)$v['gift_id'];
                    $tmpGift = XsGift::findOne($v['gift_id']);
                    $gift['super_gift_id_txt'] = ($tmpGift['name'] ?? '') . ',' . $v['gift_id'];
                    continue;
                } elseif ($v['rare_type'] == 3) {
                    $gift['jackpot_gift_id'] = (string)$v['gift_id'];
                    $tmpGift = XsGift::findOne($v['gift_id']);
                    $gift['jackpot_gift_id_txt'] = ($tmpGift['name'] ?? '') . ',' . $v['gift_id'];
                    continue;
                }
                $str[] = sprintf('可开出礼物ID：%s，是否稀缺：%s', $v['gift_id'], $v['rare_type'] == 1 ? '是' : '否');
            }
            $gift['gifts'] = implode('<br/>', $str);
            $gift['gifts_txt'] = implode('；', $str);
        } else {
            $gift['gifts'] = '';
            $gift['gifts_txt'] = '';
            $gift['super_gift_id'] = '';
            $gift['jackpot_gift_id'] = '';
        }
    }

    public function upload(array $params): array
    {
        $id = $params['id'];
        $list = $params['list'] ?? '';
        $start = $params['spng'] ?? '';
        $end = $params['epng'] ?? '';
        $webp = $params['webp'] ?? '';
        $mp4 = $params['mp4'] ?? '';
        $update = [];
        $list && $update['icon'] = $list;

        $gift = XsGift::findOne($id);
        if ($gift['is_interact_gift'] == 1) {
            $list && $update['cover'] = $list;
            $start && $update['cover_start'] = $start;
            $end && $update['cover_end'] = $end;
            $webp && $update['video_one'] = $webp;
            $mp4 && $update['video_two'] = $mp4;
        }
        if ($update) {
            [$res, $msg] = XsGift::edit($id, $update);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }
        } else {
            return [];
        }

        $before = [];
        foreach ($update as $k => $_) {
            $before[$k] = $gift[$k] ?? '';
        }

        return ['id' => $id, 'before_json' => $before, 'after_json' => $update];
    }

    private function getCondition(array $params): array
    {
        $condition = [];
        $condition[] = ['app_id', '=', APP_ID];

        if (!empty($params['id'])) {
            $condition[] = ['id', '=', (int)$params['id']];
        }

        if (!empty($params['name'])) {
            $condition[] = ['name', 'like', $params['name']];
        }

        if (isset($params['deleted'])) {
            $condition[] = ['deleted', '=', (int)$params['deleted']];
        }

        if (!empty($params['display'])) {
            $condition[] = ['display', 'like', $params['display']];
        }

        if (!empty($params['gift_type'])) {
            $condition[] = ['gift_type', '=', $params['gift_type']];
        }

        if (!empty($params['price_start']) && is_numeric($params['price_start'])) {
            $condition[] = ['price', '>=', $params['price_start']];
        }

        if (!empty($params['price_end']) && is_numeric($params['price_end'])) {
            $condition[] = ['price', '<=', $params['price_end']];
        }

        if (!empty($params['tab_id'])) {
            $condition[] = ['tab_id', '=', $params['tab_id']];
        }

        if (!empty($params['customized_gift_uid']) && is_numeric($params['customized_gift_uid'])) {
            $condition[] = ['customized_gift_uid', '=', $params['customized_gift_uid']];
        }

        if (!empty($params['bigarea_id'])) {
            if ($params['bigarea_id'] == 'cn') {
                $ids = [];
                foreach (['zh_cn', 'zh_tw'] as $lang) {
                    $gifts = XsGift::getListByWhere([['excludes', 'like', $lang]], 'id');
                    $ids = array_merge($ids, array_column($gifts, 'id'));
                }
                if ($ids) {
                    $condition[] = ['id', 'NOT IN', $ids];
                }
            } else {
                $gifts = XsGift::getListByWhere([['excludes', 'like', $params['bigarea_id']]], 'id');
                if ($gifts) {
                    $condition[] = ['id', 'NOT IN', array_column($gifts, 'id')];
                }
            }
        }

        switch ($params['gift_search_id'] ?? 0) {
            case 1:
                $condition[] = ['is_lucky', '=', 1];
                break;
            case 2:
                $condition[] = ['is_named', '=', 1];
                break;
            case 3:
                $condition[] = ['gift_type', '=', 'coin'];
                $condition[] = ['income_type', '=', 'coin'];
                break;
            case 4:
                $condition[] = ['is_combo', '=', 1];
                break;
            case 5:
                $condition[] = ['is_skin', '=', 1];
                break;
            case 6:
                $condition[] = ['is_interact_gift', '=', 1];
                break;
            case 7:
                $condition[] = ['is_feed_gift', '=', 1];
                break;
            case 8:
                $condition[] = ['is_relation_gift', '=', 1];
                break;
            case 9:
                $condition[] = ['is_customized', '=', 1];
                break;
            case 10:
                $condition[] = ['privilege_type', '>', 0];
                break;
            case 11:
                $condition[] = ['is_blind_box', '=', 1];
                break;
            case 12:
                $condition[] = ['is_secret_gift', '=', 1];
                break;
            default:
                break;
        }

        return $condition;
    }

    public function getGiftMap($value = null, $format = '')
    {
        $condition = ENV == 'dev' ? [] : [['app_id', '=', APP_ID]];
        $map = XsGift::getListByWhere($condition, 'id,name', 'id desc');

        if ($map) {
            $map = array_map(function ($item) {
                $item['name'] = $item['name'] . ',' . $item['id'];
                return $item;
            }, $map);
        }
        $map = $map ? array_column($map, 'name', 'id') : [];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        return StatusService::formatMap($map, $format);
    }

    public function getGiftSearchMap(string $str): array
    {
        $condition = ENV == 'dev' ? [] : [['app_id', '=', APP_ID]];

        if (is_numeric($str)) {
            $condition[] = ['id', '=', $str];
        } else {
            $condition[] = ['name', 'like', $str];
        }
        $map = XsGift::getListByWhere($condition, 'id,name', 'id desc');

        if ($map) {
            $map = array_map(function ($item) {
                $item['name'] = $item['name'] . ',' . $item['id'];
                return $item;
            }, $map);
        }
        $map = $map ? array_column($map, 'name', 'id') : [];

        return StatusService::formatMap($map);
    }

    public static function updateGiftInfo(string $type, array $gift, int $size)
    {
        $data = [];
        if ($type == 'list') {
            $data = [
                'version' => $gift['version'] + 1
            ];
        } else if ($type == 'webp') {
            $data = [
                'size' => $size,
                'type' => 'multiframe',
            ];
        } else if ($type == 'mp4') {
            $data = [
                'vap_size' => $size,
                'vap_type' => 'normal',
            ];
        } else {
            // todo 待新增图片类型
        }

        $beforeJson = [];
        foreach ($data as $key => $value) {
            $beforeJson[$key] = $gift[$key];
        }
        // 记录下更新礼物信息的日志
        OperateLog::addOperateLog([
            'before_json'  => $beforeJson,
            'content'      => '更新礼物资源',
            'after_json'   => $data,
            'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
            'model'        => 'gift',
            'model_id'     => $gift['id'],
            'action'       => BmsOperateLog::ACTION_UPDATE,
            'operate_id'   => Helper::getSystemUid(),
            'operate_name' => Helper::getSystemUserInfo()['user_name'],
        ]);

        $data['display'] = $gift['display'];
        list($res, $msg) = XsGift::edit($gift['id'], $data);
        if (!$res) {
            return [false, '礼物资源信息更新失败, 原因' . $msg];
        }

        return [true, ''];
    }

    public function exportSql()
    {
        $uid = Helper::getSystemUid();

        $fileName = 'gift_export_sql_' . $uid . time() . '.sql';
        $tables = [
            ['schema' => XsGift::SCHEMA_READ, 'table' => 'xs_gift', 'id' => 'id', 'where' => ''],
        ];

        set_time_limit(60);
        $file = Helper::exportSql($fileName, $tables, true);
        Helper::downLoadFile($file, 'giftExportSql', 'sql');
    }

    /**
     * 检查文件是否在OSS中存在
     * @param string $url 文件URL
     * @return bool 是否存在
     */
    private function checkOssFileExists(string $url, OssUpload $ossUpload): bool
    {
        if (empty($url)) {
            return false;
        }
        
        try {
            // 创建OSS上传实例，使用默认bucket
            
            // 从URL中提取对象路径
            $parsedUrl = parse_url($url);
            $objectPath = ltrim($parsedUrl['path'] ?? '', '/');
            
            // 移除查询参数
            $objectPath = strtok($objectPath, '?');
            
            // 检查文件是否存在
            return $ossUpload->doesObjectExist($objectPath);
        } catch (\Exception $e) {
            // 如果检查失败，记录错误并返回false
            error_log("OSS文件存在性检查失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 根据文件URL生成带有文件类型判断的HTML标签
     * @param string $url 文件URL
     * @return string HTML标签
     */
    private function generateMediaHtml(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        
        // 检查文件是否在OSS中存在，不存在返回空字符串
        // if (!$this->checkOssFileExists($url, $ossUpload)) {
        //     return '';
        // }

        $url = Helper::getHeadUrl($url);
        
        // 获取文件扩展名
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        // 移除查询参数以获取真实的文件扩展名
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $url .= '?time=' . time();
        
        if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'])) {
            // 视频文件，使用video标签
            return sprintf('<a href="%s" target="_blank" style="display: inline-block; border: 2px solid #007bff; border-radius: 8px; padding: 8px; background: #e3f2fd; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><video controls style="max-width: 120px; max-height: 120px; display: block; border-radius: 4px;"><source src="%s" type="video/%s">您的浏览器不支持视频播放</video><div style="font-size: 12px; color: #666; text-align: center; margin-top: 4px;">视频文件</div></a>', $url, $url, $extension);
        } elseif (in_array($extension, ['webp', 'png', 'jpg', 'jpeg', 'gif', 'bmp'])) {
            // 图片文件，使用img标签
            return sprintf('<a href="%s" target="_blank" style="display: inline-block; border: 2px solid #ddd; border-radius: 8px; padding: 8px; background: #f8f9fa; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><img src="%s" style="max-width: 50px; max-height: 50px; display: block; border-radius: 4px;" alt="图片预览" title="点击查看大图" /></a>', $url, $url);
        } else {
            // 其他文件类型，直接显示链接
            return sprintf('<a href="%s" target="_blank">%s</a>', $url, basename($url));
        }
    }
}
