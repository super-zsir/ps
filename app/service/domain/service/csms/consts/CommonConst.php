<?php

namespace Imee\Service\Domain\Service\Csms\Consts;

class CommonConst
{
    const SYSTEM_OP = 9999;
    const IMAGE_OP = 9995;

    // 审核项
    const audit_item = array(
        'text' => array(
            'nickname' => 'nickname', // 昵称签名
            'xs_chatroom' => 'xs_chatroom', // 房间名称公告
            'xs_fleet' => 'xs_fleet', //家族标题介绍
            'xs_welcome_text' => 'xs_welcome_text', // 迎新招呼
            'xs_group' => 'xs_group', // 群组名称
            'xs_live_config' => 'xs_live_config', //粉丝牌
            'xs_marry_message' => 'xs_marry_message', //婚礼留言板
            'xs_order_vote' => 'xs_order_vote', // 订单评论
            'xs_relation_defend' => 'xs_relation_defend', // 守护关系名
            'text' => 'text', // 朋友圈动态文本
        ),
        'image' => array(
            'tmp_icon' => 'tmp_icon', // 用户头像
            'god_tmp_icon' => 'god_tmp_icon', // 大神用户头像
            'xs_user_photos' => 'xs_user_photos', // 形象照
            'xs_wedding_album'=> 'xs_wedding_album', // 婚礼相册
            'xs_fleet_icon' => 'xs_fleet_icon', // 家族封面
            'xs_marry_relation' => 'xs_marry_relation', // 情侣小窝封面
            'image' => 'image', // 房间公屏图片
            'picture' => 'picture', // 朋友圈动态图片
        ),
        'audio' => array(
            'chongya_voice' => 'chongya_voice', // 声音审核
            'grabmic_song' => 'grabmic_song', // c位抢唱
            'audio' => 'audio', // 朋友圈动态音频
        ),
        'video' => array(
            'video_check' => 'video_check', //大神视频
            'video' => 'video', // 朋友圈动态视频
        )
    );
}