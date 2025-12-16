<?php

namespace Imee\Export\Operate\Chatroom;

use Dcat\EasyExcel\Excel;
use Imee\Models\Config\BbcChatroomModuleTag;
use Imee\Models\Config\BbcSettlementChannel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsChatroomModuleFactory;
use Imee\Service\Helper;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Operate\Chatroom\ChatroomService;

class ChatroomExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'],$filterParams['lang']);

        $service = new ChatroomService();
        $property = XsChatroom::$propertyAllMap;
        $sex = XsChatroom::$sexMap;
        $factory = XsChatroomModuleFactory::getOptions();
        $tag = BbcChatroomModuleTag::getOptions();
        $channel = BbcSettlementChannel::getOptions();
        $mode = XsChatroom::$modeMap;
        $nine = XsChatroom::$nineMap;
        $deleted = XsChatroom::$deletedMap;
        $language = Helper::getLanguageArr();
        $areas = XsChatroom::getAreaMap();
        $bigArea = XsBigarea::getAllNewBigArea();

        return Excel::export()
            ->chunk(function (int $page) use (
                $filterParams, $service, $property, $sex,
                $factory, $tag, $channel, $mode, $nine,
                $deleted, $language, $areas, $bigArea
            ) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = $service->getListAndTotal(['page' => $page, 'limit' => $pageSize] + $filterParams);
                foreach ($data['data'] as &$value) {
                    $value['property'] = $property[$value['property']] ?? '';
                    $value['sex'] = $sex[$value['sex']] ?? '';
                    $value['room_factory_type'] = $factory[$value['room_factory_type']] ?? '';
                    $value['fixed_tag_id'] = $tag[$value['fixed_tag_id']] ?? '';
                    $value['settlement_channel'] = $channel[$value['settlement_channel']] ?? '';
                    $value['mode'] = $mode[$value['mode']] ?? '';
                    $value['nine'] = $nine[$value['nine']] ?? '';
                    $value['deleted'] = $deleted[$value['deleted']] ?? '';
                    $value['language'] = $language[$value['language']] ?? '';
                    $value['area'] = $areas[$value['area']] ?? '';
                    $value['big_area'] = $bigArea[$value['big_area']] ?? '';
                }

                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
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
        return $header;
    }
}