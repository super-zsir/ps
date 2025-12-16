<?php

namespace Imee\Models\Xs;

class XsGift extends BaseModel
{
    protected static $primaryKey = 'id';

    protected $allowEmptyStringArr = ['excludes', 'tag_ids', 'description', 'jump_page',
        'is_app_public', 'icon', 'customized_gift_ts', 'delete_from', 'extend',
    ];

    const DELETE_YES = 1;
    const DELETE_NO = 0;

    public static $deletedMap = [
        self::DELETE_NO  => '上架',
        self::DELETE_YES => '下架',
    ];

    public static $displays = [
        'room'     => '房间(room)',
        'chat'     => '私聊(chat)',
        'order'    => '订单(order)',
        'mounts'   => '坐骑(mounts)',
        'decorate' => '主页装扮(decorate)',
        'video'    => '视频(video)',
    ];

    public static $subDisplays = [
        'all'          => 'all',
        'wolf'         => 'wolf',
        'guess'        => 'guess',
        'under'        => 'under',
        'juben'        => 'juben',
        'union'        => 'union',
        'finger_guess' => 'finger_guess',
        'theone'       => 'theone',
        'order'        => 'order',
        'music'        => 'music',
        'radio-defend' => 'radio-defend',
        'cp-woman'     => 'cp-woman',
        'cp-man'       => 'cp-man',
        'joy-woman'    => 'joy-woman',
        'joy-man'      => 'joy-man',
        'live'         => 'live',
        'expand'       => 'expand',
        'cplink'       => 'cplink',
        'concert'      => 'concert',
    ];

    public static $type = [
        'static'     => 'static',
        'normal'     => 'normal',
        'fullscreen' => 'fullscreen',
        'multiframe' => 'multiframe',
    ];

    //收入类型（收礼方）
    public static $incomeTypes = [
        'money'        => '星球币(money)',
        'coin'         => '金币(coin)',
        'union_gold'   => '联盟金币(union_gold)',
        'union_active' => '联盟活跃值(union_active)'
    ];

    public static $deleted = [
        '0' => '上架',
        '1' => '下架',
    ];

    public static $xtype = [
        ''           => '默认',
        'small'      => 'small',
        'big'        => 'big',
        'multiframe' => 'multiframe',
    ];

    public static $vapTypeMap = [
        ''           => '默认',
        'normal'     => 'normal',
        'fullscreen' => 'fullscreen',
    ];

    public static $tabIdMap = [
        1 => 'Event',
        2 => 'Hot',
        3 => 'National Flag',
        4 => 'Lucky Gift',
        5 => 'Custom',
        11 => '私密礼物',
        6 => 'Football',
        7 => 'Interactive gift',
        8 => 'Relation',
        9 => '特权',
        10 => '盲盒礼物',
    ];

    public static $excludes = [
        'zh_cn' => '简体中文',
        'zh_tw' => '繁体中文',
        'en'    => '英语',
        'ar'    => '阿语',
        'ms'    => '马来语',
        'th'    => '泰语',
        'id'    => '印尼语',
        'vi'    => '越南语',
        'ko'    => '韩语',
        'ja'    => '日语',
        'tr'    => '土耳其语',
        'hi'    => '印地语',
        'bn'    => '孟加拉语',
        'ur'    => '乌尔都语',
        'tl'    => '他加禄语',
        'mz'    => '马来华语',
    ];

    //显示方式
    public static $types = [
        'static'     => '静态(static)',
        'normal'     => '普通(normal)',
        'fullscreen' => '全屏(fullscreen)',
        'multiframe' => '多帧(multiframe)',
    ];

    //礼物大小
    public static $xtypes = [
        'small'      => '小帧(small)',
        'big'        => '大帧(big)',
        'multiframe' => '多帧(multiframe)',
    ];

    public static $tagUrlMap = [
        0 => '不需要',
        1 => '需要',
    ];

    //礼物类型
    public static $giftTypes = [
        'normal' => '普通(normal)',
        'coin'   => '金币(coin)',
    ];

    public static $diyTypeMap = [
        1 => '普通',
        2 => 'unity火箭',
    ];

    public static $relationGiftType = [
        1 => '关系奖励礼物',
        2 => '普通关系礼物',
    ];

    public static $relationType = [
        1 => 'cp',
        2 => '挚友',
    ];

    public static $relationLv = [
        3 => 'lv3',
        4 => 'lv4',
        5 => 'lv5',
        6 => 'lv6',
    ];

