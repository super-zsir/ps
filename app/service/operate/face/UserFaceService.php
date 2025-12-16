<?php

namespace Imee\Service\Operate\Face;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsUserFace;
use Imee\Models\Xs\XsUserFaceAudit;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class UserFaceService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    const REPLACE_IMAGE_ACTION = 1;
    const REPLACE_UID_ACTION = 2;
    const DELETE_IMAGE_ACTION = 3;
    const UPDATE_AUDIT_STATUS = 4;

    const LIKE = 63;

    public function getList(array $params): array
    {
        $list = XsUserFace::getListAndTotal($this->getConditions($params), '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $entityIdArr = array_column($list['data'], 'entity_id');
        $userFaceAuditList = XsUserFaceAudit::getListByEntityId($entityIdArr, 'entity_id, face, op');
        $uidArr = array_column($list['data'], 'uid');
        $userInfo = XsUserProfile::getUserProfileBatch($uidArr);
        foreach ($list['data'] as &$item) {
            $item['image'] = $userFaceAuditList[$item['entity_id']]['face'] ?? '';
            $item['op'] = $userFaceAuditList[$item['entity_id']]['op'] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['name'] = $userInfo[$item['uid']]['name'] ?? '';
        }
        return $list;
    }

    public function replace(array $params): array
    {
        $this->verify($params);
        $userFace = $this->getUserFaceInfo($params['uid']);

        $data = [
            'uid' => (int) $params['uid'],
        ];
        $admin = Helper::getAdminName($params['admin_uid']);
        switch ($params['type']) {
            case self::REPLACE_IMAGE_ACTION:
                $data['image'] = Helper::getHeadUrl($params['image']);
                list($res, $msg) = $this->rpcService->faceChange($data);
                break;
            case self::REPLACE_UID_ACTION:
                $data['new_uid'] = (int) $params['new_uid'];
                $data['operator'] = $admin;
                list($res, $msg) = $this->rpcService->faceReplace($data);
                break;
            case self::DELETE_IMAGE_ACTION:
                list($res, $msg) = $this->rpcService->faceDelete($data);
                break;
            case self::UPDATE_AUDIT_STATUS:
                $data['id'] = (int) $params['id'];
                $data['status'] = (int) $params['status'];
                $data['operator'] = $admin;
                list($res, $msg) = $this->rpcService->faceStatus($data);
                break;
        }

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $beforeJson = [
            'image'   => $userFace['face'] ?? '',
            'uid'     => $userFace['uid'] ?? '',
            'new_uid' => $userFace['uid'] ?? '',
            'status'  => $userFace['status'] ?? '',
        ];

        return ['id' => $params['id'], 'after_json' => $data, 'before_json' => $beforeJson];

    }

    private function verify(array $params): void
    {
        if ($params['type'] == self::REPLACE_UID_ACTION) {
            $user = XsUserProfile::findOne($params['new_uid']);
            if (empty($user)) {
                throw new ApiException(ApiException::MSG_ERROR, '替换的UID不存在');
            }
        }
    }

    /**
     * 获取基本人脸信息
     * @param int $uid
     * @return array
     */
    private function getUserFaceInfo(int $uid): array
    {
        $userFace = XsUserFace::findOneByWhere([['uid', '=', $uid]]);
        if ($userFace) {
            $userFaceAudit = XsUserFaceAudit::findOneByWhere([['entity_id', '=', $userFace['entity_id']]]);
            $userFace['face'] = $userFaceAudit['face'] ?? '';
            $userFace['status'] = $userFaceAudit['status'] ?? 0;
        }
        return $userFace;
    }

    private function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }

        if (isset($params['like_id']) && !empty($params['like_id'])) {
            $conditions[] = ['uid', 'IN', Helper::formatIdString($params['like_id'])];
        }

        return $conditions;
    }

    /**
     * 人脸审核记录列表
     * @param array $params
     * @return array
     */
    public function getAuditList(array $params): array
    {
        $list = XsUserFaceAudit::getListAndTotal($this->getAuditConditions($params), '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $uidArr = array_column($list['data'], 'uid');
        $userInfo = XsUserProfile::getUserProfileBatch($uidArr);
        foreach ($list['data'] as &$item) {
            $item['like_id'] = '';
            // 如果conflict_entity_id字段有值 _ 分割一下
            // 如果是数字的话 就显示相似人脸 根据conflict_entity_id搜索用户的人脸
            if ($item['conflict_entity_id']) {
                if (strpos($item['conflict_entity_id'], '_') !== false && (is_numeric(explode('_', $item['conflict_entity_id'])[0]))) {
                    $item['like_id'] = explode('_', $item['conflict_entity_id'])[0];
                }
            }
            $item['history_face'] = $this->getHistoryFace($item['previous_entity_id'])['face'] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['name'] = $userInfo[$item['uid']]['name'] ?? '';
            $item['score'] .= '%';
        }
        return $list;
    }

    private function getHistoryFace(string $prevEntityId): array
    {
        if (empty($prevEntityId)) {
            return [];
        }

        return XsUserFaceAudit::findOneByWhere([
            ['entity_id', '=', $prevEntityId],
            ['status', '=', XsUserFaceAudit::AUDIT_SUCCESS]
        ]);
    }

    private function getAuditConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['bigarea_id', '=', $params['bigarea_id']];
        }
        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ['uid', 'IN', Helper::formatIdString($params['uid'])];
        }
        if (isset($params['op']) && !empty($params['op'])) {
            $conditions[] = ['op', '=', $params['op']];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $conditions[] = ['status', '=', $params['status'] - 1];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<', strtotime($params['dateline_edate'])];
        }

        return $conditions;
    }
}