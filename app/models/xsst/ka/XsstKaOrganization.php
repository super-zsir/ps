<?php

namespace Imee\Models\Xsst\Ka;

use Imee\Models\Xsst\BaseModel;
use Imee\Schema\AdapterSchema;


class XsstKaOrganization extends BaseModel
{
    public function beforeCreate()
    {
        $this->create_time = time();
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
    }

    const KA_ID = 1;

    const IS_MASTER_YES = 1;
    const IS_MASTER_NO  = 0;

    const IS_MASTER_MAP = [
        self::IS_MASTER_YES => '是',
        self::IS_MASTER_NO  => '否',
    ];

    const DATA_PURVIEW0 = '0';
    const DATA_PURVIEW1 = '1';
    const DATA_PURVIEW2 = '2';

    const DATA_PURVIEW_MAP = [
        self::DATA_PURVIEW0 => '看本人',
        self::DATA_PURVIEW1 => '看本部门',
        self::DATA_PURVIEW2 => '看本人+下级部门',
    ];

    const STATE_YSE = '1';
    const STATE_NO  = '0';

    const STATE_MAP = [
        self::STATE_YSE => '启用',
        self::STATE_NO  => '禁用',
    ];

    private static $allList = [];

    public static function getAllList(): array
    {
        if (empty(self::$allList) || self::isResetCache()) {
            self::$allList = self::find()->toArray();
        }

        return self::$allList;
    }

    public static function findParentList(): array
    {
        return self::query()
            ->where('pid = :pid:', ['pid' => 0])
            ->orderBy('weight desc,id asc')
            ->execute()
            ->toArray();
    }

    public static function findListByPid($pid): array
    {
        return self::query()
            ->where('pid = :pid:', ['pid' => $pid])
            ->orderBy('weight desc,id asc')
            ->execute()
            ->toArray();
    }

    public static function updatePath($id): bool
    {
        $path = [];
        $info = self::findFirstById($id);
        self::updatePathRecursion($id, $path);

        $path = array_reverse($path);
        $path = implode(',', $path);

        if ($info->path != $path) {
            $info->path = $path;
            $info->save();
        }

        return true;
    }

    public static function updatePathRecursion($id, &$path = [])
    {
        $info = self::findFirstById($id);

        $path[] = $info->id;

        if ($info->pid == 0) {
            if (count($path) == 1) {
                $info->path = implode(',', $path);
                $info->save();
            }
            return true;
        } else {
            self::updatePathRecursion($info->pid, $path);
        }

        return $path;
    }

    public static function deleteByPath($id): array
    {
        $res = [];

        $list = self::query()
            ->where('FIND_IN_SET(:id:, path)', ['id' => $id])
            ->execute();

        if ($list->valid()) {
            $list->delete();

            $path = [];

            foreach ($list as $item)
            {
                $tmpPath = explode(',', $item->path);
                $path = array_merge($path, $tmpPath);
            }

            $res = array_values(array_unique($path));
        }

        return $res;
    }

    public static function getChildKfIdsByOrgId($orgId): array
    {
        $res = [];

        // 转化为kf_id查询
        $orgIds = self::findAllChildIdsById($orgId);

        if (!empty($orgIds)) {
            $kfList = XsstKaOrganizationUser::getKfListByOrgIds($orgIds);
            $res = array_keys($kfList);
        }

        return $res;
    }

    public static function getKfIdsByOrgId($orgId): array
    {
        $res = [];

        // 转化为kf_id查询
        $orgIds = self::findAllIdsById($orgId);

        if (!empty($orgIds)) {
            $kfList = XsstKaOrganizationUser::getKfListByOrgIds($orgIds);
            $res = array_keys($kfList);
        }

        return $res;
    }

    public static function findAllChildIdsById($id = 0): array
    {
        if (empty($id)) {
            return [];
        }

        $res = [];

        $list = self::query()
            ->where('FIND_IN_SET(:id:, path)', ['id' => $id])
            ->execute()
            ->toArray();

        if (!empty($list)) {

            $path = [];

            foreach ($list as $item)
            {
                $isOk = false;
                $tmpPath = explode(',', $item['path']);

                foreach ($tmpPath as $val)
                {
                    if ($val == $id) {
                        $isOk = true;
                    }

                    if (true === $isOk) {
                        $path[] = $val;
                    }
                }
            }

            $res = array_values(array_unique($path));
        }

        return $res;
    }

    public static function findAllIdsById($id = 0): array
    {
        if (empty($id)) {
            return [];
        }

        $res = [];

        $list = self::query()
            ->where('FIND_IN_SET(:id:, path)', ['id' => $id])
            ->execute()
            ->toArray();

        if (!empty($list)) {

            $path = [];

            foreach ($list as $item)
            {
                $tmpPath = explode(',', $item['path']);
                $path = array_merge($path, $tmpPath);
            }

            $res = array_values(array_unique($path));
        }

        return $res;
    }

    public static function getInfoById($id): array
    {
        $list = self::getAllList();

        if (empty($list)) {
            return [];
        }

        $list = array_column($list, null, 'id');

        return $list[$id] ?? [];
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

    public static function getAllChildDept($id)
    {
        $all = self::find()->toArray();
        if (empty($all)) {
            return [];
        }
        $path = '';
        foreach ($all as $item) {
            if ($item['id'] == $id) {
                $path = $item['path'];
                break;
            }
        }
        if (empty($path)) {
            return [];
        }

        $res = [];
        foreach ($all as $item) {
            if (strpos($item['path'], $path . ',') === 0) {
                $res[$item['id']] = $item['org_name'];
            }
        }

        return $res;
    }
}