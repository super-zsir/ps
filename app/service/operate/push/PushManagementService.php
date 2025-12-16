<?php


namespace Imee\Service\Operate\Push;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Sdk\SdkBase;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Redis\ImRedis;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCountry;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstBrokerOperate;
use Imee\Models\Xsst\XsstPushManagement;
use Imee\Models\Xsst\XsstPushRecord;
use Imee\Service\Helper;
use Imee\Service\StatusService;
use Phalcon\Di;

class PushManagementService
{
    use ImportTrait;

    const MAX_UPLOAD = 100000;

    const COPY_CONTENT = 1;
    const COPY_LIST = 2;

    public function getListAndTotal($params): array
    {
        $limit = (int)array_get($params, 'limit', 15);
        $page = (int)array_get($params, 'page', 1);

        $id = array_get($params, 'id');
        $pushRange = array_get($params, 'push_range');
        $msgType = trim(array_get($params, 'msg_type', ''));
        $note = trim(array_get($params, 'note', ''));
        $adminId = intval(array_get($params, 'creator', 0));
        $datelineStartTime = trim(array_get($params, 'dateline_sdate', ''));
        $datelineEndTime = trim(array_get($params, 'dateline_edate', ''));
        $sendStartTime = trim(array_get($params, 'send_time_sdate', ''));
        $sendEndTime = trim(array_get($params, 'send_time_edate', ''));

        $query = [];
        $msgType && $query[] = ['msg_type', '=', $msgType];
        is_numeric($pushRange) && $query[] = ['push_range', '=', $pushRange];
        $id && $query[] = ['id', '=', $id];
        $note && $query[] = ['note', 'like', $note];
        $adminId && $query[] = ['admin_id', '=', $adminId];
        $datelineStartTime && $query[] = ['dateline', '>=', strtotime($datelineStartTime)];
        $datelineEndTime && $query[] = ['dateline', '<', strtotime($datelineEndTime) + 86400];
        $sendStartTime && $query[] = ['send_time', '>=', strtotime($sendStartTime)];
        $sendEndTime && $query[] = ['send_time', '<', strtotime($sendEndTime) + 86400];
        $data = XsstPushManagement::getListAndTotal($query, '*', 'id desc', $page, $limit);
        if (!$data['data']) {
            return $data;
        }

        $admins = CmsUser::getAdminUserBatch(array_column($data['data'], 'admin_id'));
        $bigAreaMap = XsBigarea::getAllNewBigArea();
        $countryMap = XsCountry::getListMap();

        foreach ($data['data'] as &$rec) {
            $adminId = array_get($rec, 'admin_id', 0);
            $dateline = array_get($rec, 'dateline', 0);
            $pushRange = array_get($rec, 'push_range');
            $cronSendTime = array_get($rec, 'plan_time', 0);

            $rec['picture_url'] = Helper::getHeadUrl($rec['picture']);
            if ($adminId) {
                $rec['admin'] = $admins[$adminId]['user_name'] ?? '';
            } else {
                $rec['admin'] = '自动推送';
            }
            $pushConditions = [];
            if ($pushRange == 4) {
                $conditions = $this->formatPushCondition($rec['push_condition'], $bigAreaMap, $countryMap);
                $rec = array_merge($rec, $conditions);
                if (!empty($conditions['registration_time_sdate']) && !empty($conditions['registration_time_edate'])) {
                    $pushConditions[] = '注册时间：' . $conditions['registration_time_sdate'] . '～～' . $conditions['registration_time_edate'];
                }
                if (!empty($conditions['big_area'])) {
                    $pushConditions[] = '大区：' . $conditions['big_area'];
                }
                if (!empty($conditions['country_text'])) {
                    $pushConditions[] = '国家：' . $conditions['country_text'];
                }
                if (!empty($conditions['role_text'])) {
                    $pushConditions[] = '人群：' . $conditions['role_text'];
                }
                if (!empty($conditions['online_time'])) {
                    $pushConditions[] = '近期活跃：' . $conditions['online_time_text'];
                }
                if (!empty($conditions['broker_operate_text'])) {
                    $pushConditions[] = '运营负责人：' . $conditions['broker_operate_text'];
                }
            } else {
                $pushConditions[] = XsstPushManagement::$pushRange[$rec['push_range']] ?? '';
            }
            $rec['push_range'] = strval($rec['push_range']);
            $rec['push_range_msg'] = implode("<br />", $pushConditions);
            $rec['dateline'] = $dateline > 0 ? date('Y-m-d H:i:s', $dateline) : '';
            $rec['plan_time'] = $cronSendTime > 0 ? date('Y-m-d H:i:s', $cronSendTime) : '';
            $rec['send_time'] = $rec['send_time'] ? Helper::now($rec['send_time']) : '';
        }
        return $data;
    }

