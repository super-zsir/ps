<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsVipRecord;

class ViprecordService
{
    public function getArea()
    {
        $format = [];
        $areaMap = XsBigarea::getAllNewBigArea();
        foreach ($areaMap as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getRecordType()
    {
        $format = [];
        foreach (XsVipRecord::$displayRecordType as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getList($params)
    {
        $conditon = [];
        if (isset($params['uid'])) {
            $uids = explode(',', $params['uid']);
            $filterUids = array_filter($uids, function ($v) {
                return is_numeric($v);
            });
            
            if (count(($uids)) != count($filterUids)) {
                throw new ApiException(ApiException::MSG_ERROR, "请检测uid字段所传的格式");
            }
            $conditon[] = ['uid', 'in', $uids];
        }

        if (isset($params['user_big_area_id']) && is_numeric($params['user_big_area_id'])) {
            $conditon[] = ['user_big_area_id', '=', $params['user_big_area_id']];
        }

        if (isset($params['vip_level']) && is_numeric($params['vip_level'])) {
            $conditon[] = ['vip_level', '=', $params['vip_level']];
        }

        if (isset($params['record_type']) && is_numeric($params['record_type'])) {
            $conditon[] = ['record_type', '=', $params['record_type']];
        }

        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditon[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }

        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditon[] = ['dateline', '<', strtotime($params['dateline_edate']) + 86400];
        }

        $result = XsVipRecord::getListAndTotal($conditon, '*', 'id desc', $params['page'], $params['limit']);
        if ($result['total'] == 0 || !$result['data']) {
            return $result;
        }
        $uids = [];
        foreach ($result['data'] as $v) {
            $uids[] = $v['uid'];
        }
        $areaMap = XsBigarea::getAllNewBigArea();
        $userMap = XsUserProfile::getUserProfileBatch($uids);
        foreach ($result['data'] as &$v) {
            $v['user_name'] = isset($userMap[$v['uid']]) ? $userMap[$v['uid']]['name'] : '';
            $v['area_name'] = isset($areaMap[$v['user_big_area_id']]) ? $areaMap[$v['user_big_area_id']] : '';
            $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
            $v['before_expire_dateline'] = $v['before_expire_dateline'] > 0 ?
                date('Y-m-d H:i:s', $v['before_expire_dateline']) : '';
            $v['after_expire_dateline'] = $v['after_expire_dateline'] > 0 ?
                date('Y-m-d H:i:s', $v['after_expire_dateline']) : '';
            $v['display_record_type'] = isset(XsVipRecord::$displayRecordType[$v['record_type']]) ?
                XsVipRecord::$displayRecordType[$v['record_type']] : '';
        }
        return $result;
    }
}
