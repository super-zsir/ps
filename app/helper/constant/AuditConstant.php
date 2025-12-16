<?php


namespace Imee\Helper\Constant;

class AuditConstant
{

    // cms.system_id
    const SYSTEM_ID = 1;

    const STATUS_NORMAL = 1;
    const STATUS_DELETE = 2;

    // 存放所有审核模块和选项
    const MODULE_CHOICE_KEY = 'newkefu:modulechoice';

    const MAX_TASK_NUMBER = 100;






    const TASK_DEFAULT_NUMBER = 100;


    // =====================redis=========================
    const REDIS_STAFF_TASK_PRE = 'newkefu:staff_task:';
    const REDIS_MODULE_USER = 'newkefu:module_user:';
    const REDIS_TASK_TTL = 600;
    const REDIS_TASK_LOCK_PRE = 'newkefu:new_task_lock:';
    const REDIS_TASK_LOCK_TTL = 5;

    // 阿里头像缓存
    const REDIS_ALI_FACE_BODY = 'ali_face_body:';
    const REDIS_CHECKED_CACHE = 'newkefu:checked_cache';




    // ====================beanstalk=======================
    const BEANSTALK_ALI_FACE = 'xs.ali_face_body';



    const NEW_TASK_FIELD = 'choice';



    // 审核模块
    const TEXT = 'text';
    const TEXT_RECHECK = 'rechecktext';
    const TEXT_INSPECT = 'inspecttext';
    const MACHINE = 'machine';
    const MACHINE_RECHECK = 'recheckmachine';
    const MACHINE_INSPECT = 'inspectmachine';
    const CIRCLE_VERIFY = 'circleverify';
    const CIRCLE_SECOND = 'circlesecond';               // 朋友圈复审
    const COMMENT_CIRCLE = 'commentcircle';
    const CIRCLE_REPORT = 'circlereport';
    const VOICEVERIFY = 'voiceverify';
    const SCREEN_IMAGE = 'screenimage';
    const USER_GOD = 'usergod';
    const GOD_VIDEO = 'godvideo';
    const WHITEWALL = 'whitewall';                      // 表白墙
    const FANSCARD = 'fanscard';                        // 粉丝牌
    const WEDDINGALBUM = 'weddingalbum';                // 婚礼相册
    const GRABMICSONG = 'grabmicsong';                  // C位抢唱
    const GODTAG = 'godtag';                            // 标签审核
    const ABILITYAUDIT = 'abilityaudit';                // 能力类型
    const USERABILITY = 'userability';                  // 用户能力
    const GAMECARD = 'gamecard';                        // 冲鸭游戏卡
    const INTIMATE = 'intimate';                        // 亲密互动图片
    const ROOMVALID = 'roomvalid';                      // 派对房审核

	// 以下并未迁移
	const CIRCLECOMMENT = 'commentcircle';              // 朋友圈评论
	const CIRCLECOMMENTSLICE = 'circlecommentslice';    // 朋友圈评论拼接
	const VOICEINSPECT = 'voiceinspect';                // 声音核查



    // 审核项
    const VOICE_CHOICE_CHONGYA = 'chongya_voice';
    const SCREEN_IMAGE_CHOICE = 'screen_image';
    const USER_GOD_VERIFY = 'god_verify';
    const GOD_VIDEO_CHOICE = 'god_video';

    const WHITEWALL_CHOICE = 'whitewall';               // 表白墙
    const GODTAG_CHOICE = 'god_tag';                    // 标签审核项
	const FRIEND_CARD = 'friend_card';


    // 审核来源
    const AUDIT_SOURCE_OLD = 'old';
    const AUDIT_SOURCE_NEW = 'new';
    // 用户昵称/签名	用户头像	房间名字/公告	家族标题/介绍	家族封面	群组名字	订单评论	用户形象照	迎新招呼	婚礼相册	声音	大神视频	标签	房间公屏图片	大神技能	C 位抢唱	粉丝牌审核	朋友圈-音频	朋友圈-图片	朋友圈-文本	朋友圈-视频	动态举报	动态评论	联盟审核
    const ALL_AUDIT = array(
        'nickname' => 'nickname',
        'tmp_icon' => 'tmp_icon',
        'xs_chatroom' => 'xs_chatroom',
        'xs_fleet' => 'xs_fleet',
        'xs_fleet_icon' => 'xs_fleet_icon',
        'xs_group' => 'xs_group',
        'xs_order_vote' => 'xs_order_vote',
        'xs_user_photos' => 'xs_user_photos',
        'xs_welcome_text' => 'xs_welcome_text',
        'xs_wedding_album'=> 'xs_wedding_album',
        'chongya_voice' => 'chongya_voice',
        'video_check' => 'video_check',
        'user_tag' => 'user_tag',
        'image' => 'image',
        'god_verify' => 'god_verify',
        'grabmic_song' => 'grabmic_song',
        'xs_live_config' => 'xs_live_config',
        'audio' => 'audio',
        'picture' => 'picture',
        'text' => 'text',
        'video' => 'video',
        'circle_report' => 'circle_report',
        'circle_comment' => 'circle_comment',
        'bbu_union' => 'bbu_union',
    );

    const REPORT_STATE = [
        '' => '全部状态',
        1 => '待处理',
        2 => '处理中',
        3 => '已处理',
        4 => '已驳回',
    ];

    const REPORT_TYPE = [
        '' => '全部类型',
        1 => '诈骗',
        2 => '政治',
        3 => '侵权举报',
        4 => '侮辱诋毁',
        5 => '色情',
        6 => '广告',
        7 => '现金单',
        8 => '游戏捣乱',
    ];
}
