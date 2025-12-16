<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsBroker;
use Imee\Models\Xs\XsBrokerUser;
use Imee\Models\Xs\XsEmoticons;
use Imee\Models\Xs\XsEmoticonsGroup;
use Imee\Models\Xs\XsEmoticonsIdentity;
use Imee\Models\Xs\XsEmoticonsMeta;
use Imee\Models\Xs\XsEmoticonsSellLog;
use Imee\Models\Xs\XsFamily;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstEmoticonsMeta;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class EmoticonsService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $conditions = [
            ['status', '<>', XsEmoticons::DELETE_STATUS]
        ];
        
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }
        if (isset($params['modity_time_sdate']) && !empty($params['modity_time_sdate'])) {
            $conditions[] = ['modity_time', '>=', strtotime($params['modity_time_sdate'])];
        }
        if (isset($params['modity_time_edate']) && !empty($params['modity_time_edate'])) {
            $conditions[] = ['modity_time', '<=', strtotime($params['modity_time_edate'])];
        }
        if (isset($params['status'])) {
            $conditions[] = ['status', '=', $params['status']];
        }

        $list = XsEmoticons::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $logs = BmsOperateLog::getFirstLogList('emoticons', array_column($list['data'], 'id'));

        foreach ($list['data'] as &$item) {
            if (!empty($item['extra'])) {
                $extra = (array)@json_decode($item['extra'], true);
                $item = array_merge($item, $extra);
            }
            $item['bigarea'] = $item['bigarea_id'];
            $item['durtion'] = intval($item['durtion'] / 86400);
            $item['operator'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
            $item['identity'] = (string) $item['identity'];
            $item['identity_obj'] = [
                'title' => XsEmoticons::$identityMap[$item['identity']],
                'value' => XsEmoticons::$identityMap[$item['identity']]
            ];
            if (in_array($item['identity'], [XsEmoticons::EMOTICONS_IDENTITY_USER, XsEmoticons::EMOTICONS_IDENTITY_FAMILY])) {
                $item['identity_obj']['type'] = 'manMadeModal';
                $item['identity_obj']['modal_id'] = 'table_modal';
                $item['identity_obj']['params'] = [
                    'guid'         => 'emoticonsidentitylist',
                    'emoticons_id' => $item['id']
                ];
                $identityList = XsEmoticonsIdentity::getListByEmoticonsId($item['id']);
                $item['ids'] = implode(',', $identityList);
            } else if ($item['identity'] == XsEmoticons::EMOTICONS_IDENTITY_SELL) {
                $item['identity_obj']['type'] = 'manMadeModal';
                $item['identity_obj']['modal_id'] = 'emoticons_identity_info';
                $item['identity_obj']['params'] = [
                    'price'   => $item['price'],
                    'durtion' => $item['durtion'],
                ];
            }

        }

        return $list;
    }

    public function add(array $params): array
    {
        $this->verify($params);
        $data = $this->setRpcData($params);
        list($res, $msg) = $this->rpcService->createEmoticons($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    public function edit(array $params): array
    {
        $this->verify($params);
        $data = $this->setRpcData($params);
        list($res, $msg) = $this->rpcService->updateEmoticons($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $data['id'], 'after_json' => $data];
    }

    private function setRpcData(array $params): array
    {
        $data = [
            'group_id'          => (int)$params['group_id'],
            'identity'          => (int)$params['identity'],
            'emoticons_meta_id' => (int)$params['emoticons_meta_id'],
            'bigarea_ids'       => [$params['bigarea_id'] ?? 0],
        ];

        // 创建时人群选择所有人或者VIP时大区可以多选
        if (empty($params['id']) && in_array($data['identity'], [XsEmoticons::EMOTICONS_IDENTITY_ALL, XsEmoticons::EMOTICONS_IDENTITY_VIP, XsEmoticons::EMOTICONS_IDENTITY_ACTIVE, XsEmoticons::EMOTICONS_IDENTITY_FAMILY_LEVEL])) {
            $data['bigarea_ids'] = $params['bigarea_ids'];
        }
        if ($data['identity'] == XsEmoticons::EMOTICONS_IDENTITY_SELL) {
            $data['price'] = (int)$params['price'];
            $data['durtion'] = (int)$params['durtion'];
        }
        if (in_array($data['identity'], [XsEmoticons::EMOTICONS_IDENTITY_FAMILY, XsEmoticons::EMOTICONS_IDENTITY_USER])) {
            $data['target_ids'] = array_map('intval', $params['ids']);
        } elseif ($data['identity'] == XsEmoticons::EMOTICONS_IDENTITY_FAMILY_LEVEL) {
            $lv = (int)$params['family_lv'];
            $data['extra'] = json_encode(['family_lv' => $lv]);
        }

        if (!empty($params['id'])) {
            $data['id'] = (int) $params['id'];
            $data['bigarea_id'] = intval($params['bigarea'] ?? 0);
            unset($data['bigarea_ids']);
        } else {
            $data['bigarea_ids'] = array_map('intval', $data['bigarea_ids']);
        }

        return $data;
    }

    public function delete(int $id): void
    {
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '表情包不存在');
        }
        list($res, $msg) = $this->rpcService->deletedEmoticons($id);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function status(int $id, int $status): void
    {
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '表情包不存在');
        }

        $data = [
            'id' => (int) $id,
            'status' => (int) $status
        ];

        list($res, $msg) = $this->rpcService->upOrDownEmoticons($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function check(int $id): array
    {
        $emoticons = XsEmoticons::findOne($id);
        $data = [];
        // 如果需要下架的配置为限时购买，且已有用户购买未到期，则二次确认弹窗
        if ($emoticons && $emoticons['identity'] == XsEmoticons::EMOTICONS_IDENTITY_SELL) {
            $info = XsEmoticonsIdentity::findOneByWhere([
                ['emoticons_id', '=', $id],
                ['expire_time', '>', time()]
            ]);
            if ($info) {
                $data = [
                    "is_confirm"   => 1,
                    "confirm_text" => "已有用户购买该表情未到期，是否确认下架"
                ];
            }
        }

        return $data;
    }

    public function getIdentityList(array $params): array
    {
        return XsEmoticonsIdentity::getListAndTotal([
            ['emoticons_id', '=', $params['emoticons_id']]
        ], 'target_id');
    }

    public function getSellLogList(array $params): array
    {
        $conditions = [];
        if (isset($params['target_id']) && !empty($params['target_id'])) {
            $conditions[] = ['target_id', '=', $params['target_id']];
        }
        if (isset($params['create_time_sdate']) && !empty($params['create_time_sdate'])) {
            $conditions[] = ['create_time', '>=', strtotime($params['create_time_sdate'])];
        }
        if (isset($params['create_time_edate']) && !empty($params['create_time_edate'])) {
            $conditions[] = ['create_time', '<=', strtotime($params['create_time_edate'])];
        }
        if (isset($params['emoticons_id'])) {
            $conditions[] = ['emoticons_id', '=', $params['emoticons_id']];
        }

        $list = XsEmoticonsSellLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $emoticonsIds = Helper::arrayFilter($list['data'], 'emoticons_id');
        $targetIds = Helper::arrayFilter($list['data'], 'target_id');
        $emoticonsList = XsEmoticons::getBatchCommon($emoticonsIds, ['id', 'group_id', 'emoticons_meta_id', 'price']);
        $userBigAreaList = XsUserBigarea::getUserBigAreaBatch($targetIds);
        $brokerList = XsBroker::getBatchCommon($targetIds, ['id'], 'creater');
        $brokerUserList = XsBrokerUser::getBatchCommon($targetIds, ['id'], 'uid');

        foreach ($list['data'] as &$item) {
            $item['group_id'] = $emoticonsList[$item['emoticons_id']]['group_id'] ?? '';
            $item['emoticons_meta_id'] = $emoticonsList[$item['emoticons_id']]['emoticons_meta_id'] ?? '';
            $item['price'] = $emoticonsList[$item['emoticons_id']]['price'] ?? '';
            $item['bigarea_id'] = $userBigAreaList[$item['target_id']]['bigarea_id'] ?? '';
            $item['create_time'] = Helper::now($item['create_time']);
            $item['is_anchor'] = $brokerList[$item['target_id']]['id'] ?? ($brokerUserList[$item['target_id']]['id'] ?? 0);
            $item['is_anchor'] = empty($item['is_anchor']) ? 0 : 1;
        }

        return $list;
    }

    private function verify(array &$params): void
    {
        if (in_array($params['identity'], [XsEmoticons::EMOTICONS_IDENTITY_USER, XsEmoticons::EMOTICONS_IDENTITY_FAMILY])) {
            $ids = $params['ids'] ?? '';
            $idsFile = $params['ids_file'] ?? '';
            if (empty($ids) && empty($idsFile)) {
                throw new ApiException(ApiException::MSG_ERROR, 'ID必须填写，可采用手动输入或者上传的形式');
            }
            if ($ids && $idsFile) {
                throw new ApiException(ApiException::MSG_ERROR, 'ID和File互斥只能填写一个');
            }
            $ids = empty($ids) ? $idsFile : $ids;
            $params['ids'] = $this->checkId($ids, $params['identity'], $params['bigarea_id']);
        } elseif ($params['identity'] == XsEmoticons::EMOTICONS_IDENTITY_FAMILY_LEVEL) {
            $level = $params['family_lv'] ?? 0;
            if (!in_array($level, array_keys(XsEmoticons::$familyLevelMap))) {
                throw new ApiException(ApiException::MSG_ERROR, '家族等级不正确');
            }
        }

        $map = $this->getIdentityMap($params['group_id']);

        if ($map) {
            if (!in_array($params['identity'], array_column($map, 'value'))) {
                throw new ApiException(ApiException::MSG_ERROR, '可用人群选项不正确，请重新选择');
            }
        }
    }

    public function getFamilyLevelMap(): array
    {
        return StatusService::formatMap(XsEmoticons::$familyLevelMap);
    }


    private function checkId(string $ids, int $type, int $bigArea): array
    {
        if (!is_array($ids)) {
            $ids = Helper::formatIdString($ids);
        }
        if ($type == XsEmoticons::EMOTICONS_IDENTITY_USER) {
            $errorId = XsUserProfile::checkUid($ids);
        } else {
            $errorId = XsFamily::checkFidAndBigArea($ids, $bigArea);
        }

        if ($errorId && is_array($errorId)) {
            throw new ApiException(ApiException::MSG_ERROR, '所上传输入ID必须存在且需和所属大区对应，以下ID错误:' . implode(',', $errorId));
        }

        // 特定UID还要根据UID获取大区再次校验
        if ($type == XsEmoticons::EMOTICONS_IDENTITY_USER) {
            $errorId = XsUserBigarea::checkUidBigArea($ids, $bigArea);
        }

        if ($errorId && is_array($errorId)) {
            throw new ApiException(ApiException::MSG_ERROR, '所上传输入ID必须存在且需和所属大区对应，以下ID错误:' . implode(',', $errorId));
        }

        return $ids;
    }

    public function getTagMap(): array
    {
        $tagList = XsEmoticonsGroup::findAll();

        if (empty($tagList)) {
            return $tagList;
        }

        $map = [];
        foreach ($tagList as $item) {
            $map[] = [
                'label' => "【ID:{$item['id']}】 " . $item['name'],
                'value' => $item['id']
            ];
        }

        return $map;
    }

    public function getMetaMap(): array
    {
        $metaList = XsEmoticonsMeta::findAll();

        if (empty($metaList)) {
            return $metaList;
        }

        $metas = XsstEmoticonsMeta::getBatchCommon(array_column($metaList, 'id'), ['meta_id', 'name', 'name_en'], 'meta_id');
        $map = [];
        foreach ($metaList as $item) {
            $detail = json_decode($item['detail'], true)[0];
            $name = empty($metas[$item['id']]) ? $detail['name']['cn'] ?? '' : ($metas[$item['id']]['name'] ?? '') . ' ' . ($metas[$item['id']]['name_en'] ?? '');
            $map[] = [
                'label' => "【ID:{$item['id']}】 " . $name,
                'value' => $item['id']
            ];
        }

        return $map;
    }

    public function getIdentityMap($id): array
    {
        // 如果所选择的所属标签是不可购买的，则可用人群不展示【限时购买】；
        // 如果所选择的所属标签是可购买的，则可用人群只展示【限时购买】。
        $group = XsEmoticonsGroup::findOne($id);
        $identityMap = [];

        if ($group['pay'] == XsEmoticonsGroup::PAY_YES) {
            $identityMap = [XsEmoticons::EMOTICONS_IDENTITY_SELL];
        } elseif ($group['pay'] == XsEmoticonsGroup::PAY_NO) {
            $identityMap = [
                XsEmoticons::EMOTICONS_IDENTITY_ALL,
                XsEmoticons::EMOTICONS_IDENTITY_FAMILY,
                XsEmoticons::EMOTICONS_IDENTITY_USER,
                XsEmoticons::EMOTICONS_IDENTITY_VIP,
                XsEmoticons::EMOTICONS_IDENTITY_FAMILY_LEVEL,
            ];
        } elseif ($group['pay'] == XsEmoticonsGroup::PAY_ACTIVE) {
            $identityMap = [
                XsEmoticons::EMOTICONS_IDENTITY_ACTIVE,
            ];
        }
        $map = [];
        foreach ($identityMap as $identity) {
            $map[] = [
                'label' => "【ID:{$identity}】 " . XsEmoticons::$identityMap[$identity],
                'value' => (string) $identity
            ];
        }
        return $map;
    }
}