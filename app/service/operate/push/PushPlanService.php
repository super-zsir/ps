<?php

namespace Imee\Service\Operate\Push;

use Imee\Exception\ApiException;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Helper;
use Imee\Service\Rpc\PushRpcService;
use Imee\Service\StatusService;

class PushPlanService
{
    use ImportTrait;

    /**
     * @var PushRpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PushRpcService();
    }

    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        [$res, $msg, $data] = $this->rpcService->getPushPlanList($conditions);

        if (!$res) {
            return [false, $msg, []];
        }

        foreach ($data['list'] as &$item) {
            $item['content_id'] = implode(',', $item['content_ids']);
            $item['update_time'] = Helper::now($item['update_time']);
        }

        return [true, '', $data];
    }

    public function add(array $params): array
    {
        $data = $this->validation($params);

        [$res, $msg, $id] = $this->rpcService->addPushPlan($data);

        if (!$res) {
            return [false, $msg, []];
        }
        return [true, '', ['id' => $id, 'after_json' => $data]];
    }

    public function uploadUids(): array
    {
        list($result, $msg, $data) = $this->uploadCsv(['uid']);
        if (!$result) {
            return [false, $msg, []];
        }

        $uids = array_column($data['data'], 'uid');
        $uids = array_filter($uids);
        $uids = array_unique($uids);
        $uids = array_values($uids);

        if (count($uids) > 5000) {
            return [false, '当前最大限制为5000个uid, 请分批发放', []];
        }
        if (empty($uids)) {
            return [false, '上传uid数据为空', []];
        }

        return [true, '', implode(',', $uids)];
    }

    public function edit(array $params): array
    {
        $data = $this->validation($params);

        [$res, $msg] = $this->rpcService->editPushPlan($data);

        if (!$res) {
            return [false, $msg];
        }

        return [true, ['after_json' => $data]];
    }

    public function delete(int $id)
    {
        [$res, $msg] = $this->rpcService->delPushPlan($id);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['after_json' => ['id' => $id]]];
    }

    public function info(int $id)
    {
        [$res, $msg, $data] = $this->rpcService->getPushPlan($id);

        if (!$res) {
            return [false, $msg, $data];
        }
        $filterList = $data['filter']['filter_list'] ?? [];
        $filterDialogue = $data['filter']['filter_dialogue'] ?? [];
        $filterWhere = [];
        if (!empty($filterDialogue)) {
            foreach ($filterDialogue as &$item) {
                $whereKey = $this->getFilterWhereName($item['field']);
                if (!in_array($whereKey, $filterWhere)) {
                    $filterWhere[] = $whereKey;
                }
                if (in_array($item['field'], ['update_time'])) {
                    $item['offset'][0] = ($item['offset'][0] ?? 0) / 3600;
                    $item['offset'][1] = ($item['offset'][1] ?? 0) / 3600;
                    $data['repeat']     = (string) ((int) $item['repeat']) ?? '0';
                    $data['update_time_min'] = $item['offset'][0];
                    $data['update_time_max'] = $item['offset'][1];
                }
            }
            $data['filter']['filter_dialogue'] = $filterDialogue;
        }
        if (!empty($filterList)) {
            foreach ($filterList as &$item) {
                if (in_array($item['field'], ['active_time', 'register_time'])) {
                    if (isset($item['offset']) && !empty($item['offset'])) {
                        $item['offset'][0] = ((int)$item['offset'][0]) / 3600;
                        $item['offset'][1] = ((int)$item['offset'][1]) / 3600;
                    }
                }
                $whereKey = $this->getFilterWhereName($item['field']);
                if (!in_array($whereKey, $filterWhere)) {
                    $filterWhere[] = $whereKey;
                }
                if (in_array($item['field'], ['last_room_time', 'last_live_time'])) {
                    $data['room_type'][] = explode('_', $item['field'])[1];
                    $data['entry_time_min'] = ($item['offset'][0] ?? 0) / 3600;
                    $data['entry_time_max'] = ($item['offset'][1] ?? 0) / 3600;
                }
                if (in_array($item['field'], ['last_ludo_time', 'last_carrom_time', 'last_ball_time'])) {
                    $data['play_type'][] = explode('_', $item['field'])[1];
                    $data['join_time_min'] = ($item['offset'][0] ?? 0) / 3600;
                    $data['join_time_max'] = ($item['offset'][1] ?? 0) / 3600;
                }
            }
            $data['filter']['filter_list'] = $filterList;
        }
        $data['filter_where'] = $filterWhere;
        if (isset($data['filter']['big_area_id_list']) && !empty($data['filter']['big_area_id_list'])) {
            $data['filter']['big_area_id_list'] = array_map('strval', $data['filter']['big_area_id_list']);
        }
        return [true, '', $data];
    }

    public function copy(array $params): array
    {
        [$res, $msg, $data] = $this->rpcService->getPushPlan($params['id']);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        unset($params['id']);
        $json = $this->validation($params);
        $equals = false;
        if (in_array($data['status'], [0, 1])) {
            $json['content_ids'] = explode(',', $json['content_id']);
            $diffKey = ['content_ids', 'filter_type', 'mode', 'start_time', 'end_time', 'frequency'];
            foreach ($diffKey as $key) {
                $oldValue = $data[$key] ?? '';
                $newValue = $json[$key] ?? '';
                if ($oldValue != $newValue) {
                    $equals = true;
                    break;
                }
            }
            if (!$equals && $this->diffFilter($data['filter'], $json['filter'])) {
                throw new ApiException(ApiException::MSG_ERROR, '复制内容不可完全一致');
            }
        }
        unset($json['content_ids']);
        [$res, $msg, $id] = $this->rpcService->addPushPlan($json);
        if (!$res) {
            return [false, $msg, []];
        }
        $data['id'] = $id;
        return [true, '', ['after_json' => $data]];
    }

    private function diffFilter($oldFilter, $newFilter)
    {
        foreach (['uid_list', 'big_area_id_list'] as $list) {
            $oldList = $oldFilter[$list] ?? [];
            $newList = $newFilter[$list] ?? [];
            if (array_diff($oldList, $newList)) {
                return false;
            }
        }
        $newFilterList = $newFilter['filter_list'] ?? [];
        $oldFilterList = $oldFilter['filter_list'] ?? [];

        $newFilterDialogue = $newFilter['filter_dialogue'] ?? [];
        $oldFilterDialogue = $oldFilter['filter_dialogue'] ?? [];

        $listRes = $this->diffFilterList($oldFilterList, $newFilterList);
        $dialogueRes = $this->diffFilterDialogue($oldFilterDialogue, $newFilterDialogue);
        if ($listRes || $dialogueRes) {
            return false;
        }
        return true;
    }

    private function diffFilterList($oldFilterList, $newFilterList)
    {
        $newKeys = $oldKeys = [];
        if ($oldFilterList) {
            $oldFilterList = array_column($oldFilterList, null, 'field');
            $oldKeys = array_keys($oldFilterList);
        }
        if ($newFilterList) {
            $newFilterList = array_column($newFilterList, null, 'field');
            $newKeys = array_keys($newFilterList);
        }
        if (count($oldKeys) != count($newKeys)) {
            return true;
        } else if (array_diff($oldKeys, $newKeys)) {
            return true;
        }
        foreach ($oldFilterList as $field => $fieldInfo) {
            if ($field == 'sex' && $fieldInfo['value'] != $newFilterList[$field]['value']) {
                return true;
            }
            if (in_array($field, ['register_time', 'active_time'])) {
                $oldOffset = $fieldInfo['offset'] ?? [];
                $newOffset = $newFilterList[$field]['offset'] ?? [];
                if (array_diff($oldOffset, $newOffset)) {
                    return true;
                }
            }
            if (in_array($field, ['register_time', 'active_time', 'last_room_time', 'last_live_time', 'last_ludo_time', 'last_carrom_time', 'last_ball_time'])) {
                $oldMin = $fieldInfo['min'] ?? 0;
                $newMin = $newFilterList[$field]['min'] ?? 0;
                $oldMax = $fieldInfo['max'] ?? 0;
                $newMax = $newFilterList[$field]['max'] ?? 0;
                if ($newMin != $oldMin || $newMax != $oldMax) {
                    return true;
                }
            }
        }

        return false;
    }

    private function diffFilterDialogue($oldFilterDialogue, $newFilterDialogue)
    {
        $newKeys = $oldKeys = [];
        if ($oldFilterDialogue) {
            $oldFilterDialogue = array_column($oldFilterDialogue, null, 'field');
            $oldKeys = array_keys($oldFilterDialogue);
        }
        if ($newFilterDialogue) {
            $newFilterDialogue = array_column($newFilterDialogue, null, 'field');
            $newKeys = array_keys($newFilterDialogue);
        }
        if (count($oldKeys) != count($newKeys)) {
            return true;
        } else if (array_diff($oldKeys, $newKeys)) {
            return true;
        }
        foreach ($oldFilterDialogue as $field => $fieldInfo) {
            if (in_array($field, ['update_time'])) {
                $oldOffset = $fieldInfo['offset'] ?? [];
                $newOffset = $newFilterDialogue[$field]['offset'] ?? [];
                $oldValue = $fieldInfo['repeat'] ?? false;
                $newValue = $newFilterDialogue[$field]['repeat'] ?? false;
                if (array_diff($oldOffset, $newOffset) || $oldValue != $newValue) {
                    return true;
                }
            }
        }

        return false;
    }

    public function stop(int $id)
    {
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);
        $data = [
            'id' => $id,
            'modifier' => $admin,
            'status' => 3
        ];
        [$res, $msg] = $this->rpcService->stopPushPlan($id);

        if (!$res) {
            return [false, $msg, []];
        }
        return [true, '', ['after_json' => $data]];
    }

    private function validation(array $params)
    {
        $adminId = Helper::getSystemUid();
        $admin = Helper::getAdminName($adminId);
        $time = time();
        $json = [
            'content_id' => implode(',', $params['content_id']),
            'name' => $params['name'],
            'content_repeat' => (int)$params['content_repeat'],
            'filter_type' => (int)$params['filter_type'],
            'mode' => (int)$params['mode'],
            'app_id' => APP_ID,
            'modifier' => $admin,
            'update_time' => $time,
            'filter' => [],
        ];
        if ($params['content_repeat'] == 1) {
            if (empty($params['repeat_time'])) {
                throw new ApiException(ApiException::MSG_ERROR, '文案排重选择是时，必须填写排重时间区间');
            }
            $json['repeat_time'] = (int)$params['repeat_time'];
        }
        $filter = $params['filter'] ?? [];
        if ($params['filter_type'] == 1) {
            if (empty($filter['big_area_id_list'])) {
                throw new ApiException(ApiException::MSG_ERROR, '推送类型为条件时，必须选择大区');
            }
            $filterList = [];
            if (isset($filter['filter_list'])) {
                foreach ($filter['filter_list'] as &$item) {
                    if (!empty($item['value'])) {
                        $item['value'] = (int) $item['value'];
                    }
                    if (in_array($item['field'], ['register_time', 'active_time'])) {
                        $item['min'] = strtotime($item['min'] ?? 0);
                        $item['max'] = strtotime($item['max'] ?? 0);
                        if ($item['max'] <= $item['min']) {
                            throw new ApiException(ApiException::MSG_ERROR, $this->getErrorMsg($item['field']) . '的开始时间不能大于结束时间');
                        }
                    }
                    if (!empty($item['offset'])) {
                        $item['offset'][0] = (int)($item['offset'][0] ?? 0) * 3600;
                        $item['offset'][1] = (int)($item['offset'][1] ?? 0) * 3600;
                    }
                    $filterList[] = $item;
                }
            }
            $filterDialogue = [];
            if (isset($filter['filter_dialogue'])) {
                foreach ($filter['filter_dialogue'] as &$item) {
                    if (isset($item['repeat'])) {
                        $item['repeat'] = ((bool) $item['repeat'] ?? false);
                    }
                    if (!empty($item['offset'])) {
                        $item['offset'][0] = (int)($item['offset'][0] ?? 0) * 3600;
                        $item['offset'][1] = (int)($item['offset'][1] ?? 0) * 3600;
                    }
                    $filterDialogue[] = $item;
                }
            }
            $filterListName = array_column($filterList, 'field');
            $filterDialogueName = array_column($filterDialogue, 'field');
            foreach ($params['content_id'] as $contentId) {
                [$res, $msg, $info] = $this->rpcService->getPushContent($contentId);
                if (empty($info)) {
                    throw new ApiException(ApiException::MSG_ERROR, "所选文案{$contentId}不存在，请重新选择");
                }
                if (($info['mod'] == 1 || $info['mod'] == 2) &&
                    (!in_array('last_room_time', $filterListName) && !in_array('last_live_time', $filterListName))
                ) {
                    throw new ApiException(ApiException::MSG_ERROR, '您所关联的文案类型为语音房或视频房，筛选条件必须包含房间行为');
                } else if ($info['mod'] == 3 && !in_array('update_time', $filterDialogueName)) {
                    throw new ApiException(ApiException::MSG_ERROR, '您所关联的文案类型为聊天页面，筛选条件必须包含IM消息');
                }
            }
            $filterWhereArr = [
                'big_area_id_list' => array_map('intval', $filter['big_area_id_list']),
                'filter_list' => $filterList,
                'filter_dialogue' => $filterDialogue
            ];
        } else {
            if (empty($filter['uid_list'])) {
                throw new ApiException(ApiException::MSG_ERROR, '推送类型为名单时，必须上传用户名单');
            }
            $uidList = is_array($filter['uid_list']) ? $filter['uid_list'] : explode(',', $filter['uid_list']);
            $filterWhereArr = [
                'uid_list' => array_map('intval', $uidList),
            ];
        }
        if ($params['mode'] == 2) {
            if (empty($params['frequency'])) {
                throw new ApiException(ApiException::MSG_ERROR, '定时推送时频次必选');
            }
            $json['frequency'] = (int)$params['frequency'];
            if ($params['frequency'] == 1) {
                if (empty($params['start_time'])) {
                    throw new ApiException(ApiException::MSG_ERROR, '单次推送时开始时间必选');
                }
                $json['start_time'] = strtotime($params['start_time']);
            } else {
                if (empty($params['start_time']) || empty($params['end_time'])) {
                    throw new ApiException(ApiException::MSG_ERROR, '每日、每周、每月推送时开始和结束时间必选');
                }
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                if ($params['start_time'] > $params['end_time']) {
                    throw new ApiException(ApiException::MSG_ERROR, '推送的开始时间不能大于结束时间');
                }
                $json['start_time'] = $params['start_time'];
                $json['end_time'] = $params['end_time'];
            }
        }
        $json['filter'] = $filterWhereArr;
        if (isset($params['id'])) {
            $json['id'] = (int)$params['id'];
        } else {
            $json['cerator'] = $admin;
            $json['create_time'] = $time;
        }
        return $json;
    }

    private function getConditions(array $params)
    {
        $conditions = [
            'page' => (int)($params['page'] ?? 1),
            'page_num' => (int)($params['limit'] ?? 15),
            'app_id' => APP_ID,
        ];

        if (isset($params['name'])) {
            $conditions['name'] = $params['name'];
        }

        if (isset($params['status'])) {
            $conditions['status'] = (int)$params['status'];
        }

        if (isset($params['id'])) {
            $conditions['id'] = (int)$params['id'];
        }

        if (isset($params['content_id'])) {
            $conditions['content_id'] = (int)$params['content_id'];
        }

        return $conditions;
    }

    private function getErrorMsg($field)
    {
        $message = [
            'register_time' => '注册时间',
            'active_time' => '活跃时间',
            'last_room_time' => '房间行为',
            'last_live_time' => '房间行为',
            'last_ludo_time' => '玩法行为',
            'last_ball_time' => '玩法行为',
            'last_carrom_time' => '玩法行为',
            'update_time' => 'IM消息',
        ];

        return $message[$field] ?? '';
    }

    private function getFilterWhereName($field)
    {
        $where = [
            'sex' => '1',
            'active_time' => '2',
            'register_time' => '2',
            'last_room_time' => '3',
            'last_live_time' => '3',
            'last_ludo_time' => '4',
            'last_ball_time' => '4',
            'last_carrom_time' => '4',
            'update_time' => '5',
        ];

        return $where[$field] ?? '';
    }

    public function getLogList(array $params): array
    {
        [$res, $msg, $data] = $this->rpcService->getPushRecordList($params);

        if (!$res) {
            return [false, $msg, []];
        }

        foreach ($data['list'] as &$item) {
            $item['push_time'] = Helper::now($item['push_time']);
        }

        return [true, '', $data];
    }

    public function options()
    {
        $bigArea = XsBigarea::getBigAreaCnNameById();
        $params = [
            'status' => 2,
            'page' => 1,
            'limit' => 5000
        ];
        [$res, $msg, $data] = $this->rpcService->getPushContentList($params);
        $content = [];
        if ($data['list']) {
            foreach ($data['list'] as $item) {
                $content[] = [
                    'label' => $item['id'] . '-' . $item['title'],
                    'value' => $item['id']
                ];
            }
        }
        return ['area' => StatusService::formatMap($bigArea, 'label,value'), 'content' => $content];
    }

}