    private function formatPushCondition(string $pushConditionJson, array $bigAreaMap): array
    {
        $pushCondition = json_decode($pushConditionJson, true);

        if (empty($pushCondition)) {
            return [];
        }

        if (!empty($pushCondition['registration_time_sdate'])) {
            $pushCondition['registration_time_sdate'] = Helper::now($pushCondition['registration_time_sdate']);
        }

        if (!empty($pushCondition['registration_time_edate'])) {
            $pushCondition['registration_time_edate'] = Helper::now($pushCondition['registration_time_edate']);
        }

        if (!empty($pushCondition['big_area_id'])) {
            $pushCondition['big_area_id'] = strval($pushCondition['big_area_id']);
            $pushCondition['big_area'] = $bigAreaMap[$pushCondition['big_area_id']] ?? '';
        }

        if (!empty($pushCondition['role'])) {
            $roleArr = array_map(function ($item) {
                return XsstPushManagement::$roleMap[$item] ?? '';
            }, $pushCondition['role']);
            $pushCondition['role'] = array_map('strval', $pushCondition['role']);
            $pushCondition['role_text'] = implode(',', $roleArr);
        }

        if (!empty($pushCondition['country'])) {
            $pushCondition['country_text'] = $pushCondition['country'];
            $pushCondition['country'] = explode(',', $pushCondition['country']);
        } else {
            $pushCondition['country'] = [];
        }

        if (!empty($pushCondition['online_time'])) {
            $pushCondition['online_time'] = strval($pushCondition['online_time']);
            $pushCondition['online_time_text'] = XsstPushManagement::$onlineTimeMap[$pushCondition['online_time']];
        }

        if (!empty($pushCondition['broker_operate'])) {
            $pushCondition['broker_operate_text'] = strval($pushCondition['broker_operate']);
            $pushCondition['broker_operate'] = explode(',', $pushCondition['broker_operate']);
        }

        return $pushCondition;
    }

    public function add(array $params): void
    {
        $this->saveData($params);
    }

    public function edit(array $params): void
    {
        //删除记录
        XsstPushRecord::deleteByWhere([
            ['task_id', '=', $params['id']],
            ['status', '=', XsstPushRecord::NOT_SENT_STATUS]
        ]);

        $this->saveData($params);
    }

