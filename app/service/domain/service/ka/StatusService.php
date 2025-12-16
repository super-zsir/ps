<?php

namespace Imee\Service\Domain\Service\Ka;

use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\StatusService as BaseStatusService;
use Imee\Comp\Operate\Auth\Service\StaffService;
use Imee\Models\Xsst\Ka\BmsKaUserList;
use Imee\Models\Xs\XsBigarea;

class StatusService extends BaseStatusService
{
    /**
     * KA用户-获取组织架构
     * @param  null    $value
     * @param  string  $format
     */
    public static function getKaOrgList($value = null, $format = '')
    {
        $map = ['0' => '未分配'];

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-获取后台用户列表
     * @param  null    $value
     * @param  string  $format
     */
    public static function getAdminList($value = null, $format = '')
    {
        $map      = (new StaffService())->getAllStaff();
        $map      = array_column($map, 'user_name', 'user_id');
        $map['0'] = '系统';
        if (!is_null($value) && is_numeric($value)) {
            return isset($map[$value]) ? $map[$value] : '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-获取后台用户列表
     * @param  null    $value
     * @param  string  $format
     */
    public static function getOrgUserAdminList($value = null, $format = '')
    {
        $list = (new StaffService())->getAllStaff();
        $map  = [];

        foreach ($list as $item) {
            $map[$item['user_id']] = $item['user_name'] . "({$item['user_email']})";
        }

        if (!is_null($value) && is_numeric($value)) {
            return isset($map[$value]) ? $map[$value] : '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-获取后台部门列表
     * @param  null    $value
     * @param  string  $format
     */
    public static function getOrgList($value = null, $format = '')
    {
        $map = XsstKaOrganization::getAllList();

        if (empty($map)) {
            return [];
        }

        $map = ['0' => '未分配'] + array_column($map, 'org_name', 'id');

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-组织架构-获取后台用户列表
     * @param  null    $value
     * @param  string  $format
     */
    public static function getOrgUserList($value = null, $format = '')
    {
        $map = ['0' => '未分配'] + XsstKaOrganizationUser::findAllKfMap();

        if (empty($map)) {
            return [];
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-组织架构-获取后台用户列表(带部门名称)
     * @param  null    $value
     * @param  string  $format
     */
    public static function getOrgUserAndOrgNameList($value = null, $format = '')
    {
        $map = ['0' => '未分配'] + XsstKaOrganizationUser::findAllKfAndOrgNameMap();

        if (empty($map)) {
            return [];
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-获取建联方式
     * @param  null    $value
     * @param  string  $format
     */
    public static function getKaBuildStatusType($value = null, $format = '')
    {
        $map = BmsKaUserList::KA_BUILD_TYPE_MAP;

        if (is_numeric($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * KA用户-获取建联状态
     * @param  null    $value
     * @param  string  $format
     */
    public static function getKaBuildStatus($value = null, $format = '')
    {
        $map = BmsKaUserList::BUILD_AL_STATUS_MAP;

        if (!is_null($value) && is_numeric($value)) {
            return isset($map[$value]) ? $map[$value] : '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * 获取大区
     */
    public function getAreaMap($value = null, $format = '')
    {
        $map = XsBigarea::getBigAreaList();
        unset($map['all']);

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }

    /**
     * 获取ka等级
     */
    public function getKaTag($value = null, $format = '')
    {
        $map = BmsKaUserList::KA_TAG;
        unset($map['all']);

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = self::formatMap($map, $format);
        }

        return $map;
    }
}
