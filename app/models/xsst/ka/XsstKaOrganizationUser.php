<?php

namespace Imee\Models\Xsst\Ka;

use Imee\Models\Xsst\BaseModel;
use Imee\Schema\AdapterSchema;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class XsstKaOrganizationUser extends BaseModel
{
    use UserInfoTrait;

    private static $allList = [];

    public function beforeCreate()
    {
        $this->create_time = time();
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
    }

    public static function getInfoByKfId($kfId)
    {
        return static::findFirst([
            'conditions' => 'kf_id = :kf_id:',
            'bind' => ['kf_id' => $kfId]
        ]);
    }

    public static function getAllList(): array
    {
        if (empty(self::$allList) || self::isResetCache()) {
            self::$allList = self::find()->toArray();
        }

        return self::$allList;
    }

    public static function findAllKfMap(): array
    {
        $list = self::getAllList();

        if (empty($list)) {
            return [];
        }

        $kfIds = array_column($list, 'kf_id');
        $list = (new self)->getStaffBaseInfos($kfIds);

        return array_column($list, 'user_name', 'user_id');
    }

    public static function findAllKfAndOrgNameMap(): array
    {
        $list = self::getAllList();

        if (empty($list)) {
            return [];
        }

        $kfMap  = array_column($list, null, 'kf_id');
        $orgIds = array_values(array_filter(array_unique(array_column($list, 'org_id'))));
        $list = (new self)->getStaffBaseInfos(array_keys($kfMap));
        $list = array_column($list, 'user_name', 'user_id');
        $res  = [];

        foreach ($kfMap as $kfId => $item)
        {
            $orgName = '';
            if ($item['org_id'] > 0) {
                $orgInfo = XsstKaOrganization::getInfoById($item['org_id']);
                $orgName = $orgInfo['org_name'];
            }

            if (!isset($list[$kfId])) {
                continue;
            }

            $res[$kfId] = $list[$kfId];

            if (!empty($orgName)) {
                $res[$kfId] .= "({$orgName})";
            }
        }

        return $res;
    }

    public static function getOrgIdByKfId($kfId = 0): int
    {
        if (empty($kfId)) {
            return 0;
        }

        $list = self::getAllList();
        $list = array_column($list, null, 'kf_id');

        return isset($list[$kfId]) ? $list[$kfId]['org_id'] : 0;
    }

    public static function getKfListByOrgIds($ids = []): array
    {
        if (empty($ids)) {
            return [];
        }

        $list = self::query()
            ->where('org_id IN ({org_id:array})', ['org_id' => $ids])
            ->execute()
            ->toArray();

        return array_column($list, null, 'kf_id');
    }

    public static function getKfIdsByKfId($kfId): array
    {
        $info = self::findFirstByKfId($kfId);

        if (empty($info)) {
            return [$kfId];
        }

        if ($info->data_purview == XsstKaOrganization::DATA_PURVIEW0) {
            return [$kfId];
        }

        if ($info->data_purview == XsstKaOrganization::DATA_PURVIEW1) {
            $list = self::findByOrgId($info->org_id)->toArray();
            return !empty($list) ? array_column($list, 'kf_id') : [$kfId];
        }

        if ($info->data_purview == XsstKaOrganization::DATA_PURVIEW2) {
            $kfIds = XsstKaOrganization::getChildKfIdsByOrgId($info->org_id);
            return !empty($kfIds) ? $kfIds : [$kfId];
        }

        return [$kfId];
    }

    public static function getListByKfId($kfIdArr)
    {
        return self::find([
            'conditions' => 'kf_id IN ({kf_id:array})',
            'bind'       => ['kf_id' => $kfIdArr]
        ]);
    }

    public static function isMaster($kfId): bool
    {
        $info = self::findFirstByKfId($kfId);

        if (empty($info)) {
            return false;
        }

        return $info->is_master == XsstKaOrganization::IS_MASTER_YES;
    }

    public static function isResetCache(): bool
    {
        static $_token = null;

        $randomToken = AdapterSchema::getInstance(AdapterSchema::GUID_NOT_SET_DATA)->getRandomToken();

        if ($_token == $randomToken) {
            return false;
        }

        $_token = $randomToken;

        return true;
    }
}