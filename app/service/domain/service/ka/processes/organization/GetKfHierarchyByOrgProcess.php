<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;

use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class GetKfHierarchyByOrgProcess
{
    use UserInfoTrait;

    /**
     * @var XsstKaOrganization
     */
    private $orgModel = XsstKaOrganization::class;

    /**
     * @var XsstKaOrganizationUser
     */
    private $orgUserModel = XsstKaOrganizationUser::class;

    protected $orgList;
    protected $kfList;

    public function __construct()
    {

    }

    public function handle(): array
    {
        $res = [];

        $this->orgList = $this->orgModel::getAllList();
        if (empty($this->orgList)) {
            return $res;
        }

        foreach ($this->orgList as &$item)
        {
            $item['path'] = explode(',', $item['path']);
        }

        $this->orgList = array_column($this->orgList, null, 'id');

        // 查询组织架构里所有客服
        $orgUserList = $this->orgUserModel::getAllList();
        if (empty($orgUserList)) {
            return $res;
        }

        // 符合条件的所有客服
        $this->kfList = [];

        // 组合组织和客服的关系
        foreach ($orgUserList as $orgUser)
        {
            if ($orgUser['org_id'] == 0 || !isset($this->orgList[$orgUser['org_id']])) {
                continue;
            }

            if (!isset($orgUser['kf_id']) || $orgUser['kf_id'] == 0) {
                continue;
            }

            // 禁用账号
            if ($orgUser['state'] == $this->orgModel::STATE_NO) {
                continue;
            }

            $this->kfList[$orgUser['kf_id']] = $orgUser;
            $this->kfList[$orgUser['kf_id']]['kf_id_list'][$orgUser['kf_id']] = $orgUser['kf_id'];
        }

        $this->getOrgKfId();

        // 组装数据
        foreach ($this->orgList as $tmp) {
            $res[] = [
                'id'    => $tmp['id'],
                'name'  => $tmp['org_name'],
                'type'  => 'org',
                'kf_id' => $tmp['kf_id_list'] ?? [],
            ];
        }

        $kfUserList = $this->getStaffBaseInfos(array_keys($this->kfList));

        foreach ($this->kfList as $tmp) {
            $res[] = [
                'id'    => $tmp['kf_id'],
                'name'  => isset($kfUserList[$tmp['kf_id']]) ? $kfUserList[$tmp['kf_id']]['user_name'] : '',
                'type'  => 'kf',
                'kf_id' => $tmp['kf_id_list'] ?? [],
            ];
        }

        return $res;
    }

    private function getOrgKfId(): void
    {
        // 查询客服数据权限，关联的客服关系
        foreach ($this->kfList as &$kfInfo)
        {
            $orgId = $kfInfo['org_id'] ?? 0;
            $kfId  = $kfInfo['kf_id'] ?? 0;
            if ($orgId == 0) {
                continue;
            }

            $this->orgList[$orgId]['kf_id_list'][$kfId] = $kfId;

            // 判断用户数据权限
            $kfMap = $this->getDataPurviewKfList($kfInfo['kf_id']);
            $kfInfo['kf_id_list'] += $kfMap;
        }

        // 根据客服的关系对应到组织架构上
        foreach ($this->orgList as &$orgInfo)
        {
            $tmpKfIdList = $this->getOrgKfIdListById($orgInfo['id']);
            $orgInfo['kf_id_list'] = $tmpKfIdList;
        }
    }

    private function getDataPurviewKfList($kfId): array
    {
        return [$kfId => $kfId];
//        $kfInfo = $this->kfList[$kfId];
//
//        // 看本人
//        if ($kfInfo['data_purview'] == $this->orgModel::DATA_PURVIEW0) {
//            return [$kfId => $kfId];
//        }
//
//        // 看本部门
//        if ($kfInfo['data_purview'] == $this->orgModel::DATA_PURVIEW1) {
//            return $this->getOrgKfIdById($kfInfo['org_id']);
//        }
//
//        // 看本部门 + 下级部门
//        if ($kfInfo['data_purview'] == $this->orgModel::DATA_PURVIEW2) {
//            return $this->getOrgKfIdRecursionById($kfInfo['org_id']);
//        }
    }

    private function getOrgKfIdById($orgId): array
    {
        $kfIdMap = [];

        foreach ($this->kfList as $item)
        {
            if ($item['org_id'] == $orgId) {
                $kfIdMap[$item['kf_id']] = $item['kf_id'];
            }
        }

        return $kfIdMap;
    }

    private function getOrgKfIdRecursionById($orgId): array
    {
        $kfIdMap = [];

        // 查询下级部门
        $pathArr = [];
        foreach ($this->orgList as $orgInfo)
        {
            if (in_array($orgId, $orgInfo['path'])) {
                $pathArr[count($orgInfo['path'])] = $orgInfo['path'];
            }
        }

        krsort($pathArr);
        $maxPath = current($pathArr);

        $index = array_search($orgId, $maxPath);
        $realPath = array_slice($maxPath, $index);

        // 获取符合所有的客服id
        foreach ($this->kfList as $item)
        {
            if (in_array($item['org_id'], $realPath)) {
                $kfIdMap[$item['kf_id']] = $item['kf_id'];
            }
        }

        return $kfIdMap;
    }

    private function getOrgKfIdListById($orgId): array
    {
        $allIds = $this->orgModel::findAllIdsById($orgId);

        $kfIdMap = [];

        foreach ($this->kfList as $item)
        {
            if (in_array($item['org_id'], $allIds)) {
                $kfIdMap += $item['kf_id_list'];
            }
        }

        return $kfIdMap;
    }
}