    public function validate($params): array
    {
        $id = intval($params['id'] ?? 0);
        $msgType = trim(array_get($params, 'msg_type', ''));
        $fromId = intval(array_get($params, 'from_id', 0));
        $msgContent = trim(array_get($params, 'msg_content', ''));
        $note = trim(array_get($params, 'note', ''));
        $uidList = array_get($params, 'uid_list', []);
        $picture = trim(array_get($params, 'picture', ''));
        $link = trim(array_get($params, 'link', ''));
        $pushRange = intval(array_get($params, 'push_range', 0));
        $title = trim(array_get($params, 'title', ''));
        $adminId = intval(array_get($params, 'admin_uid', ''));
        $copyType = intval(array_get($params, 'copy_type', 0));

        $admin = CmsUser::findOne($adminId);
        $adminBigAreaMap = XsBigarea::getBigareaIdList(explode(',', $admin['bigarea']));
        $adminBigAreaIds = array_values($adminBigAreaMap);

        $pushCondition = '';

        switch ($pushRange) {
            case 0:
                $this->validationUid($uidList, $adminBigAreaIds, $adminId);
                break;
            case 4:
                $pushCondition = $this->validationCondition($params, $adminBigAreaIds);
                break;
        }

        return [
            'id'             => $copyType ? 0 : $id,
            'oid'            => $id,
            'app_id'         => APP_ID,
            'from_id'        => $fromId,
            'msg_type'       => $msgType,
            'push_range'     => $pushRange,
            'msg_content'    => $msgContent,
            'link'           => $link,
            'picture'        => $picture,
            'note'           => $note,
            'title'          => $title,
            'push_condition' => json_encode($pushCondition, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'admin_id'       => Helper::getSystemUid(),
            'num'            => count($uidList),
            'dateline'       => time(),
            'uid_list'       => $uidList,
            'status'         => XsstPushManagement::STATUS_CREATE_USER,
            'copy_type'      => $copyType,
        ];
    }

    private function saveData(array $params): void
    {
        list($result, $taskId) = XsstPushManagement::addOrEdit($params['id'], $params);

        if (!$result) {
            $this->addRecordLog('添加数据错误' . $taskId);
            return;
        }
        $params['task_id'] = $taskId;
        // 生成发送用户数据
        $this->createPushRecordData($params);
    }

    private function createPushRecordData($params): void
    {
        switch ($params['push_range']) {
            case 0:
                $this->createListData($params);
                break;
            case 4:
                $this->createConditionData($params);
                break;
        }

        // 生成完毕，修改状态为待发送
        XsstPushManagement::edit($params['task_id'], ['status' => XsstPushManagement::WAIT_STATUS]);
    }

    // 通过名单生成uid
    private function createListData($params): void
    {
        if (empty($params['uid_list'])) {
            return;
        }

        $baseData = [
            'task_id'  => $params['task_id'],
            'dateline' => $params['dateline']
        ];

        foreach (array_chunk($params['uid_list'], 1000) as $uidList) {
            $addBatchData = array_map(function ($uid) use ($baseData) {
                return array_merge($baseData, ['uid' => $uid]);
            }, $uidList);

            list($res, $msg, $_) = XsstPushRecord::addBatch($addBatchData);
            if (!$res) {
                $this->addRecordLog(sprintf('task_id: %s, add data error. msg: %s', $params['task_id'], $msg));
                // 记录一个生成失败的状态
                XsstPushManagement::edit($params['task_id'], ['status' => XsstPushManagement::STATUS_CREATE_USER_ERROR]);
                return;
            }
            // 延时一下在写入
            usleep(100 * 1000);
        }
    }

    // 通过条件生成uid
    private function createConditionData(array $params): void
    {
        $copyType = intval($params['copy_type'] ?? 0);
        if ($copyType == self::COPY_LIST) {
            $totalNum = $this->copySavePushRecord($params);
        } else {
            $pushCondition = json_decode($params['push_condition'], true);
            if (empty($pushCondition)) {
                return;
            }

            // 获取缓存条件数据
            $redisKey = ImRedis::setConditionsKey($pushCondition);
            if (ImRedis::isConditions($redisKey)) {
                $totalNum = $this->cacheSavePushRecord($params, $redisKey);
            } else {
                $totalNum = $this->requestSavePushRecord($pushCondition, $params, $redisKey);
                ImRedis::setExpire($redisKey);
            }
        }


        // 修改数据总条数
        XsstPushManagement::edit($params['task_id'], ['num' => $totalNum]);
    }

    /**
     * 通过请求数仓接口获取用户信息并添加
     *
     * @param array $pushCondition
     * @param array $params
     * @param string $redisKey
     * @return int
     */
    private function requestSavePushRecord(array $pushCondition, array $params, string $redisKey): int
    {
        $requestParams = $this->parseCondition($pushCondition);
        $pageNum = 0;
        $totalNum = 0;

        while (true) {
            $data = $this->requestDataServiceGetUidList($requestParams, $pageNum);
            if (empty($data['data'])) {
                break;
            }
            $totalNum += $data['total'];
            $this->createListData([
                'task_id'  => $params['task_id'],
                'dateline' => $params['dateline'],
                'uid_list' => $data['data']
            ]);
            // 将请求相同的条件用户做缓存
            ImRedis::addConditionsUserList($redisKey, $data['data']);
            // dev环境目前有问题，会一直返回固定用户， 只调用1次就好
            if (ENV == 'dev') {
                break;
            }
            $pageNum++;
            usleep(100 * 1000 * 3);
        }

        return $totalNum;
    }

    /**
     * 通过缓存获取用户信息并添加
     *
     * @param array $params
     * @param string $redisKey
     * @return int
     */
    private function cacheSavePushRecord(array $params, string $redisKey): int
    {
        $generatorList = ImRedis::getConditionsList($redisKey);

        $total = 0;
        foreach ($generatorList as $generator) {
            foreach ($generator as $uidList) {
                $uidList = json_decode($uidList, true);
                if (empty($uidList)) {
                    continue;
                }
                $this->createListData([
                    'task_id'  => $params['task_id'],
                    'dateline' => $params['dateline'],
                    'uid_list' => $uidList
                ]);
                $total += count($uidList);
            }
        }
        return $total;
    }

    /**
     * 查询当前复制任务的用户信息并添加
     *
     * @param array $params
     * @return int
     */
    private function copySavePushRecord(array $params): int
    {
        $id = intval($params['oid'] ?? 0);
        $taskId = intval($params['task_id'] ?? 0);
        $now = time();

        $generatorList = XsstPushRecord::getGeneratorListByWhere([
            ['task_id', '=', $id]
        ], 'uid');

        $totalNum = 0;

        foreach ($generatorList as $generator) {
            $uidList = Helper::arrayFilter($generator, 'uid');
            if (empty($uidList)) {
                continue;
            }
            $data = [
                'uid_list' => $uidList,
                'task_id'  => $taskId,
                'dateline' => $now,
            ];
            $this->createListData($data);
            $totalNum += count($uidList);
        }

        return $totalNum;
    }

    private function parseCondition(array $pushCondition): array
    {
        $bigAreaId = $pushCondition['big_area_id'];
        $country = $pushCondition['country'] ?: '*';
        $role = $pushCondition['role'];
        $onlineStartTime = $pushCondition['online_time_sdate'] ?: '';
        $onlineEndTime = $pushCondition['online_time_edate'] ?: '';
        $registrationStartTime = $pushCondition['registration_time_sdate'] ?: 0;
        $registrationEndTime = $pushCondition['registration_time_edate'] ?: 0;
        $brokerOperate = $pushCondition['broker_operate'] ?: '';
        $brokerOperateBid = '*';

        $isBrokerCreater = $isBrokerAdmin = $isBrokerHunterBd = $isBrokerUser = $isCoinUser = 0;

        // 判断人群中包含哪些
        foreach ($role as $r) {
            switch ($r) {
                case XsstPushManagement::ROLE_ANCHOR:
                    $isBrokerUser = 1;
                    break;
                case XsstPushManagement::ROLE_BROKER_MASTER:
                    $isBrokerCreater = 1;
                    break;
                case XsstPushManagement::ROLE_BROKER_ADMIN:
                    $isBrokerAdmin = 1;
                    break;
                case XsstPushManagement::ROLE_HUNTER:
                    $isBrokerHunterBd = 1;
                    break;
                case XsstPushManagement::ROLE_OPERATION_MANAGER:
                    if (!empty($brokerOperate)) {
                        $lists = XsstBrokerOperate::getListByWhere([['uid', 'IN', explode(',', $brokerOperate)]], 'bid,uid');
                        if (!empty($lists)) {
                            $brokerOperateBid = implode(',', Helper::arrayFilter($lists, 'bid'));
                        }
                    }
                    break;
                case XsstPushManagement::ROLE_COIN_USER:
                    $isCoinUser = 1;//币商
                    break;
            }
        }

        return [
            ["fieldName" => "bigarea_id", "fieldType" => "NUMBER", "fieldValue" => (string)$bigAreaId],
            ["fieldName" => "start_reg_ts", "fieldType" => "NUMBER", "fieldValue" => (string)$registrationStartTime],
            ["fieldName" => "end_reg_ts", "fieldType" => "NUMBER", "fieldValue" => (string)$registrationEndTime],
            ["fieldName" => "start_last_online_ts", "fieldType" => "STRING", "fieldValue" => (string)$onlineStartTime],
            ["fieldName" => "end_last_online_ts", "fieldType" => "STRING", "fieldValue" => (string)$onlineEndTime],
            ["fieldName" => "country_str", "fieldType" => "STRING", "fieldValue" => (string)$country],
            ["fieldName" => "country_list", "fieldType" => "LIST_STRING", "fieldValue" => (string)$country],
            ["fieldName" => "is_broker_user", "fieldType" => "NUMBER", "fieldValue" => (string)$isBrokerUser],
            ["fieldName" => "is_broker_creater", "fieldType" => "NUMBER", "fieldValue" => (string)$isBrokerCreater],
            ["fieldName" => "is_broker_admin", "fieldType" => "NUMBER", "fieldValue" => (string)$isBrokerAdmin],
            ["fieldName" => "is_broker_hunter_bd", "fieldType" => "NUMBER", "fieldValue" => (string)$isBrokerHunterBd],
            ["fieldName" => "bid_str", "fieldType" => "STRING", "fieldValue" => (string)$brokerOperateBid],
            ["fieldName" => "bid_list", "fieldType" => "LIST_STRING", "fieldValue" => (string)$brokerOperateBid],
            ["fieldName" => "is_coin_merchant", "fieldType" => "NUMBER", "fieldValue" => (string)$isCoinUser], //币商人
            ["fieldName" => "page_size", "fieldType" => "NUMBER", "fieldValue" => "10000"],
        ];
    }

    // 请求数仓那边拉取当前条件下的用户(T+1)
    private function requestDataServiceGetUidList(array $requestParams, int $pageNum): array
    {
        //请求数仓接口
        $url = ENV == 'dev' ? 'http://223.76.184.188:8766/api/data-tunnel/fetch' : 'http://serv-data-sg-services.aopacloud.private/api/data-tunnel/fetch';
//        $code = ENV == 'dev' ? 'kO9DE2FJ' : '0azYuE1f';
//        $authKey = ENV == 'dev' ? 'gTtESOdS' : '7Emb6QtF';
        $code = ENV == 'dev' ? 'kO9DE2FJ' : 'uGf5kyPk';
        $authKey = ENV == 'dev' ? 'gTtESOdS' : '6EUn6IqP';
        $isTest = ENV == 'dev';
        $requestParams[] = ["fieldName" => "page_num", "fieldType" => "NUMBER", "fieldValue" => (string)$pageNum];
        $params = [
            "code"      => $code,
            "authKey"   => $authKey,
            "versionId" => 1,
            "parameter" => $requestParams,
            "isTest"    => $isTest
        ];
        // 确保JSON编码不转义斜杠、中文等
        $jsonParams = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $result = (new SdkBase(SdkBase::FORMAT_JSON, 30))->httpRequest($url, true, $jsonParams, null, null, null, true);
        if ($result['statusCode'] != 200) {
            $this->addRecordLog(sprintf('data service requested error, msg %s', $result['message']));
            return [];
        }

        return ['data' => Helper::arrayFilter($result['data']['data'] ?? [], 'uid'), 'total' => $result['data']['total'] ?? 0];
    }

    public function getDetailListAndTotal($params): array
    {
        $limit = (int)array_get($params, 'limit', 15);
        $page = (int)array_get($params, 'page', 1);

        $maxId = array_get($params, 'max_id', 0);
        $id = array_get($params, 'id');
        $status = array_get($params, 'status');

        $query = [];
        $maxId && is_numeric($maxId) && $query[] = ['id', '<', $maxId];
        $id && is_numeric($id) && $query[] = ['task_id', '=', $id];
        $status && is_numeric($status) && $query[] = ['status', '=', $status];

        $data = XsstPushRecord::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $adminList = CmsUser::getUserNameList(Helper::arrayFilter($data['data'], 'admin_id'));

        $totalCount = XsstPushRecord::getCount([['task_id', '=', $id]]);
        $successCount = XsstPushRecord::getCount([['task_id', '=', $id], ['status', '=', XsstPushRecord::SENT_STATUS]]);
        $waitCount = XsstPushRecord::getCount([['task_id', '=', $id], ['status', '=', XsstPushRecord::NOT_SENT_STATUS]]);
        $recallCount = XsstPushRecord::getCount([['task_id', '=', $id], ['status', '=', XsstPushRecord::SENT_RECALL]]);
        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $updateline = array_get($rec, 'updateline', 0);
            $rec['admin'] = $adminList[$rec['admin_id']] ?? '-';
            $rec['dateline'] = $dateline > 0 ? date('Y-m-d H:i:s', $dateline) : ' - ';
            $rec['updateline'] = $updateline > 0 ? date('Y-m-d H:i:s', $updateline) : ' - ';
        }

        return [
            'data'    => $data['data'],
            'total'   => $data['total'],
            'summary' => compact('totalCount', 'successCount', 'waitCount', 'recallCount')
        ];
    }

