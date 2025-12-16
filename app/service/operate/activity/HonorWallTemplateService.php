<?php

namespace Imee\Service\Operate\Activity;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsActHonourWallConfig;
use Imee\Models\Xs\XsActHonourWallTab;
use Imee\Models\Xs\XsFamily;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class HonorWallTemplateService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    /**
     * 列表
     * @param array $params
     * @return array
     */
    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);

        $list = XsActHonourWallConfig::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list)) {
            return $list;
        }

        $adminList = CmsUser::getUserNameList(Helper::arrayFilter($list['data'], 'admin_id'));
        foreach ($list['data'] as &$item) {
            $item['page_url'] = $this->formatPageUrl($item['id']);
            $visionContent = json_decode($item['vision_content_json'], true);
            $item['head_image'] = Helper::getHeadUrl($visionContent['head_image']);
            $item['admin_id'] = $item['admin_id'] . '-' . ($adminList[$item['admin_id']] ?? '');
            $item['update_time'] = Helper::now($item['update_time']);
        }

        return $list;
    }

    /**
     * 获取page_url
     * @param int $id
     * @return array
     */
    private function formatPageUrl(int $id): array
    {
        $url = ENV == 'dev' ? XsActHonourWallConfig::PAGE_URL_DEV : XsActHonourWallConfig::PAGE_URL;
        $pageUrl = sprintf($url, $id);

        return [
            'title'        => $pageUrl,
            'value'        => $pageUrl,
            'type'         => 'url',
            'url'          => $pageUrl,
            'resourceType' => 'static'
        ];
    }

    /**
     * 获取列表查询条件
     * @param array $params
     * @return array
     */
    private function getConditions(array $params): array
    {
        $conditions = [];

        $id = intval($params['id'] ?? 0);
        $language = trim($params['language'] ?? 0);
        $isShow = intval($params['is_show'] ?? -1);
        $creatorId = intval($params['creator_id'] ?? 0);

        $id && $conditions[] = ['id', '=', $id];
        $language && $conditions[] = ['language', '=', $language];
        $isShow > 0 && $conditions[] = ['is_show', '=', $isShow];
        $creatorId && $conditions[] = ['admin_id', '=', $creatorId];

        return $conditions;
    }

    /**
     * 创建
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function create(array $params): array
    {
        $data = $this->validate($params);
        list($res, $id) = $this->rpcService->createActHonourWall($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc: error, msg: ' . $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 编辑
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function modify(array $params): array
    {
        $data = $this->validate($params);

        list($res, $id) = $this->rpcService->editActHonourWall($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc: error, msg: ' . $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 详情
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function copy(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id is empty');
        }

        $config = XsActHonourWallConfig::findOne($id);

        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, '荣誉墙配置获取失败');
        }

        // tab 相关配置不需要复制
        $data = [
            'admin_id'             => $params['admin_uid'],
            'title'                => $config['title'],
            'language'             => $config['language'],
            'is_show'              => $config['is_show'],
            'vision_content_json'  => $config['vision_content_json'],
            'rule_content'         => $config['rule_content'],
            'act_honour_wall_tabs' => []
        ];

        list($res, $id) = $this->rpcService->createActHonourWall($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc: error, msg: ' . $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 详情
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function info(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id is empty');
        }

        $config = XsActHonourWallConfig::findOne($id);

        if (empty($config)) {
            throw new ApiException(ApiException::MSG_ERROR, '荣誉墙配置获取失败');
        }

        $tabList = XsActHonourWallTab::getListByWhere([['act_honour_wall_config_id', '=', $id]], '*', 'tab_group asc, id asc');


        return [
            'title'               => $config['title'],
            'language'            => $config['language'],
            'is_show'             => (string)$config['is_show'],
            'vision_content_json' => $this->formatVisionContent($config['vision_content_json']),
            'rule_content'        => $config['rule_content'],
            'act_honour_wall_tab' => $this->formatTabList($tabList)
        ];
    }

    /**
     * 格式化色值、资源部分
     * @param string $visionContentJson
     * @return array
     */
    private function formatVisionContent(string $visionContentJson): array
    {
        $visionContent = json_decode($visionContentJson, true);

        // 图片资源前端需要展示、拼接_all 代表 资源全链
        foreach ($visionContent as $key => $value) {
            if (str_contains($key, '_image') && $value) {
                $visionContent[$key . '_all'] = Helper::getHeadUrl($value);
            }
        }

        return $visionContent;
    }

    /**
     * 获取tab 并 format成前端需要格式
     * @param array $tabList
     * @return array
     */
    private function formatTabList(array $tabList): array
    {
        $newTabList = [];

        foreach ($tabList as $tab) {
            $key = $tab['tab_group'] . '&&' . $tab['tab_name'];
            $newTabList[$key][] = $tab;
        }

        $actHonourWallTabList = [];


        foreach ($newTabList as $key => $value) {
            [$tabGroup, $tabName] = explode('&&', $key);
            $dataSourceList = [];
            foreach ($value as $item) {
                $source = (string)XsActHonourWallTab::getSourceType($item['source']);
                $tmp = [
                    'id'             => $item['id'],
                    'source'         => $source,
                    'object_type'    => (string)$item['source'],
                    'title'          => $item['title'],
                    'act_id'         => (string)$item['act_id'],
                    'button_list_id' => (string)$item['button_list_id'],
                    'object'         => $this->formatObject($item['source'], $item['object']),
                ];

                $dataSourceList[] = $tmp;
            }
            $actHonourWallTabList[] = [
                'id'               => $tabGroup,
                'name'             => $tabName,
                'data_source_list' => $dataSourceList,
            ];
        }

        return $actHonourWallTabList;
    }

    /**
     * 格式化object
     * @param int $objectType
     * @param string $object
     * @return array
     */
    private function formatObject(int $objectType, string $object): array
    {
        $object = json_decode($object, true);
        $newObject = [];

        switch ($objectType) {
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_RANK:
                $newObject = $object;
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_CUSTOM:
                $uidArr = $object['uids'] ?? [];
                foreach ($uidArr as $key => $uid) {
                    $newObject[] = [
                        'id'  => $key + 1,
                        'uid' => $uid
                    ];
                }
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_CONTRIBUTION:
                $newObject = $this->formatObjectCommon($object['contribution_objects'] ?? [], 'uid');
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_FAMILY:
                $newObject = $this->formatObjectCommon($object['family_objects'] ?? [], 'fid');
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_CP:
                $cpObject = $object['cp_objects'] ?? [];
                foreach ($cpObject as $key => $uid) {
                    $newObject[] = [
                        'id'   => $key + 1,
                        'uid1' => $uid['uid1'] ?? '',
                        'uid2' => $uid['uid2'] ?? '',
                    ];
                }
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_GIFT:
                $newObject = $this->formatObjectCommon($object['custom_gift_objects'] ?? [], 'gift_id');
                break;
        }

        return $newObject;
    }


    /**
     * object 转换成前端要的格式
     * @param array $object
     * @param string $field
     * @return array
     */
    private function formatObjectCommon(array $object, string $field): array
    {
        $newObject = [];
        foreach ($object as $key => $value) {
            $members = $value['members'] ?? [];
            $newObject[] = [
                'id'   => $key + 1,
                'mid'  => (string) $value[$field],
                'uid1' => $members[0] ?? '',
                'uid2' => $members[1] ?? '',
                'uid3' => $members[2] ?? '',
            ];
        }

        return $newObject;
    }

    /**
     * 删除
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function delete(array $params): array
    {
        $id = intval($params['id'] ?? 0);

        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'id is empty');
        }

        list($res, $msg) = $this->rpcService->delActHonourWall(['id' => $id]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc: error, msg: ' . $msg);
        }

        return ['id' => $id, 'after_json' => []];
    }

    /**
     * 验证数据
     * @param array $params
     * @return array
     * @throws ApiException
     */
    private function validate(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $title = trim($params['title'] ?? '');
        $language = trim($params['language'] ?? '');
        $isShow = trim($params['is_show'] ?? -1);
        $ruleContent = trim($params['rule_content'] ?? '');
        $visionContentJson = $params['vision_content_json'] ?? [];
        $actHonourWallTabList = $params['act_honour_wall_tab'] ?? [];

        $data = [
            'title'               => $title,
            'language'            => $language,
            'is_show'             => $isShow,
            'rule_content'        => $ruleContent,
            'vision_content_json' => json_encode($visionContentJson),
            'admin_id'            => $params['admin_uid'],
        ];
        // 编辑添加下id
        $id && $data['id'] = $id;

        $newActHonourWallTabList = [];

        foreach ($actHonourWallTabList as $tab) {
            $newDataSourceList = [];
            $name = trim($tab['name'] ?? '');
            $dataSourceList = $tab['data_source_list'] ?? [];
            foreach ($dataSourceList as $dataSource) {
                $newDataSourceList[] = $this->validateDataSource($dataSource, $id);
            }

            $newActHonourWallTabList[] = [
                'name'             => $name,
                'data_source_list' => $newDataSourceList,
            ];
        }

        $data['act_honour_wall_tabs'] = $newActHonourWallTabList;

        return $data;
    }

    /**
     * 验证data_source
     * @param array $dataSource
     * @param int $id
     * @return array
     * @throws ApiException
     */
    private function validateDataSource(array $dataSource, int $id): array
    {
        $source = intval($dataSource['source'] ?? 0);
        $title = trim($dataSource['title'] ?? '');
        $actId = intval($dataSource['act_id'] ?? 0);
        $buttonListId = intval($dataSource['button_list_id'] ?? 0);
        $objectType = intval($dataSource['object_type'] ?? 0);
        $object = $dataSource['object'] ?? [];
        // 验证下活动相关数据
        if ($actId) {
            if (empty($buttonListId)) {
                throw new ApiException(ApiException::MSG_ERROR, '活动ID配置后，buttonList ID为必填项');
            }
            $this->validateActivity($actId, $buttonListId, $id);
        }

        $realSource = $this->setSource($source, $objectType);

        return [
            'source'         => $realSource,
            'title'          => $title,
            'act_id'         => $actId,
            'button_list_id' => $buttonListId,
            'object'         => $this->validateObject($realSource, $object),
        ];
    }

    /**
     * 验证活动信息
     * @param int $actId
     * @param int $buttonListId
     * @param int $id
     * @return void
     * @throws ApiException
     */
    private function validateActivity(int $actId, int $buttonListId, int $id): void
    {
        $act = BbcTemplateConfig::findOne($actId);
        if (empty($act)) {
            throw new ApiException(ApiException::MSG_ERROR, '活动不存在');
        }

        // 活动仅支持用户维度、礼物维度类型（除周星礼物榜）活动
        if ($act['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE || !in_array($act['type'], [BbcTemplateConfig::TYPE_RANK, BbcTemplateConfig::TYPE_GIFT_RANK])) {
            throw new ApiException(ApiException::MSG_ERROR, '不支持当前活动类型');
        }

        $buttonList = BbcRankButtonList::findOne($buttonListId);
        if (empty($buttonList)) {
            throw new ApiException(ApiException::MSG_ERROR, 'buttonList不存在');
        }

        // 每个button_list只能绑定一个荣誉墙
        $tab = XsActHonourWallTab::findOneByWhere([['button_list_id', '=', $buttonListId]]);
        if (empty($id) && $tab) {
            throw new ApiException(ApiException::MSG_ERROR, 'buttonList已被绑定过');
        }
    }

    /**
     * 验证对象
     * @param int $source
     * @param array $object
     * @return array|array[]
     * @throws ApiException
     */
    private function validateObject(int $source, array $object): array
    {
        // 根据不同数据来源验证方式不同
        switch ($source) {
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_RANK:
                $objectConfig = $this->validateShowTop($object);
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_USER:
                $objectConfig = $this->validateUids($object);
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_CONTRIBUTION:
                $objectConfig = $this->validateObjectId($object, 'uid', 'contribution_objects');
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_FAMILY:
                $objectConfig = $this->validateObjectId($object, 'fid', 'family_objects');
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_CP:
                $objectConfig = $this->validateCpObject($object);
                break;
            case XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_GIFT:
                $objectConfig = $this->validateObjectId($object, 'gift_id', 'custom_gift_objects');
                break;
            // todo: 待新增类型
            default:
                throw new ApiException(ApiException::MSG_ERROR, '未知来源类型');
        }

        return $objectConfig;
    }

    /**
     * 验证榜单对象
     * @param array $object
     * @return array
     * @throws ApiException
     */
    private function validateShowTop(array $object): array
    {
        $showTop = intval($object['show_top'] ?? 0);
        if ($showTop < 1 || $showTop > 10) {
            throw new ApiException(ApiException::MSG_ERROR, '展示TOP为必填项，且为1-10之间的正整数');
        }
        return ['show_top' => $showTop];
    }

    /**
     * 验证用户对象
     * @param array $object
     * @return array
     * @throws ApiException
     */
    private function validateUids(array $object): array
    {
        $uids = Helper::arrayFilter($object, 'uid');
        if (count($uids) < 1 || count($uids) > 100) {
            throw new ApiException(ApiException::MSG_ERROR, '用户UID最小1名、最多100名');
        }

        $this->validateUid($uids);
        return ['uids' => Helper::formatIds($uids)];
    }

    /**
     * 验证对象公共方法
     * @param array $object
     * @param string $idField
     * @param string $objectName
     * @return array[]
     * @throws ApiException
     */
    private function validateObjectId(array $object, string $idField, string $objectName): array
    {
        if (count($object) < 1 || count($object) > 100) {
            throw new ApiException(ApiException::MSG_ERROR, '组别最小1组、最多100组');
        }

        $newObject = [];
        foreach ($object as $item) {
            $uidArr = [];
            $mid = intval($item['mid'] ?? 0);
            if (empty($mid)) {
                throw new ApiException(ApiException::MSG_ERROR, '房主UID/家族长UID/礼物ID为必填项');
            }
            foreach ($item as $k => $v) {
                if (str_contains($k, 'uid') && $v) {
                    $uidArr[] = intval($v);
                }
            }

            // 面向对象为礼物时需要验证下礼物id
            $idField == 'gift_id' && $this->validateGiftId($mid);
            $idField == 'fid' && $this->validateFamilyId($mid);

            $this->validateUid($uidArr);

            $newObject[] = [
                $idField  => $mid,
                'members' => $uidArr
            ];
        }

        return [$objectName => $newObject];
    }

    /**
     * 验证礼物id
     * @param int $gid
     * @return void
     * @throws ApiException
     */
    private function validateGiftId(int $gid): void
    {
        $gift = XsGift::findOne($gid);
        if (empty($gift) || $gift['is_customized'] != 1) {
            throw new ApiException(ApiException::MSG_ERROR, '礼物ID不存在或者非定制礼物');
        }
    }

    /**
     * 验证家族id
     * @param int $gid
     * @return void
     * @throws ApiException
     */
    private function validateFamilyId(int $fid): void
    {
        $family = XsFamily::findOne($fid);
        if (empty($family)) {
            throw new ApiException(ApiException::MSG_ERROR, '家族ID不存在');
        }
    }

    /**
     * 验证cp对象
     * @param array $objectList
     * @return array[]
     * @throws ApiException
     */
    private function validateCpObject(array $objectList): array
    {
        if (count($objectList) < 1 || count($objectList) > 100) {
            throw new ApiException(ApiException::MSG_ERROR, 'CP组最小1组、最多100组');
        }
        $newCpObjectList = [];
        $uidArr = [];
        foreach ($objectList as $cpObjectKey => $cpObject) {
            $cpUid1 = intval($cpObject['uid1'] ?? 0);
            $cpUid2 = intval($cpObject['uid2'] ?? 0);
            $cpObjectNum = $cpObjectKey + 1;
            if (empty($cpUid1) || empty($cpUid2)) {
                throw new ApiException(ApiException::MSG_ERROR, sprintf('第%s组CP组用户UID为必填项', $cpObjectNum));
            }
            $uidArr = array_merge($uidArr, [$cpUid1, $cpUid2]);
            $newCpObjectList[] = ['uid1' => $cpUid1, 'uid2' => $cpUid2];
        }

        $this->validateUid($uidArr);
        return ['cp_objects' => $newCpObjectList];
    }

    /**
     * 验证uid
     * @param array $uidArr
     * @return void
     * @throws ApiException
     */
    private function validateUid(array $uidArr): void
    {
        $existsUid = XsUserProfile::checkUid($uidArr);
        if ($existsUid) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('用户UID%s不存在', implode(',', $existsUid)));
        }
    }

    /**
     * 设置真实source
     * @param int $scope
     * @param int $objectType
     * @return int
     */
    private function setSource(int $scope, int $objectType): int
    {
        return ($scope == XsActHonourWallTab::ACT_HONOUR_WALL_SOURCE_RANK) ? $scope : $objectType;
    }

    /**
     * 格式化前端提交的参数格式
     * @param array $params
     * @return void
     */
    public function formatParams(array &$params): void
    {
        if (!empty($params['vision_content_json'])) {
            $params['vision_content_json'] = @json_decode($params['vision_content_json'], true);
        }

        if (!empty($params['act_honour_wall_tab'])) {
            $params['act_honour_wall_tab'] = @json_decode($params['act_honour_wall_tab'], true);
        }
    }

    public function getOptions(): array
    {
        $statusService = new StatusService();
        $language = $statusService->getLanguageMap(null, 'label,value');
        $isShow = $this->getIsShowMap();
        $source = StatusService::formatMap(XsActHonourWallTab::$sourceMap, 'label,value');
        $objectType = StatusService::formatMap(XsActHonourWallTab::$objectTypeMap, 'label,value');
        $giftList = StatusService::formatMap(XsGift::getCustomGiftMap(), 'label,value');
        $activity = StatusService::formatMap(BbcTemplateConfig::getActivityMap(), 'label,value');

        return compact('language', 'isShow', 'source', 'objectType', 'giftList', 'activity');
    }

    public function getButtonListMap(int $actId): array
    {
        return StatusService::formatMap(BbcRankButtonList::getButtonListMapByActId($actId), 'label,value');
    }

    public function getIsShowMap(): array
    {
        return StatusService::formatMap(XsActHonourWallConfig::$isShowMap, 'label,value');
    }
}