    public static $privilegeType = [
        1 => '家族特权礼物',
    ];

    public static $familyLevel = [
        6 => 'LV6',
        7 => 'LV7',
        8 => 'LV8',
    ];

    public static $unitySignMap = [
        1 => '横',
        2 => '竖',
    ];

    public static $yesNoMap = [
        '0' => '否',
        '1' => '是',
    ];

    public static $uploadTypeMap = [
        'list'         => 'static/gift_big/{gid}.png',
        'spng'         => 'static/gift_big/{gid}.s.png',
        'epng'         => 'static/gift_big/{gid}.e.png',
        'hpng'         => 'static/gift_big/{gid}.h.png',
        'zip'          => 'static/gift_big/{gid}.zip',
        'webp'         => 'static/gift_big/{gid}.webp',
        'json'         => 'static/gift_big/{gid}.json',
        'mp4'          => 'static/gift_big/{gid}.mp4',
        'diypreviewbg' => 'static/gift_big/{gid}_diy_preview_bg.mp4',//预览动画背景mp4
        'diypreview'   => 'static/gift_big/{gid}_diy_preview.mp4',//预览动画mp4
        'android'      => 'static/gift_big/android/{gid}',
        'ios'          => 'static/gift_big/ios/{gid}',
        'tag'          => 'static/gift_big/tag/{ymd}.png',
    ];

    public static $interactGiftPath = [
        'list' => 'static/gift_big/cover/{gid}',
        'spng' => 'static/gift_big/cover_start/{gid}',
        'epng' => 'static/gift_big/cover_end/{gid}',
        'webp'  => 'static/gift_big/video_one/{gid}.webp',
        'mp4'  => 'static/gift_big/video_two/{gid}.mp4',
    ];

    public static function findFirstValue(int $id)
    {
        return self::findFirst([
            'conditions' => 'id=:id:',
            'bind'       => ['id' => $id]
        ]);
    }

    /**
     * 获取幸运礼物
     * @return array
     */
    public static function getGiftIdNameList()
    {
        $data = self::getGeneratorListByWhere([
            ['is_lucky', '=', 1]
        ], 'id,name');
        $result = [];
        foreach ($data as $list) {
            foreach ($list as $val) {
                $result[] = [
                    'id'   => $val['id'],
                    'name' => $val['id'] . '-' . $val['name']
                ];
            }
        }
        return array_column($result, 'name', 'id');
    }

    /**
     * 获取有效礼物
     * @return array
     */
    public static function getOnlineList()
    {
        $list = self::getListByWhere([
            ['deleted', '=', self::DELETE_NO],
        ], 'id, name');

        return $list ? array_column($list, 'name', 'id') : [];
    }

    /**
     * 批量校验礼物是否存在
     * @param array $idArr
     * @return array
     */
    public static function existsGiftIds(array $idArr): array
    {
        $diffArray = [];
        if (empty($idArr)) {
            return $diffArray;
        }

        foreach (array_chunk($idArr, 200) as $chunk) {
            $giftList = XsGift::getListByWhere([['id', 'IN', $chunk]], 'id');
            $giftList = $giftList ? array_column($giftList, 'id') : [];
            $diffChunk = array_diff($chunk, $giftList);
            if ($diffChunk) {
                $diffArray = array_merge($diffArray, $diffChunk);
            }
        }

        return $diffArray;
    }

    /**
     * 判断是否幸运礼物
     * @param int $id
     * @return array
     */
    public static function hasLuckyGift(int $id): array
    {
        return self::findOneByWhere([
            ['id', '=', $id],
            ['is_lucky', '=', 1]
        ]);
    }

    /**
     * 获取定制礼物map
     * @return array
     */
    public static function getCustomGiftMap(): array
    {
        $list = self::getListByWhere([
            ['is_customized', '=', 1],
            ['deleted', '=', self::DELETE_NO],
        ], 'id, name', 'id desc');

        $map = [];

        foreach ($list as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }

        return $map;
    }

    public static function getLuckyGiftByIdBatch(array $ids) : array
    {
        $res = self::getListByWhere([
            ['id', 'in', $ids],
            ['is_lucky', '=', 1]
        ],'id');

        return array_column($res,'id','id');
    }

    public static function getListByIds(array $ids): array
    {
        $list = self::getListByWhere([
            ['id', 'in', $ids],
        ], 'id');

        if ($list) {
            $list = array_column($list, 'id');
        }

        return $list;
    }
}