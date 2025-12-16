<?php

namespace Imee\Service\Operate\Activity;

use Imee\Exception\ApiException;
use Imee\Helper\Traits\ExportCsvTrait;
use Imee\Models\Config\BbcOnepkObject;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankScoreConfig;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActRankAwardUserExtend;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstOnepkObjectLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Phalcon\Di;

class ActivityOnePkPlayService extends ActivityService
{
    use ExportCsvTrait;

    const PAGE_URL = '%s/rank-template-v4/?aid=%d&clientScreenMode=1';
    const DESC_PATH = '%s/ps-resource/?aid=%d&lan=%s#/btn-race';

    // pk对象最多50组
    const ONE_PK_OBJECT_COUNT = 50;

    public function add(array $params)
    {
        // 根据params['id'] 是否存在判断是编辑还是创建
        $this->valid($params);
        $data = $this->formatData($params);
        $conn = Di::getDefault()->getShared('bbcdb');
        $conn->begin();
        try {
            [$_, $id] = BbcTemplateConfig::add($data['config']);
            // 更新活动链接
            $prefix = ENV == 'prod' ? self::PROD_URL : self::DEV_URL;
            $update = [
                'page_url' => sprintf(self::PAGE_URL, $prefix, $id),
            ];
            if (in_array($data['config']['onepk_obj'], [BbcTemplateConfig::ONE_PK_OBJECT_ROOM, BbcTemplateConfig::ONE_PK_OBJECT_ANCHOR])) {
                $update['desc_path'] = sprintf(self::DESC_PATH, $prefix, $id, $data['config']['language']);
            }
            BbcTemplateConfig::edit($id, $update);
            $data['list']['act_id'] = $id;
            [$_, $listId] = BbcRankButtonList::add($data['list']);
            foreach ($data['score']['add'] as &$item) {
                $item['act_id'] = $id;
                $item['button_list_id'] = $listId;
            }
            BbcRankScoreConfig::addBatch($data['score']['add']);
            foreach ($data['obj']['add'] as &$object) {
                $object['act_id'] = $id;
                $object['button_list_id'] = $listId;
            }
            BbcOnepkObject::addBatch($data['obj']['add']);
            $conn->commit();
            return [true, $id];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage() ?? '添加失败'];
        }
    }

    public function edit(array $params)
    {
        // 根据params['id'] 是否存在判断是编辑还是创建
        $this->valid($params);
        $data = $this->formatData($params);
        $conn = Di::getDefault()->getShared('bbcdb');
        $conn->begin();
        try {
            $templateConfig = BbcTemplateConfig::findOne($params['id']);
            // 更新活动链接
            $prefix = ENV == 'prod' ? self::PROD_URL : self::DEV_URL;
            if ($templateConfig && in_array($templateConfig['onepk_obj'], [BbcTemplateConfig::ONE_PK_OBJECT_ROOM, BbcTemplateConfig::ONE_PK_OBJECT_ANCHOR])) {
                $data['config']['desc_path'] = sprintf(self::DESC_PATH, $prefix, $params['id'], $data['config']['language']);
            }
            BbcTemplateConfig::edit($params['id'], $data['config']);
            BbcRankButtonList::edit($params['button_list_id'], $data['list']);
            $this->handlePkConfig(BbcRankScoreConfig::class, $data['score'], $params['status']);
            $this->handlePkConfig(BbcOnepkObject::class, $data['obj'], $params['status']);
            // 活动状态不是未发布（0）时，需要调服务端接口删除pk配置。
            if ($params['status'] != BbcTemplateConfig::STATUS_NOT_RELEASE) {
                if ($data['obj']['del']) {
                    $rpcData = [
                        'act_id'       => (int)$params['id'],
                        'onepk_obj_id' => $data['obj']['del']
                    ];
                    list($res, $msg) = (new PsService())->delOnepkObj($rpcData);
                    if (!$res) {
                        return [false, $msg];
                    }
                }
                if ($data['obj']['add'] || $data['obj']['edit'] || $data['obj']['del']) {
                    // 只要修改对战信息则调取服务端接口
                    list($res, $msg) = (new PsService())->updateOnepkObj($params['id']);
                    if (!$res) {
                        return [false, $msg];
                    }
                }

                // 延时一下在写入
                sleep(1);
                XsstOnepkObjectLog::addOnepkRecord($params['id'], XsstOnepkObjectLog::TYPE_UP);

            }
            $conn->commit();
            return [true, '修改成功'];
        } catch (\Exception $e) {
            $conn->rollback();
            return [false, $e->getMessage() ?? '修改失败'];
        }
    }

    /**
     * 处理pk配置数据
     * @param $model
     * @param $data
     * @param $status
     * @return void
     */
    private function handlePkConfig($model, $data, $status): void
    {
        if (!empty($data['add'])) {
            $model::addBatch($data['add']);
        }
        if (!empty($data['edit'])) {
            $model::updateBatch($data['edit']);
        }
        if (!empty($data['del']) && $status == BbcTemplateConfig::STATUS_NOT_RELEASE) {
            $model::deleteByWhere([['id', 'IN', $data['del']]]);
        }
    }

    /**
     * 详情接口
     * @param int $id
     * @return array
     */
    public function info(int $id): array
    {
        $templateConfig = BbcTemplateConfig::findOne($id);
        $listConfig = BbcRankButtonList::getInfoByActIdAndTag($id, BbcRankButtonList::RANK_TAG_ONE_PK);
        $scoreConfigList = BbcRankScoreConfig::getListByWhere([
            ['act_id', '=', $id],
            ['button_list_id', '=', $listConfig['id']]
        ]);
        foreach ($scoreConfigList as &$score) {
            $score['type'] = (string)$score['type'];
        }
        $pkObjectList = BbcOnepkObject::getListByWhere([
            ['act_id', '=', $id],
            ['button_list_id', '=', $listConfig['id']]
        ]);
        $templateConfig['time_offset'] = $this->getTimeOffset($templateConfig['time_offset']);
        $timeOffset = (8 - $templateConfig['time_offset']) * 3600;
        $configStartTime = intval($templateConfig['start_time']) - $timeOffset;
        $dataPeriod =  intval($templateConfig['data_period']) * 86400;
        $configEndTime = $templateConfig['end_time'] - $timeOffset - $dataPeriod;
        $status = $this->getStatus($templateConfig['status'], $templateConfig['start_time'],  $templateConfig['end_time'] - $dataPeriod);

        $time = time();
        foreach ($pkObjectList as &$object) {
            $startTime = intval($object['start_time']) - $timeOffset;
            $endTime = intval($object['end_time']) - $timeOffset;
            $state = BbcOnepkObject::STATE_WAIT;
            if (($status != BbcTemplateConfig::STATUS_NOT_RELEASE) && $object['status'] == BbcOnepkObject::STATUS_EFFECTIVE && $object['start_time'] <= $time) {
                $state = BbcOnepkObject::STATE_HAVE;
            }
            $object['start_time'] = Helper::now($startTime);
            $object['end_time'] = Helper::now($endTime);
            $object['state'] = $state;
        }

        $data = [
            'id'                  => $id,
            'title'               => $templateConfig['title'],
            'active_start_time'   => Helper::now($configStartTime),
            'active_end_time'     => Helper::now($configEndTime),
            'status'              => $status,
            'onepk_obj'           => strval($templateConfig['onepk_obj']),
            'onepk_object'        => $pkObjectList,
            'room_support'        => strval($listConfig['room_support']),
            'rank_score_config'   => $scoreConfigList,
            'button_desc'         => $listConfig['button_desc'],
            'rule_content_json'   => $templateConfig['rule_content_json'],
            'award_content_json'  => json_decode($templateConfig['award_content_json'], true),
            'vision_content_json' => $this->getVisionContentJson($templateConfig['vision_content_json'] ?? ''),
            'data_period'         => $templateConfig['data_period'],
            'language'            => $templateConfig['language'],
            'button_list_id'      => $listConfig['id'],
            'bigarea_id'          => explode('|', $templateConfig['bigarea_id']),
            'time_offset'         => $templateConfig['time_offset'],
            'admin'               => Helper::getAdminName($templateConfig['admin_id'])
        ];

        return $data;
    }

    private function getVisionContentJson(string $visionContentJson): array
    {
        if (empty($visionContentJson)) {
            return [];
        }
        $json = json_decode($visionContentJson, true);
        foreach ($json as $key => $item) {
            if ($key == 'banner_homepage_img') {
                $json[$key . '_all'] = Helper::getHeadUrl($item);
            }
        }

        return $json;
    }

    public function export(array $params)
    {
        $file = PUBLIC_DIR . DS . 'onePkData_' . $params['admin_uid'] . time() . '.csv';
        $resMap = ['负', '胜', '平', '未产生'];
        $config = BbcTemplateConfig::findOne($params['id']);
        $header = '活动id,pk分组,对战时间,PK对象,PK者身份,id,公会ID,公会名称,分值,胜负情况';
        $type = '房主';
        $childType = '成员';
        if ($config['onepk_obj'] == BbcTemplateConfig::ONE_PK_OBJECT_ANCHOR) {
            $header = '活动id,pk分组,对战时间,PK对象,PK者身份,id,公会ID,公会名称,分值,胜负情况';
            $type = '主播';
            $childType = '贡献用户';
        }
        file_put_contents($file, "\xEF\xBB\xBF" . $header . "\n", FILE_APPEND);
        $buttonList = BbcRankButtonList::getInfoByActIdAndTag($params['id'], BbcRankButtonList::RANK_TAG_ONE_PK);
        $objList = BbcOnepkObject::getListByWhere([
            ['act_id', '=', $params['id']],
            ['button_list_id', '=', $buttonList['id']]
        ], 'start_time, end_time, onepk_objid_1, onepk_objid_2', 'start_time asc');
        $result = [];

        $config['time_offset'] = intval($config['time_offset']) / 10;
        $timeOffset = (8 - intval($config['time_offset'])) * 3600;
        $pkObjName = BbcTemplateConfig::$onePkObject[$config['onepk_obj']];
        $now = time();
        foreach ($objList as $key => $item) {
            $startTime = intval($item['start_time']) - $timeOffset;
            $endTime = intval($item['end_time']) - $timeOffset;
            $value = [
                'key'       => $key + 1,
                'obj'       => $pkObjName,
                'time'      => Helper::now($startTime) . '-' . Helper::now($endTime),
                'type'      => $type,
                'childType' => $childType,
                'status'    => $item['end_time'] > $now ? 0 : 1,
            ];
            $this->getExportList($params['id'], $buttonList['id'], $item['onepk_objid_1'], $value, $result);
            $this->getExportList($params['id'], $buttonList['id'], $item['onepk_objid_2'], $value, $result);
        }
        // 0表示输 1表示赢 2表示平
        foreach ($result as &$list) {
            $res1 = $res2 = 0;
            $sum1 = array_sum(array_column($list[0], 'score'));
            $sum2 = array_sum(array_column($list[1], 'score'));
            if ($sum1 > $sum2) {
                $res1 = 1;
            } else if ($sum1 < $sum2) {
                $res2 = 1;
            } else {
                $res1 = $res2 = 2;
            }
            // 只需第一行数据添加结果即可
            $list[0][0]['res'] = $list[0][0]['res'] ?: $resMap[$res1];
            $list[0][0]['score'] = $sum1;
            $list[1][0]['score'] = $sum2;
            $list[1][0]['res'] = $list[1][0]['res'] ?: $resMap[$res2];
            foreach ($list as $value) {
                $tmpStr = $this->formatCsvTextBatch($value);
                file_put_contents($file, $tmpStr, FILE_APPEND);
            }
        }
        return $file;
    }

    private function getExportList($actId, $listId, $extendId, $value, &$result)
    {
        $list = XsActRankAwardUserExtend::getListByWhere([
            ['act_id', '=', $actId],
            ['list_id', '=', $listId],
            ['extend_id', '=', $extendId]
        ], 'object_id, extend_id, score', 'score desc');
        $uids = array_column($list, 'object_id');
        $brokerUsers = XsBrokerUser::getBrokerUserBatch($uids);
        $bids = array_column($brokerUsers, 'bid');
        $brokers = XsBroker::getBrokerBatch($bids);
        // 房主公会信息
        $mBid = $brokerUsers[$extendId]['bid'] ?? 0;
        $mBname = $brokers[$mBid]['bname'] ?? '';
        // 初始化数据格式
        $tmp = [
            'act_id'    => $actId,
            'key'       => $value['key'],
            'time'      => $value['time'],
            'obj'       => $value['obj'],
            'type'      => $value['type'],
            'object_id' => $extendId,
            'bid'       => $mBid,
            'bname'     => $mBname,
            'score'     => 0,
            'res'       => $value['status'] == 0 ? '未产生' : '',
        ];
        // 房主信息默认添加在第一条
        $data = [$tmp];
        foreach ($list as $item) {
            $tmp['res'] = '';
            $bid = $brokerUsers[$item['object_id']]['bid'] ?? 0;
            $bname = $brokers[$bid]['bname'] ?? '';
            // 成员信息处理
            $tmp['type'] = $value['childType'];
            $tmp['object_id'] = $item['object_id'];
            $tmp['bid'] = $bid;
            $tmp['bname'] = $bname;
            $tmp['score'] = $item['score'];
            $data[] = $tmp;

        }
        if ($data) {
            // 加个空行隔一下对战数据
            $data[] = [''];
            $result[$value['key']][] = $data;
        }
    }

    public function valid(array $params)
    {
        $roomSupport = $params['room_support'] ?? 0;
        $onepkObj = $params['onepk_obj'] ?? 0;
        if ($onepkObj == BbcTemplateConfig::ONE_PK_OBJECT_ROOM && $roomSupport != BbcRankButtonList::ROOM_SUPPORT_VOICE) {
            throw new ApiException(ApiException::MSG_ERROR, 'pk对象为房间时只支持配置语音房');
        }
        if (count($params['onepk_object']) > self::ONE_PK_OBJECT_COUNT) {
            throw new ApiException(ApiException::MSG_ERROR, 'pk对象最多可配置' . self::ONE_PK_OBJECT_COUNT . '组');
        }
        $objIds = [];
        $timeOffset = (8 - intval($params['time_offset'])) * 3600;
        $activeStartTime = strtotime($params['active_start_time']) + $timeOffset;
        $status = $params['status'] ?? 0;
        $now = time();
        if ($activeStartTime < $now && $status == BbcTemplateConfig::STATUS_NOT_RELEASE) {
            throw new ApiException(ApiException::MSG_ERROR, '活动时间必须大于当前时间');
        }
        foreach ($params['onepk_object'] as $obj) {
            $objStartTime = strtotime($obj['start_time']) + $timeOffset;
            $objEndTime = strtotime($obj['end_time']) + $timeOffset;
            if (!isset($obj['state']) || $obj['state'] == BbcOnepkObject::STATE_WAIT) {
                if ($objStartTime < $now) {
                    throw new ApiException(ApiException::MSG_ERROR,'PK开始时间不得早于当前时间');
                }
            }
            if ($objEndTime - $objStartTime > 86400) {
                throw new ApiException(ApiException::MSG_ERROR, 'PK开始时间、结束时间的间隔不得大于24h');
            }
            if ($obj['onepk_objid_1'] == $obj['onepk_objid_2'] || in_array($obj['onepk_objid_1'], $objIds) || in_array($obj['onepk_objid_2'], $objIds)) {
                throw new ApiException(ApiException::MSG_ERROR, '同个活动下uid只能出现一次');
            }
            $objIds[] = $obj['onepk_objid_1'];
            $objIds[] = $obj['onepk_objid_2'];
            foreach ([$obj['onepk_objid_1'], $obj['onepk_objid_2']] as $id) {
                if ($params['onepk_obj'] == 0) {
                    // 验证房主uid是否存在
                    $info = XsChatroom::getInfoByUidAndProperty($id, XsChatroom::VIP_PROPERTY);
                } else {
                    $info = XsUserProfile::findOne($id);
                }

                if (empty($info)) {
                    throw new ApiException(ApiException::MSG_ERROR, 'pk配置中id:' . $id . '不存在');
                }
                $objId = $obj['id'] ?? 0;
                // 查询表中数据是否存在同一时间，一个用户id是否存在pk
                $pkInfo = BbcOnepkObject::check($id, $objStartTime, $objEndTime, $objId);
                if ($pkInfo && (!empty($objId) || !isset($params['id']))) {
                    throw new ApiException(ApiException::MSG_ERROR, '有房主uid该时间段已存在pk，请检查配置');
                }
            }
        }
    }

    public function formatData(array &$params): array
    {
        $params['status'] = $params['status'] ?? 0;
        $params['dateline'] = time();
        $params['new_time_offset'] = (8 - intval($params['time_offset'])) * 3600;
        $rankButtonList = $this->getRankButtonListData($params);
        [$addScore, $editScore, $delScore] = $this->getRankScoreConfigData($params);
        [$addObj, $editObj, $delObj] = $this->getPkObjectData($params);
        $templateConfig = $this->getTemplateData($params);

        return [
            'list'   => $rankButtonList,
            'score'  => [
                'add'  => $addScore,
                'edit' => $editScore,
                'del'  => $delScore,
            ],
            'obj'    => [
                'add'  => $addObj,
                'edit' => $editObj,
                'del'  => $delObj,
            ],
            'config' => $templateConfig
        ];
    }

    private function getRankButtonListData(array $params)
    {
        $rankButtonList = [
            'room_support' => $params['room_support'],
            'button_desc'  => $params['button_desc'] ?? '',
        ];

        // 功能为添加时需要添加的字段
        if (empty($params['id'])) {
            $rankButtonList['button_tag_id'] = 0;
            $rankButtonList['rank_tag'] = BbcRankButtonList::RANK_TAG_ONE_PK;
            $rankButtonList['dateline'] = $params['dateline'];
            $rankButtonList['rank_list_num'] = 100;
            $rankButtonList['upgrade_extend_num'] = 0;
            $rankButtonList['admin_id'] = $params['admin_id'];
        }

        return $rankButtonList;
    }

    private function getRankScoreConfigData(array $params): array
    {
        $baseRankScoreConfig = [
            'score'    => 1,
            'dateline' => $params['dateline'],
            'admin_id' => $params['admin_id']
        ];

        $editScoreConfig = $scoreConfigIds = $addScoreConfig = [];
        // 查询现有统计范围配置进行筛选
        if (!empty($params['id'])) {
            $baseRankScoreConfig['act_id'] = $params['id'];
            $baseRankScoreConfig['button_list_id'] = $params['button_list_id'];
            $scoreConfigIds = BbcRankScoreConfig::getInfoByActId($params['id']);
        }
        foreach ($params['rank_score_config'] as $item) {
            // 存在id为修改数据，否则添加数据
            if (isset($item['id']) && !empty($item['id'])) {
                $editScoreConfig[$item['id']] = ['type' => $item['type']];
            } else {
                $addScoreConfig[] = array_merge($baseRankScoreConfig, ['type' => $item['type']]);
            }
        }
        // 获取删除掉的score_config数据id
        $delRankScoreConfigId = array_diff($scoreConfigIds, array_keys($editScoreConfig));
        $delRankScoreConfigId = array_map('intval', $delRankScoreConfigId);
        return [$addScoreConfig, $editScoreConfig, array_values($delRankScoreConfigId)];
    }

    private function getPkObjectData(array &$params): array
    {
        $params['is_up'] = BbcOnepkObject::STATUS_EFFECTIVE;
        $addPkObject = $editPkObject = $noDelOkIds = $pkObjectArr = $pkObjectIds = $baseData = [];
        if (!empty($params['id'])) {
            $baseData = [
                'act_id'         => $params['id'],
                'button_list_id' => $params['button_list_id'],
            ];
            $pkObjectArr = BbcOnepkObject::getInfoByActId($params['id']);
            $pkObjectIds = array_keys($pkObjectArr);
        }
        $isUpdate = false;

        foreach ($params['onepk_object'] as $v) {
            $data = [
                'start_time'    => strtotime($v['start_time']) + $params['new_time_offset'],
                'end_time'      => strtotime($v['end_time']) + $params['new_time_offset'],
                'onepk_objid_1' => $v['onepk_objid_1'],
                'onepk_objid_2' => $v['onepk_objid_2'],
                'status'        => BbcOnepkObject::STATUS_EFFECTIVE,
            ];
            $data = array_merge($data, $baseData);
            $pkObj = $pkObjectArr[$v['id'] ?? 0] ?? [];
            if ($pkObj) {
                $noDelOkIds[] = $v['id'];
                $update = $this->handlePkObjUpdateData($data, $pkObj, $isUpdate);
                $update && $editPkObject[$v['id']] = $update;
            } else {
                $addPkObject[] = $data;
            }
        }

        if ($params['status'] != BbcTemplateConfig::STATUS_NOT_RELEASE && ($addPkObject || $editPkObject)) {
            $params['is_up'] = BbcOnepkObject::STATUS_INVALID;
        }

        // 获取删除掉的oneObject数据id
        $delPkObjectId = array_diff($pkObjectIds, $noDelOkIds);
        $delPkObjectId = array_map('intval', $delPkObjectId);
        return [$addPkObject, $editPkObject, array_values($delPkObjectId)];
    }

    private function handlePkObjUpdateData(array $data, array $pkObj, bool &$isUpdate): array
    {
        if ($pkObj['start_time'] < time()) {
            $data['status'] = BbcOnepkObject::STATUS_EFFECTIVE;
        }
        $update = [];
        $fields = ['start_time', 'end_time', 'onepk_objid_1', 'onepk_objid_2'];
        foreach ($fields as $field) {
            if ($data[$field] != $pkObj[$field]) {
                $isUpdate = true;
                $update[$field] = $data[$field];
            }
        }

        return $update;
    }

    public function getTemplateData(array $params): array
    {
        $templateConfig = [
            'language'            => $params['language'],
            'rule_content_json'   => $params['rule_content_json'] ?? '',
            'award_content_json'  => isset($params['award_content_json']) ? json_encode($params['award_content_json']) : '',
            'data_period'         => $params['data_period'],
            'vision_content_json' => json_encode(['banner_homepage_img' => $params['banner_homepage_img']], JSON_UNESCAPED_SLASHES),
            'onepk_obj'           => $params['onepk_obj'],
            'title'               => $params['title'],
            'start_time'          => strtotime($params['active_start_time']) + $params['new_time_offset'],
            'end_time'            => strtotime($params['active_end_time']) + $params['new_time_offset'] + (intval($params['data_period']) * 86400),
            'time_offset'         => $params['time_offset'] * 10,
            'bigarea_id'          => implode('|', array_map('intval', $params['bigarea_id'])),
        ];
        if (empty($params['id'])) {
            $templateConfig['dateline'] = $params['dateline'];
            $templateConfig['admin_id'] = $params['admin_id'];
            $templateConfig['type'] = BbcTemplateConfig::TYPE_ONE_PK;
        }
        // 活动修改前不是未发布状态且修改了对战信息则状态更新为未更新
//        if (isset($params['id']) && !empty($params['id'])) {
//            $config = BbcTemplateConfig::findOne($params['id']);
//            if ($config['status'] != BbcTemplateConfig::STATUS_NOT_RELEASE && $params['is_up'] == BbcOnepkObject::STATUS_INVALID) {
//                $templateConfig['status'] = BbcTemplateConfig::WAIT_UP_STATUS;
//            }
//        }

        return $templateConfig;
    }

    public function enum()
    {
        $service = new StatusService();
        $language = $service->getLanguageNameMap(null, 'label,value');
        $bigAreaId = $service->getFamilyBigArea(null, 'label,value');

        $timeOffset = [
            ['label' => '+3', 'value' => +3],
            ['label' => '+5', 'value' => +5],
            ['label' => '+5.5', 'value' => +5.5],
            ['label' => '+6', 'value' => +6],
            ['label' => '+7', 'value' => +7],
            ['label' => '+8', 'value' => +8],
            ['label' => '+9', 'value' => +9],
            ['label' => '-7', 'value' => -7],
            ['label' => '-8', 'value' => -8],
        ];

        return compact('language', 'bigAreaId', 'timeOffset');
    }

    /**
     * 获取活动时区
     * @param int $timeOffset
     * @return float|int
     */
    private function getTimeOffset(int $timeOffset)
    {
        // 如果时区为0则默认展示UTC:+8
        if ($timeOffset == 0) {
            return 8;
        }

        return $timeOffset / 10;
    }

    protected function onAfterList($list)
    {
        foreach ($list as &$item) {
            $dataPeriod = (intval($item['data_period']) * 86400);
            $item['time_offset'] = $this->getTimeOffset($item['time_offset']);
            $timeOffset = (8 - $item['time_offset']) * 3600;
            $starTime = intval($item['start_time']) - $timeOffset;
            $endTime = intval($item['end_time']) - $timeOffset - $dataPeriod;
            $item['bigarea_id'] = XsBigarea::formatBigAreaName($item['bigarea_id']);
            $item['admin_id'] = $item['admin_id'] . '-' . Helper::getAdminName($item['admin_id']);
            $item['dateline'] = Helper::now($item['dateline']);
            $item['onepk_obj'] = BbcTemplateConfig::$onePkObject[$item['onepk_obj']] ?? '';
            $item['start_end_time'] = Helper::now($starTime) . '-<br />' . Helper::now($endTime);
            $item['status'] = $this->getStatus($item['status'], $item['start_time'], $item['end_time'] - $dataPeriod);
            $item['status_text'] = $this->setStatusText($item['status']);
            $item['time_offset'] = 'UTC:' . ($item['time_offset'] > 0 ? "+" : '') . $item['time_offset'];
            $item['page_url'] = [
                'title'        => $item['page_url'],
                'value'        => $item['page_url'],
                'type'         => 'url',
                'url'          => $item['page_url'],
                'resourceType' => 'static'
            ];
            // 处理导出数据
            if ($item['status'] == BbcTemplateConfig::STATUS_NOT_RELEASE) {
                $item['data'] = [
                    'title' => '未产生',
                ];
            } else {
                $item['data'] = [
                    'title'        => '下载',
                    'type'         => 'url',
                    'url'          => '/api/operate/activity/activityonepkplay/export?id=' . $item['id'],
                    'resourceType' => 'outside'
                ];
            }
            $buttonList = BbcRankButtonList::findOneByWhere([
                ['act_id', '=', $item['id']]
            ]);
            $item['room_support'] = $buttonList['room_support'] ?? 0;
            $item['room_support'] = BbcRankButtonList::$roomSupportMap[$item['room_support']];
        }

        return $list;
    }

    protected function getFields(): array
    {
        return [
            'id', 'admin_id', 'page_url', 'type', 'status', 'dateline', 'title',
            'language', 'title', 'onepk_obj', 'rule_content_json', 'award_content_json',
            'start_time', 'end_time', 'data_period', 'bigarea_id', 'time_offset', 'desc_path'
        ];
    }

    public function up(array $params): array
    {
        $id = $params['id'] ?? 0;
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '参数错误，缺少id');
        }
        list($rec, $msg) = (new PsService())->updateOnepkObj($id);
        if (!$rec) {
            throw new ApiException(ApiException::MSG_ERROR, '更新pk对战状态失败，失败原因：' .  $msg);
        }

        // 记录更新日志
        XsstOnepkObjectLog::addOnepkRecord($id, XsstOnepkObjectLog::TYPE_UP);

        return ['id' => $id];
    }

    public function getLogList(array $params): array
    {
        $conditions = [
            ['aid', '=', $params['id']]
        ];

        $config = BbcTemplateConfig::findOne($params['id']);
        $timeOffset = $this->getTimeOffset($config['time_offset']);
        $timeOffset = (8 - $timeOffset) * 3600;
        $list = XsstOnepkObjectLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['admin'] = Helper::getAdminName($item['admin_uid']);
            $item['dateline'] = Helper::now($item['dateline']);
            $item['type'] = XsstOnepkObjectLog::$typeMap[$item['type']];
            $item['obj'] = $this->getOnepkObj($item['pk_obj_json'], $timeOffset);
        }

        return $list;
    }

    private function getOnepkObj(string $pkObjJson, int $timeOffset): string
    {
        $string = '';
        if (empty($pkObjJson)) {
            return $string;
        }
        $json = json_decode($pkObjJson, true);
        foreach ($json as $item) {
            $startTime = $item['start_time'] - $timeOffset;
            $endTime = $item['end_time'] - $timeOffset;
            $string .= $item['onepk_objid_1'] . ' VS ' . $item['onepk_objid_2'] . ' ';
            $string .= Helper::now($startTime) . '~' . Helper::now($endTime);
            $string .= '<br />';
        }

        return $string;
    }

    public function setPageUrl($type, $id): string
    {
        $prefix = ENV == 'dev' ? self::DEV_URL : self::PROD_URL;

        return sprintf(static::PAGE_URL, $prefix, $id);
    }
}