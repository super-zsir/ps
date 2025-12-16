<?php

namespace Imee\Export\Operate;

use Dcat\EasyExcel\Excel;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Operate\Gift\GiftMapService;
use Imee\Service\Operate\Gift\GiftService;

class GiftExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'], $filterParams['lang']);

        $service = new GiftService();
        $mapService = new GiftMapService();

        $yesFields = ['is_lucky', 'is_named', 'is_combo', 'is_skin', 'is_customized', 'is_interact_gift', 'is_feed_gift', 'is_privilege', 'is_blind_box'];
        $enumMap = [
            'display'        => 'getDisplayMap',
            'sub_display'    => 'getSubDisplayMap',
            'type'           => 'getTypeMap',
            'gift_type'      => 'getGiftTypeMap',
            'deleted'        => 'getDeletedMap',
            'excludes'       => 'getExcludesMap',
            'tab_id'         => 'getTabMap',
            'privilege_type' => 'getPrivilegeTypeMap',
            'family_lv'      => 'getFamilyLevelMap',
            'super_gift_id'  => 'getGiftMap',
        ];

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $mapService, $yesFields, $enumMap) {
                // 每次获取1000条数据导入
                $pageSize = 1000;
                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = $service->getListAndTotal($filterParams, 'id desc', $page, $pageSize);
                foreach ($data['data'] ?? [] as $k => &$item) {
                    foreach ($item as $key => $value) {
                        if (in_array($key, $yesFields)) {
                            $data['data'][$k][$key] = $mapService->getYesNoMap($value);
                        } elseif (isset($enumMap[$key])) {
                            $data['data'][$k][$key] = $mapService->{$enumMap[$key]}($value);
                        }
                    }

                    $data['data'][$k]['gifts'] = $item['gifts_txt'];
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

        $header = ExportService::getListFields($guid, $language);

        $header = $header + [
                'id'                  => 'id',
                'name'                => 'name',
                'description'         => '描述',
                'jump_page'           => '跳转url',
                'name_en'             => 'name_en',
                'name_ar'             => 'name_ar',
                'name_ms'             => 'name_ms',
                'name_th'             => 'name_th',
                'name_id'             => 'name_id',
                'name_vi'             => 'name_vi',
                'name_ko'             => 'name_ko',
                'name_tr'             => 'name_tr',
                'name_ja'             => 'name_ja',
                'name_hi'             => 'name_hi',
                'name_bn'             => 'name_bn',
                'name_ur'             => 'name_ur',
                'name_tl'             => 'name_tl',
                'price'               => 'price',
                'display'             => 'display',
                'png_url'             => '礼物预览png',
                'webp_url'            => '礼物预览webp',
                'cover_start'         => '起始素材',
                'cover_end'           => '结束素材',
                'mp4_url'             => '礼物预览mp4',
                'ordering'            => 'ordering',
                'deleted'             => 'deleted',
                'gift_type'           => 'gift_type',
                'income_type'         => 'income_type',
                'excludes'            => 'excludes',
                'is_customized'       => '是否定制礼物',
                'customized_gift_uid' => '定制礼物拥有者',
                'tag_name'            => '标签名称',
                'vap_type'            => 'vap_type',
                'is_lucky'            => '是否幸运礼物',
                'tab_id'              => 'tab_id',
                'is_combo'            => '是否连击',
                'is_named'            => '是否冠名礼物',
                'is_skin'             => '是否礼物皮肤',
                'is_interact_gift'    => '是否麦位互动礼物',
                'is_feed_gift'        => '是否热门礼物',
            ];

        return $header;
    }
}