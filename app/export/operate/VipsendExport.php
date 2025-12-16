<?php

namespace Imee\Export\Operate;

use Dcat\EasyExcel\Excel;
use Imee\Models\Xsst\BmsVipSend;
use Imee\Models\Xsst\BmsVipSendDetail;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;

class VipsendExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'],$filterParams['lang']);

        $condition = [];
        $condition[] = ['state', '!=', BmsVipSend::STATE_UNVALID];
        if (isset($filterParams['id']) && is_numeric($filterParams['id'])) {
            $condition[] = ['id', '=', $filterParams['id']];
        }

        if (isset($filterParams['state']) && is_numeric($filterParams['state'])) {
            $condition[] = ['state', '=', $filterParams['state']];
        }

        if (isset($filterParams['op_uid']) && is_numeric($filterParams['op_uid'])) {
            $condition[] = ['op_uid', '=', $filterParams['op_uid']];
        }

        if (isset($filterParams['dateline_sdate']) && !empty($filterParams['dateline_sdate'])) {
            $condition[] = ['dateline', '>=', strtotime($filterParams['dateline_sdate'])];
        }

        if (isset($filterParams['dateline_edate']) && !empty($filterParams['dateline_edate'])) {
            $condition[] = ['dateline', '<', strtotime($filterParams['dateline_edate']) + 86400];
        }

        $sends = BmsVipSend::getListByWhere($condition, 'id,op_uid');
        $admins = [];
        if ($sends) {
            $admins = CmsUser::getAdminUserBatch(array_column($sends, 'op_uid'));
        }

        $sends = array_column($sends, null, 'id');

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $sends, $admins) {
                // 每次获取1000条数据导入
                $pageSize = 1000;
                // 只查询前10页数据
                if (!$sends || $page > 10) {
                    return [];
                }

                $condition = [];
                $condition[] = ['send_id', 'in', array_column($sends, 'id')];
                if (!empty($filterParams['uid']) && is_numeric($filterParams['uid'])) {
                    $condition[] = ['uid', '=', $filterParams['uid']];
                }
                if (!empty($filterParams['vip_level']) && is_numeric($filterParams['vip_level'])) {
                    $condition[] = ['vip_level', '=', $filterParams['vip_level']];
                }

                $data = BmsVipSendDetail::getListAndTotal($condition, '*', 'send_id desc,id asc', $page, $pageSize);

                foreach ($data['data'] as &$item) {

                    $sendId = $item['send_id'];
                    $adminId = $sends[$sendId]['op_uid'] ?? 0;
                    $item['op_uid'] = $adminId . ' - ' . $admins[$adminId]['user_name'] ?? '';

                    $item['type'] = BmsVipSendDetail::$giveTypeMaps[$item['type']] ?? $item['type'];
                    $item['vip_level'] = BmsVipSendDetail::$displayVipLevel[$item['vip_level']] ?? $item['vip_level'];

                    $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
                }

                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'] ?? [];
            })
            ->headings($headings)
            ->store($filePathName);
    }

    /**
     * 获取表头
     */
    private static function getHeader($guid = '', $language = 'zh_cn'): array
    {
        static $header;
        if ($header) {
            return $header;
        }

        $header = [
            'send_id'        => '发放任务ID',
            'uid'       => 'UID',
            'vip_level' => 'VIP等级',
            'vip_day'   => 'VIP天数',
            'send_num'  => '发放数量',
            'type'      => '类型',
            'remark'    => '备注',
            'dateline'  => '发放时间',
            'op_uid'    => '创建人',
        ];

        return $header;
    }
}