    public static function getPushManagementFromId($value = null, string $format = '')
    {
        $map = XsstPushManagement::$fromIdMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getPushManagementPushRange($value = null, string $format = '')
    {
        $map = XsstPushManagement::$pushRange;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getPushManagementMsgType($value = null, string $format = '')
    {
        $map = XsstPushManagement::$msgTypeMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public function push(array $params): array
    {
        $id = $params['id'] ?? 0;
        $status = $params['status'] ?? 0;
        $planTime = $params['plan_time'] ?? 0;

        if (empty($id) || empty($status) || !in_array($status, [
                XsstPushManagement::SEND_STATUS,
                XsstPushManagement::REJECT_STATUS,
                XsstPushManagement::PLAN_STATUS,
            ])) {
            return [false, '计划ID和状态必选'];
        }

        if ($status == XsstPushManagement::PLAN_STATUS) {
            if (empty($planTime)) {
                return [false, '状态为计划发送时，计划时间必须填写'];
            }
            $delay = strtotime($planTime) - time();
            if ($delay < 600) {
                return [false, '需要设置距当前10分钟后的时间'];
            }
        }

        $info = XsstPushManagement::findOne($id);
        if (empty($info)) {
            return [false, '当前计划不存在'];
        }
        if ($info['status'] == XsstPushManagement::SEND_STATUS) {
            return [false, '当前状态已经发布'];
        }

        if ($info['status'] == XsstPushManagement::REJECT_STATUS) {
            return [false, '当前状态为拒绝发送，不可修改状态。如需发送请重新创建IM消息'];
        }

        if ($info['status'] == XsstPushManagement::STATUS_CREATE_USER) {
            return [false, '当前计划中用户生成中，请稍后重试'];
        }

        if ($info['status'] == XsstPushManagement::STATUS_CREATE_USER_ERROR) {
            return [false, '当前计划中用户生成失败，请联系管理员查看'];
        }

        $update = [
            'status' => $status,
        ];

        if ($status == XsstPushManagement::SEND_STATUS) {
            //发送消息
            $message = array(
                'id'          => intval($id),
                'msg_type'    => $info['msg_type'],
                'from_id'     => $info['from_id'],
                'app_id'      => $info['app_id'],
                'msg_content' => $info['msg_content'],
                'link'        => $info['link'],
                'picture'     => $info['picture'],
                'admin_id'    => Helper::getSystemUid(),
                'title'       => $info['title'],
            );
            PushService::addPushList('push_management', $message);
        }

        if ($status == XsstPushManagement::PLAN_STATUS) {
            $update['plan_time'] = strtotime($planTime);
        }

        return XsstPushManagement::edit($id, $update);
    }

    public function pushRollback(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $pushManagement = XsstPushManagement::findOne($id);
        if (empty($pushManagement)) {
            return [false, 'ID错误'];
        }
        if ($pushManagement['status'] != XsstPushManagement::SEND_STATUS) {
            return [false, '推送消息未发送，不能回撤'];
        }

        // 撤回消息
        NsqClient::publishJson(
            NsqConstant::TOPIC_XS_LIVE_MESSAGE, ['cmd' => 'live.message.recall', 'data' => ['taskId' => $id]]
        );
        list($flg, $rec) = XsstPushManagement::edit($id, ['status' => XsstPushManagement::PLAN_RECALL]);
        $flg && XsstPushRecord::updateByWhere([
            ['task_id', '=', $params['id']],
            ['status', '=', XsstPushRecord::SENT_STATUS]
        ], ['status' => XsstPushRecord::SENT_RECALL]);

        return [$flg, $flg ? '' : $rec];
    }

    // 验证用户名单数据
    private function validationUid(array $uidArr, array $adminBigArea, int $adminId): void
    {
        if (empty($uidArr)) {
            throw new ApiException(ApiException::MSG_ERROR, '请上传推送名单');
        }

        //有上传名单的话按名单推送
        $uidArr = array_filter($uidArr, function ($val) {
            return $val > 0;
        });

        if (count($uidArr) < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '名单数据格式不正确');
        }
        if (count($uidArr) > self::MAX_UPLOAD) {
            throw new ApiException(ApiException::MSG_ERROR, '用户量太大无法推送[一次最多' . self::MAX_UPLOAD . ']');
        }

        //获取有效的uid
        $uidArray = array_unique($uidArr);
        $uidList = array_chunk($uidArray, 500);
        $uidAllowArr = [];
        foreach ($uidList as $uidArr) {
            $tmp = XsUserProfile::findByIds($uidArr, 'uid');
            $tmp = array_column($tmp, 'uid');
            $uidAllowArr = array_merge($uidAllowArr, $tmp);
        }
        if (!$uidAllowArr) {
            throw new ApiException(ApiException::MSG_ERROR, '名单人查不到信息');
        }
        $csvArrChunk = array_chunk($uidAllowArr, 1000);
        $diffUidArr = [];
        foreach ($csvArrChunk as $item) {
            $uidLists = XsUserBigarea::getListByWhere([['uid', 'IN', $item], ['bigarea_id', 'NOT IN', $adminBigArea]], 'uid, bigarea_id');
            if ($uidLists) {
                $diffUidArr = array_merge($diffUidArr, $uidLists);
            }
        }
        if ($diffUidArr) {
            $filePath = '/tmp/uid_check_' . $adminId . '.csv';
            @file_put_contents($filePath, array_map(function ($v) {
                return sprintf('%d,%s' . PHP_EOL, $v['uid'], XsBigarea::AREA_MAP[$v['bigarea_id']] ?? '');
            }, $diffUidArr));
            $errorUrl = Helper::uploadOss($filePath);
            @unlink($filePath);
            throw new ApiException(ApiException::MSG_ERROR, '存在无发送权限的用户，请检查后重新上传: ' . $errorUrl);
        }
    }

    // 验证条件发放
    private function validationCondition(array $params, array $adminBigArea): array
    {
        $registrationStartTime = trim(array_get($params, 'registration_time_sdate', ''));
        $registrationEndTime = trim(array_get($params, 'registration_time_edate', ''));
        $bigAreaId = intval(array_get($params, 'big_area_id', 0));
        $role = array_get($params, 'role', []);
        $onlineTime = intval(array_get($params, 'online_time', 0));
        $country = array_get($params, 'country', []);
        $brokerOperate = array_get($params, 'broker_operate', []);

        // 活跃结束时间取前一天的最后一秒。开始时间为 end_time - 86400 * online_time + 1
        // 举例：online_time = 3 当天为2025-7-10 则 区间为 [2025-07-07 00:00:00 ~~ 2025-07-09 23:59:59]
        $onlineTimeEndTime = strtotime('yesterday 23:59:59');

        // 检验后台用户是否有当前选择大区的权限
        if (!in_array($bigAreaId, $adminBigArea)) {
            throw new ApiException(ApiException::MSG_ERROR, '没有当前选择的大区权限');
        }

        // 检验人群是否合规
        // 包含所有人则不需要选择其他人群
        if (in_array(XsstPushManagement::ROLE_ALL, $role) && count($role) > 1) {
            throw new ApiException(ApiException::MSG_ERROR, '当前已经选择所有人，无需在选择其他人群');
        }
        // 包含主播不需要选择公会长和公会管理员
        if (in_array(XsstPushManagement::ROLE_ANCHOR, $role) &&
            (in_array(XsstPushManagement::ROLE_BROKER_MASTER, $role) || in_array(XsstPushManagement::ROLE_BROKER_ADMIN, $role))
        ) {
            throw new ApiException(ApiException::MSG_ERROR, '当前已经选择主播，无需在选择公会长和公会管理员');
        }
        //运营负责人旗下主播
        if (in_array(XsstPushManagement::ROLE_OPERATION_MANAGER, $role) && empty($brokerOperate)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前已经选择运营负责人旗下主播，请选择 运营负责人');
        }

        return [
            'big_area_id'             => $bigAreaId,
            'role'                    => $role,
            'country'                 => $country ? implode(',', $country) : '',
            'registration_time_sdate' => $registrationStartTime ? strtotime($registrationStartTime) : '',
            'registration_time_edate' => $registrationEndTime ? strtotime($registrationEndTime) : '',
            'online_time'             => $onlineTime,
            'online_time_sdate'       => $onlineTimeEndTime - 86400 * $onlineTime + 1,
            'online_time_edate'       => $onlineTimeEndTime,
            'broker_operate'          => !empty($brokerOperate) ? implode(',', $brokerOperate) : '',
        ];
    }


    public function getAdminMap()
    {
        $list = XsstPushManagement::getListByWhere([], 'admin_id');
        $adminIdArr = Helper::arrayFilter($list, 'admin_id');

        $adminList = CmsUser::getUserNameList($adminIdArr);
        return StatusService::formatMap($adminList);
    }

    /**
     * 选中人群枚举
     * @return array
     */
    public static function getRoleMap($fromId): array
    {
        $data = XsstPushManagement::$roleMap;
        if ($fromId == 10000000) {
            $data = XsstPushManagement::$roleAllMap;
        }
        return StatusService::formatMap($data);
    }

    /**
     * @param $value
     * @param $format
     * @return array
     *  运营负责人枚举值
     */
    public static function getBrokerOperateMap($value = null, $format = ''): array
    {
        $lists = XsstBrokerOperate::getListByWhere([], 'uid', 'id desc', 10000);
        $data = cmsUser::getAdminUserBatch(array_values(array_unique(array_column($lists, 'uid'))));
        $map = [];
        foreach ($data as $item) {
            $map[$item['user_id']] = sprintf('%d - %s', $item['user_id'], $item['user_name']);
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * 近期活跃枚举
     * @return array
     */
    public static function getOnlineTimeMap(): array
    {
        return StatusService::formatMap(XsstPushManagement::$onlineTimeMap);
    }

    /**
     * 获取任务状态枚举(列表)
     * @return array
     */
    public static function getStatusAllMap(): array
    {
        return StatusService::formatMap(XsstPushManagement::$statusAllMap);
    }

    /**
     * 获取任务状态枚举（发送）
     * @return array
     */
    public static function getStatusMap(): array
    {
        return StatusService::formatMap(XsstPushManagement::$statusMap);
    }

    /**
     * 获取国家枚举
     * @return array
     */
    public static function getCountryMap(): array
    {
        // 中东部分国家需要排在最前面
        $orderCountry = ["沙特阿拉伯", "阿拉伯联合酋长国", "科威特", "巴林", "埃及", "伊朗", "伊拉克", "约旦", "黎巴嫩", "阿曼", "卡塔尔", "叙利亚", "也门", "巴勒斯坦", "阿尔及利亚", "利比亚", "摩洛哥", "突尼斯", "苏丹", "美国", "德国", "土耳其"];
        $countryList = XsCountry::getListMap();

        // 出现相同的国家时直接跳过
        foreach ($countryList as $country) {
            if (!in_array($country, $orderCountry)) {
                $orderCountry[] = $country;
            }
        }

        return StatusService::formatMap(array_combine($orderCountry, $orderCountry));
    }

    /**
     * 初始化提交参数
     * @param array $params
     * @return array
     */
    public function initParams(array $params): array
    {
        $pushRange = intval(array_get($params, 'push_range', -1));
        $id = intval(array_get($params, 'id', 0));
        $msgType = trim(array_get($params, 'msg_type', ''));
        $copyType = intval($params['copy_type'] ?? 0);

        // 用户名单需要接受上传的uid
        if ($pushRange == 0) {
            // 复制名单的话且为指定名单推送时，名单直接从库里查
            if ($copyType == self::COPY_LIST) {
                $recordList = XsstPushRecord::getListByWhere([['task_id', '=', $id]], 'uid');
                $params['uid_list'] = Helper::arrayFilter($recordList, 'uid');
            } else {
                // 检查内存和文件大小，防止内存耗尽
                $this->checkMemoryAndFileSize();
                
                list($result, $msg, $data) = $this->uploadCsv(['uid']);
                if ($result) {
                    $params['uid_list'] = array_unique(array_column($data['data'], 'uid'));
                }
            }
        }

        // 根据消息类型重置字段数据
        switch ($msgType) {
            case XsstPushManagement::TEXT_TYPE:
                $params['picture'] = $params['link'] = $params['title'] = '';
                break;
            case XsstPushManagement::PICTURE_TYPE:
                $params['link'] = '';
                break;
        }

        return $params;
    }

    // 异步操作，这里手动写入日志
    private function addRecordLog(string $msg): void
    {
        file_put_contents('/tmp/ps_push_record.log', "[" . date('Y-m-d H:i:s') . "]" . $msg . PHP_EOL, FILE_APPEND);
    }

    /**
     * 检查CSV文件行数，防止内存耗尽
     */
    private function checkMemoryAndFileSize(): void
    {
        $request = Di::getDefault()->get('request');
        
        if ($request->hasFiles()) {
            $files = $request->getUploadedFiles();
            $file = $files[0];
            $filename = $file->getName();
            $fileTempName = $file->getTempName();
            
            // 检查文件扩展名是否为csv
            $ext = $file->getExtension();
            if (strtolower($ext) === 'csv') {
                $estimatedRows = $this->estimateCsvRows($fileTempName);
                // 如果预估行数超过15000行，提示用户
                if ($estimatedRows > 15000) {
                    throw new ApiException(ApiException::MSG_ERROR, 'UID名单最大支持上传15000行，建议分批上传');
                }
            }
        }
    }
    
    /**
     * 直接统计CSV文件行数
     * @param string $filePath
     * @return int
     */
    private function estimateCsvRows(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return 0;
        }
        
        $lines = 0;
        // 直接统计所有行数
        while (($line = fgets($handle)) !== false) {
            $lines++;
        }
        
        fclose($handle);
        
        return $lines;
    }

}