<?php

namespace Imee\Models\Rpc;

use GuzzleHttp\Psr7\Response;
use Imee\Comp\Common\Rpc\BaseRpc;

/**
 * 使用说明：
 * $obj = new PsRpc();
 * 1.url传参
 * $obj->call(
 * PsRpc::API_PRICE_LEVEL, ['query' => []]
 * );
 * 2.json传参
 * $obj->call(
 * PsRpc::API_PRICE_LEVEL, ['json' => []]
 * );
 * 3.post x-www-form-urlencoded
 * $obj->call(
 * PsRpc::API_PRETTY_LIST, ['form_params' => []]
 * );
 * 4.post form-data
 * $obj->call(
 * PsRpc::API_PRETTY_LIST, ['multipart' => [
 * [
 * 'name'     => 'file',
 * 'contents' => $fileContent,
 * 'filename' => 'file_name.txt'
 * ],
 * [
 * 'name'     => 'test_name',
 * 'contents' => 'test_value'
 * ],
 * ]
 * );
 */

/**
 * Partystar服务端接口配置
 */
class PsRpc extends BaseRpc
{
    const API_UPDATE_GIFT_STATUS = 'update_gift_status'; //  礼物管理-修改礼物上下架状态
    const API_PRICE_LEVEL = 'price_level';
    const API_UPDATE_PRICE_LEVEL = 'price_level_update';
    const API_ROOM_TOP = 'room_top';
    const API_ROOM_BOTTOM_CONFIG = 'room_bottom_config';

    const API_ROOM_TOP_MODIFY = 'room_top_modify';
    const API_ROOM_TOP_CANCEL = 'room_top_cancel';
    const API_GAME_COIN_MODIFY = 'game_coin_modify';
    const API_QUERY_VIDEO_INFO = 'query_video_info';                    // 查询视频信息
    const API_SIC_BO_SWITCH = 'sic_do_switch';     // 设置sicbo大区开关
    const API_SIC_BO_CONFIG = 'sic_do_config';               // 设置sicbo配置
    const API_SLOT_SWITCH = 'set_slot_switch';    //  设置slot玩法大区开关
    const API_LUCKY_GIFT_SWITCH = 'lucky_gift_switch';
    const API_LUCKY_GIFT_DIVIDED_ADD = 'lucky_gift_divided_add';
    const API_LUCKY_GIFT_DIVIDED_EDIT = 'lucky_gift_divided_edit';
    const API_LUCKY_GIFT_DIVIDED_DEL = 'lucky_gift_divided_del';
    const API_LUCKY_GIFT_RATE_ADJUST_ADD = 'lucky_gift_rate_adjust_add';
    const API_LUCKY_GIFT_RATE_ADJUST_EDIT = 'lucky_gift_rate_adjust_edit';
    const API_LUCKY_GIFT_RATE_ADJUST_DEL = 'lucky_gift_rate_adjust_del';
    const API_LUCKY_GIFT_RATE_ADD = 'lucky_gift_rate_add';
    const API_LUCKY_GIFT_RATE_EDIT = 'lucky_gift_rate_edit';
    const API_LUCKY_GIFT_RATE_DEL = 'lucky_gift_rate_del';
    const API_USER_MEDAL_LESS_TIME = 'user_medal_less_time';
    const API_USER_MEDAL_UPDATE_CONFIG = 'user_medal_update_config';

    const API_RED_PACKET_CONFIG = 'red_packet_config';
    const API_RED_PACKET_CONFIG_INFO = 'red_packet_config_info';
    const API_LUCKY_WHEEL_MODIFY = 'lucky_wheel_modify';

    const API_LUCKY_LIMIT_CONFIG_LIST = 'lucky_limit_config_list';
    const API_LUCKY_LIMIT_CONFIG_ADD = 'lucky_limit_config_add';
    const API_LUCKY_LIMIT_CONFIG_MODIFY = 'lucky_limit_config_modify';
    const API_LUCKY_LIMIT_CONFIG_DEL = 'lucky_limit_config_del';

    // 房间pk
    const API_GET_TEAM_PK_RECORD = 'getTeamPkRecord';
    const API_GET_TEAM_PK_DIAMOND_RECORD = 'getTeamPkDiamondRecord';

    // 玩法预期限制
    const API_PROBABILITY_GAME_SWITCH_LIST = 'getProbabilityGameSwitch';
    const API_PROBABILITY_GAME_SWITCH_MODIFY = 'editProbabilityGameSwitch';

    // 背景管理
    const API_CHATROOM_MATERIAL_LIST = 'chatroom_material_list';
    const API_CHATROOM_MATERIAL_ADD = 'chatroom_material_add';
    const API_CHATROOM_MATERIAL_EDIT = 'chatroom_material_edit';
    const API_CHATROOM_MATERIAL_DELETE = 'chatroom_material_delete';

    // 背景商城
    const API_CHATROOM_BACKGROUND_LIST = 'chatroom_background_list';
    const API_CHATROOM_BACKGROUND_ADD = 'chatroom_background_add';
    const API_CHATROOM_BACKGROUND_EDIT = 'chatroom_background_edit';
    const API_CHATROOM_BACKGROUND_DELETE = 'chatroom_background_delete';
    const API_CHATROOM_BACKGROUND_DROP = 'chatroom_background_drop';
    const API_CHATROOM_BACKGROUND_M_DROP = 'chatroom_background_m_drop';

    // 背景背包
    const API_USER_CHATROOM_BACKGROUND_LIST = 'user_chatroom_background_list';
    const API_USER_CHATROOM_BACKGROUND_EDIT = 'user_chatroom_background_edit';

    // 背景发放
    const API_DROP_ROOM_BACKGROUND = 'room_background_drop';
    const API_M_DROP_ROOM_BACKGROUND = 'm_room_background_drop';

    // 发放认证标识
    const API_GIVE_CERTIFICATION_SIGN = 'give_certification_sign';
    const API_UPDATE_CERTIFICATION_SIGN = 'update_certification_sign';

    // 快捷礼物修改
    const API_QUICK_GIFT_UPDATE = 'quick_gift_update';

    // teen patti 玩法配置
    const API_TEEN_PATTI_SWITCH = 'teen_patti_switch';

    // Greedy玩法配置
    const API_GREEDY_SWITCH = 'set_greedy_switch';
    const API_GREEDY_CONFIG = 'set_greedy_config';
    const API_GREEDY_CONFIG_BIG_AREA_BOX = 'set_greedy_config_big_area';
    const API_GREEDY_BOX_SWITCH = 'set_greedy_box_switch';
    const API_GREEDY_ENGINE= 'set_greedy_engine';
    const API_GREEDY_GLOBAL_RANK_SWITCH = 'set_greedy_global_rank_switch';


    // 登陆设备白名单
    const API_LOGIN_REGISTER_WHITE_LIST_ADD = 'login_register_whitelist_add';


    //靓号管理
    const API_PRETTY_USER_CUSTOMIZE_CREATE = 'pretty_user_customize_create';
    const API_PRETTY_USER_CUSTOMIZE_MODIFY = 'pretty_user_customize_modify';
    const API_PRETTY_COMMODITY_CREATE = 'pretty_user_commodity_create';
    const API_PRETTY_COMMODITY_MODIFY = 'pretty_user_commodity_modify';
    const API_PRETTY_COMMODITY_SHELF = 'pretty_user_commodity_shelf';
    const API_GIVE_PRETTY_UID_CREATE = 'pretty_give_uid_create';
    const API_GIVE_PRETTY_UID_MODIFY = 'pretty_give_uid_modify';

    // 爆火箭玩法
    const API_ROOM_ROCKET_SWITCH = 'room_rocket_switch';
    const API_ROOM_ROCKET_CONFIG_LIST = 'room_rocket_config_list';
    const API_ROOM_ROCKET_CONFIG_INFO = 'room_rocket_config_info';
    const API_ROOM_ROCKET_CONFIG_EDIT = 'room_rocket_config_edit';
    const API_ROOM_ROCKET_AWARD_CONFIG_EDIT = 'room_rocket_award_config_edit';
    const API_ROOM_ROCKET_AWARD_CONFIG = 'room_rocket_award_config';

    // 首页弹窗
    const API_POPUPS_CONFIG_ADD = 'add_popups_config';

    // 支付密码管理
    const API_RESET_PAY_PASS = 'reset_pay_pass';
    const API_RESET_PAY_PASS_NUM = 'reset_pay_pass_num';
    const API_RESET_EMAIL = 'reset_email';
    const API_MODIFY_EMAIL = 'modify_email';

    // 自定义房间背景卡片管理
    const API_CUSTOM_ROOM_BACKGROUND_CARD_SWITCH = 'custom_room_background_card_switch';
    const API_CUSTOM_ROOM_BACKGROUND_CARD_SWITCH_EDIT = 'custom_room_background_card_switch_edit';
    const API_CUSTOM_ROOM_BACKGROUND_CARD_LOG = 'custom_room_background_card_log';
    const API_CUSTOM_ROOM_BACKGROUND_CARD_LOG_ADD = 'custom_room_background_card_log_add';
    const API_CUSTOM_ROOM_BACKGROUND_CARD_PACK = 'custom_room_background_card_log_pack';
    const API_CUSTOM_ROOM_BACKGROUND_CARD_PACK_DEL = 'custom_room_background_card_log_pack_del';

    // 超管
    const ADD_SUPER_ADMIN = 'add_super_admin';
    const ADD_PATROL_ACCOUNT = 'add_patrol_account';

    // 预期等级大区配置
    const API_PROBABILITY_GAME_BIG_AREA_CONFIG_LIST = 'probability_game_big_area_config_list';
    const API_PROBABILITY_GAME_BIG_AREA_CONFIG_EDIT = 'probability_game_big_area_config_edit';

    //迎新礼包配置
    const API_WELCOME_GIFTBAG_CREATE = 'welcome_giftbag_create';
    const API_WELCOME_GIFTBAG_MODIFY = 'welcome_giftbag_modify';
    const API_WELCOME_BIGAREA_MODIFY = 'welcome_bigarea_modify';
    const API_AGENCY_HUNTER_GIFT_BAG_STATUS_MODIFY = 'agency_hunter_gift_bag_status_modify';

    const API_WELCOME_GIFTHUNTER_CREATE = 'welcome_gifthunter_create';
    const API_WELCOME_GIFTHUNTER_CREATE_BATCH = 'welcome_gifthunter_create_batch';
    const API_WELCOME_GIFTHUNTER_UPDATE_BATCH = 'welcome_gifthunter_update_batch';
    const API_WELCOME_GIFTHUNTER_CREATE_BATCH_CONDITION = 'welcome_gifthunter_create_batch_condition';
    const API_WELCOME_GIFTHUNTER_MODIFY = 'welcome_gifthunter_modify';


    //vip发送
    const API_VIP_SEND_BATCH_ADD = 'vip_send_batch_add';
    const API_VIP_SEND_CHECK = 'vip_send_check';


    //游戏优惠券管理
    const GAME_COUPON_CONFIG_LIST = 'game_coupon_config_list';
    const GAME_COUPON_CONFIG_ALL_LIST = 'game_coupon_config_all_list';
    const GAME_COUPON_CONFIG_CREATE = 'game_coupon_config_create';
    const GAME_COUPON_CONFIG_MODIFY = 'game_coupon_config_modify';
    const GAME_COUPON_CONFIG_AMOUNT = 'game_coupon_config_amount';
    const GAME_COUPON_CONFIG_SCENE = 'game_coupon_config_scene';
    const GAME_COUPON_ISSUED_OP = 'game_coupon_issued_op';

    // 贴纸素材管理
    const API_STICKER_RESOURCE_LIST = 'sticker_resource_list';
    const API_STICKER_RESOURCE_ADD  = 'sticker_resource_add';
    const API_STICKER_RESOURCE_EDIT = 'sticker_resource_edit';
    const API_STICKER_MANAGE_LIST   = 'sticker_manage_list';
    const API_STICKER_MANAGE_ADD    = 'sticker_manage_add';
    const API_STICKER_MANAGE_EDIT   = 'sticker_manage_edit';
    // 龙虎斗
    const API_DRAGON_TIGER_SWITCH = 'dragon_tiger_switch';
    const API_DRAGON_TIGER_CONFIG = 'dragon_tiger_config';

    // 房间皮肤管理
    const API_ROOM_SKIN_CREATE = 'room_skin_create';
    const API_ROOM_SKIN_DELETE = 'room_skin_delete';
    const API_ROOM_SKIN_SEND = 'room_skin_send';
    const API_ROOM_SKIN_RECOVERY = 'room_skin_recovery';

    // 1v1pk
    const API_ONE_PK_OBJECT_DEL = 'one_pk_object_del';

    // 注册登录账号数限制管理
    const API_UPDATE_REGISTER_LOGIN_CONFIG = 'update_register_login_config';

    // 赛马管理配置
    const API_HORSE_RACE_SWITCH = 'horse_race_switch';
    const API_HORSE_RACE_CONFIG = 'horse_race_config';
    const API_HORSE_RACE_ENGINE = 'horse_race_engine';

    // 定制礼物大区开关修改
    const API_CUSTOM_GIFT_SWITCH_UPDATE = 'custom_gift_switch_update';

    // 开屏页管理
    const API_OPEN_SCREEN_CREATE = 'open_screen_create';
    const API_OPEN_SCREEN_EDIT = 'open_screen_edit';
    const API_OPEN_SCREEN_DISABLE = 'open_screen_disable';

    // 人脸识别
    const API_FACE_STATUS = 'face_status';
    const API_FACE_CHANGE = 'face_change';
    const API_FACE_DELETE = 'face_delete';
    const API_FACE_REPLACE = 'face_replace';

    // 自定义贴纸
    const API_CUSTOM_STICKER_RESOURCE_LIST = 'custom_sticker_resource_list';
    const API_CUSTOM_STICKER_RESOURCE_ADD  = 'custom_sticker_resource_add';
    const API_CUSTOM_STICKER_RESOURCE_EDIT = 'custom_sticker_resource_edit';
    const API_CUSTOM_STICKER_MANAGE_LIST   = 'custom_sticker_manage_list';
    const API_CUSTOM_STICKER_MANAGE_ADD    = 'custom_sticker_manage_add';
    const API_CUSTOM_STICKER_MANAGE_EDIT   = 'custom_sticker_manage_edit';
    const API_CUSTOM_STICKER_SWITCH        = 'custom_sticker_switch';

    // 游戏配置
    const API_GAME_CONFIG_LIST = 'game_config_list';
    const API_GAME_CONFIG_UPDATE = 'game_config_update';
    const API_GAME_CONFIG_DETAIL = 'game_config_detail';

    // 游戏黑名单
    const API_GAME_BLACK_LIST_CREATE = 'game_black_list_create';
    const API_GAME_BLACK_LIST_UPDATE = 'game_black_list_update';
    const API_GAME_BLACK_LIST_DELETE = 'game_black_list_delete';


    // 房间置顶卡
    const API_ROOM_TOP_CARD_CREATE = 'room_top_card_create';
    const API_ROOM_TOP_CARD_UPDATE = 'room_top_card_update';
    const API_ROOM_TOP_CARD_SEND = 'room_top_card_send';
    const API_ROOM_TOP_CARD_REMOVE = 'room_top_card_remove';
    const API_ROOM_TOP_CARD_DELETE = 'room_top_card_delete';

    // 设备封禁
    const API_DEVICE_FORBIDDEN = 'device_forbidden';

    // lucky fruit玩法配置
    const API_LUCKY_FRUIT_SWITCH = 'lucky_fruit_switch';
    const API_LUCKY_FRUIT_CONFIG = 'lucky_fruit_config';
    const API_LUCKY_FRUIT_WEIGHT_LIST = 'lucky_fruit_weight_list';
    const API_LUCKY_FRUIT_WEIGHT_UPDATE = 'lucky_fruit_weight_update';
    const API_LUCKY_FRUIT_WEIGHT_INIT = 'lucky_fruit_weight_init';
    const API_LUCKY_FRUIT_WEIGHT_DELETE = 'lucky_fruit_weight_delete';

    // 定制表情包Tag
    const API_EMOTICONS_TAG_CREATE = 'emoticons_tag_create';
    const API_EMOTICONS_TAG_UPDATE = 'emoticons_tag_update';
    const API_EMOTICONS_TAG_DELETE = 'emoticons_tag_delete';
    // 定制表情包素材
    const API_EMOTICONS_META_CREATE = 'emoticons_meta_create';
    const API_EMOTICONS_META_UPDATE = 'emoticons_meta_update';
    const API_EMOTICONS_META_DELETE = 'emoticons_meta_delete';
    // 定制表情包上传
    const API_EMOTICONS_CREATE = 'emoticons_create';
    const API_EMOTICONS_UPDATE = 'emoticons_update';
    const API_EMOTICONS_DELETE = 'emoticons_delete';
    const API_EMOTICONS_DOWN = 'emoticons_down';

    // 点赞素材管理
    const API_LIKE_ICON_CREATE = 'like_icon_create';
    const API_LIKE_ICON_UPDATE = 'like_icon_update';

    //表情包下发
    const API_EMOTICONS_REWARD_LIST = 'emoticons_reward_list';
    const API_EMOTICONS_REWARD_SEARCH_LIST = 'emoticons_reward_search_list';
    const API_EMOTICONS_REWARD_ADD = 'emoticons_reward_add';
    const API_EMOTICONS_REWARD_REDUCE = 'emoticons_reward_reduce';

    // 定制表情相关 API
    const API_CREATE_CUSTOMIZED_EMOTICON = 'createCustomizedEmoticon';
    const API_UPDATE_CUSTOMIZED_EMOTICON = 'updateCustomizedEmoticon';
    const API_QUERY_CUSTOMIZED_EMOTICON = 'queryCustomizedEmoticon';
    
    // 定制表情发放相关 API
    const API_CREATE_CUSTOMIZED_EMOTICON_REWARD = 'createCustomizedEmoticonReward';
    const API_QUERY_CUSTOMIZED_EMOTICON_REWARD = 'queryCustomizedEmoticonReward';
    const API_DEACTIVATE_CUSTOMIZED_EMOTICON_REWARD = 'deactivateCustomizedEmoticonReward';

    // 站外链接跳转大区开关
    const API_SET_URL_LINK_SWITCH = 'set_url_link_switch';

    // 礼物墙
    const API_SET_GIFT_WALL_CONFIG = 'set_gift_wall_config';
    const API_GET_GIFT_WALL_CONFIG = 'get_gift_wall_config';
    const API_SET_GIFT_WALL_WEEK_CONFIG = 'set_gift_wall_week_config';
    const API_GET_GIFT_WALL_WEEK_CONFIG = 'get_gift_wall_week_config';

    // 火箭crash
    const API_ROCKET_CRASH_SWITCH = 'set_rocket_crash_switch';
    const API_ROCKET_CRASH_CONFIG = 'set_rocket_crash_config';
    const API_ROCKET_CRASH_LIMIT_CONFIG_LIST = 'rocket_crash_limit_config_list';
    const API_ROCKET_CRASH_LIMIT_CONFIG_ADD = 'rocket_crash_limit_config_add';
    const API_ROCKET_CRASH_LIMIT_CONFIG_EDIT = 'rocket_crash_limit_config_edit';
    const API_ROCKET_CRASH_CONFIG_TIME_ODDS_LIST = 'rocket_crash_config_time_odds_list';
    const API_ROCKET_CRASH_CONFIG_TIME_ODDS_ADD = 'rocket_crash_config_time_odds_add';
    const API_ROCKET_CRASH_CONFIG_TIME_ODDS_EDIT = 'rocket_crash_config_time_odds_edit';

    // 更新活动1v1pk状态
    const API_UPDATE_ONEPK_OBJ = 'update_onepk_obj';

    // 配置文件管理
    const API_GET_KV = 'get_kv';
    const API_SET_KV = 'set_kv';
    const API_GET_KV_HISTORY = 'get_kv_history';

    // 塔罗牌玩法需求
    const API_TAROT_ODDS_LIST = 'tarot_odds_list';
    const API_TAROT_ODDS_EDIT = 'tarot_odds_edit';
    const API_TAROT_TOTAL_LIMIT_CONFIG_LIST = 'tarot_total_limit_config_list';
    const API_TAROT_TOTAL_LIMIT_CONFIG_ADD = 'tarot_total_limit_config_add';
    const API_TAROT_TOTAL_LIMIT_CONFIG_EDIT = 'tarot_total_limit_config_edit';
    const API_TAROT_CONTRIBUTION_LIMIT_CONFIG_LIST = 'tarot_contribution_limit_config_list';
    const API_TAROT_CONTRIBUTION_LIMIT_CONFIG_ADD = 'tarot_contribution_limit_config_add';
    const API_TAROT_CONTRIBUTION_LIMIT_CONFIG_EDIT = 'tarot_contribution_limit_config_edit';

    // 幸运玩法活动
    const API_ACT_WHEEL_LOTTERY_SET_AWARD = 'act_wheel_lottery_set_award';
    const API_ACT_WHEEL_LOTTERY_GET_WEIGHT_INFO = 'act_wheel_lottery_get_weight_info';

    // 网页离线包配置
    const API_H5_RESOURCE_LIST = 'h5_resource_list';
    const API_H5_RESOURCE_ADD = 'h5_resource_add';
    const API_H5_RESOURCE_EDIT = 'h5_resource_edit';

    //cp商城
    const API_PROP_CARD_ADD = 'prop_card_add';
    const API_PROP_CARD_EDIT = 'prop_card_edit';
    const API_PROP_CARD_CONFIG_ADD ='prop_card_config_add';
    const API_PROP_CARD_CONFIG_EDIT ='prop_card_config_edit';

    // 举报优化
    const API_REPORT_LIST = 'report_list';
    const API_BAN_USER = 'ban_user';
    const API_BAN_LOG = 'ban_log';
    const API_GET_USER_DEVICE_INFO = 'get_user_device_info';
    const API_USER_VIP_LEVEL = 'get_user_vip_level';
    const API_USER_FORBIDDEN_USER = 'user_forbidden_user';

    // horse value
    const API_HORSE_VALUE_LIST = 'horse_value_list';
    const API_HORSE_VALUE_ADD = 'horse_value_add';
    const API_HORSE_VALUE_EDIT = 'horse_value_edit';

    // 游戏大盘调控
    const API_GAME_TOTAL_LIMIT_CONFIG_LIST = 'game_total_limit_config_list';
    const API_GAME_TOTAL_LIMIT_CONFIG_ADD = 'game_total_limit_config_add';
    const API_GAME_TOTAL_LIMIT_CONFIG_EDIT = 'game_total_limit_config_edit';

    // 游戏贡献调控
    const API_GAME_CONTRIBUTION_LIMIT_CONFIG_LIST = 'game_contribution_limit_config_list';
    const API_GAME_CONTRIBUTION_LIMIT_CONFIG_ADD = 'game_contribution_limit_config_add';
    const API_GAME_CONTRIBUTION_LIMIT_CONFIG_EDIT = 'game_contribution_limit_config_edit';

    // 物品发放
    const API_COMMODITY_SEND = 'send_commodity';

    // 设置大区关系席位价格
    const API_INTIMATE_RELATION_PAY_CONFIG_EDIT = 'intimate_relation_pay_config_edit';

    const API_DIAMOND_LIST = 'diamond_list'; // 活动模版 发奖名单
    const API_DIAMOND_LIST_MODIFY = 'diamond_list_modify'; // 活动模版发奖名单 修改金额
    const API_SEND_DIAMOND_AWARD = 'send_diamond_award'; // 活动模版发奖名单 发放钻石奖励

    const API_ACTIVE_ADD_BLACK_LIST = 'active_add_black_list'; // 活动模版 添加黑名单
    const API_ACTIVE_DEL_BLACK_LIST = 'active_del_black_list'; //  活动模版 删减黑名单

    const API_PROP_CARD_SEND = 'prop_card_send';
    const API_PROP_CARD_REMOVE = 'prop_card_remove';
    // 设置登录验证码
    const API_CMS_SMS_CODE = 'intimate_updateUserExp';
    // 邀请码
    const API_INVITE_CODE = 'invitationCode';
    // 礼包人群发放
    const API_AGENCY_HUNTER_GIFT_BAG_BY_USER_TYPE_BATCH = 'agency_hunter_gift_bag_by_user_type_batch';

    //家族管理
    const API_REMOVE_FAMILY_MEMBER = 'removeFamilyMember';
    const API_MODIFY_FAMILY = 'modifyFamily';
    const API_SET_FAMILY_LV = 'set_family_lv';
    const API_DISMISS_FAMILY = 'dismissFamily';

    const API_SLOT_CONTRIBUTION_LIMIT_CONFIG_ADD = 'slot_contribution_limit_config_add';
    const API_SLOT_CONTRIBUTION_LIMIT_CONFIG_EDIT = 'slot_contribution_limit_config_edit';
    const API_SLOT_CONTRIBUTION_LIMIT_CONFIG_TEST = 'slot_contribution_limit_config_test';
    const API_SLOT_CONTRIBUTION_LIMIT_CONFIG_LIST = 'slot_contribution_limit_config_list';
    const API_SLOT_ITEM_WEIGHT_LIST = 'slot_item_weight_list';
    const API_SLOT_ITEM_WEIGHT_EDIT = 'slot_item_weight_edit';
    const API_GREEDY_SKIN_LIST = 'greedy_skin_list';
    const API_GREEDY_SKIN_ADD = 'greedy_skin_add';
    const API_GREEDY_SKIN_EDIT = 'greedy_skin_edit';
    const API_GREEDY_SKIN_DEL = 'greedy_skin_del';
    const API_GREEDY_SKIN_EXPIRE = 'greedy_skin_expire';
    const API_MULTI_ANCHOR_AWARD_CONFIG_LIST = 'multi_anchor_award_config_list';
    const API_MULTI_ANCHOR_AWARD_CONFIG_UPDATE = 'multi_anchor_award_config_update';
    const API_MULTI_ANCHOR_AWARD_CONFIG_GET = 'multi_anchor_award_config_get';

    const API_GREEDY_BOX_ODDS_LIST = 'greedy_box_odds_list';
    const API_GREEDY_BOX_ODDS_EDIT = 'greedy_box_odds_edit';

    const API_VIDEO_LIVE_STOP = 'video_live_stop';

    const API_FORBID_ROOM = 'forbid_room'; // 封禁房间
    const API_UNFORBID_ROOM = 'unforbid_room'; // 解封房间

    const API_ACT_HONOUR_WALL_ADD = 'act_honour_wall_add';
    const API_ACT_HONOUR_WALL_EDIT = 'act_honour_wall_edit';
    const API_ACT_HONOUR_WALL_DEL = 'act_honour_wall_del';

    const API_EDIT_GIFT_DESC = 'edit_gift_description'; //礼物描述

    // 修改用户地区
    const API_UPDATE_USER_COUNTRY = 'update_user_country';

    const API_OPERATE_USER_VIP = 'operateUserVip';


    // 房间封面修改
    const API_ROOM_COVER_CHANGE =  'room_cover_change';
    // 关闭房间
    const API_ROOM_CLOSE = 'room_close';

    //pk道具配置
    const API_PK_PROP_CARD_FIRST_GIFT_CONFIG_UPDATE = 'pk_prop_card_first_gift_config_update';

    //炫彩昵称
    const API_NAME_ID_LIGHTING_CONFIG_SET = 'name_id_lighting_config_set';
    const API_NAME_ID_LIGHTING_CONFIG_LIST = 'name_id_lighting_config_list';
    const API_NAME_ID_LIGHTING_LOG_LIST = 'name_id_lighting_log_list';
    const API_NAME_ID_LIGHTING_ADD = 'name_id_lighting_config_add';
    const API_USER_NAME_ID_LIGHTING_LIST = 'user_name_id_lighting_config_list';
    const API_USER_NAME_ID_LIGHTING_INVALID = 'user_name_id_lighting_config_invalid';
    const API_BIND_MOBILE = 'bind_mobile'; // 绑定手机号

    // mini卡装扮
    const API_MINI_CARD_SET = 'mini_card_set';
    const API_MINI_CARD_SEND = 'mini_card_send'; //下发
    const API_MINI_CARD_INVALID = 'mini_card_invalid'; //失效
    
    //荣誉等级
    const API_HONOR_LEVEL_CONFIG_LIST = 'honor_level_config_list';
    const API_HONOR_LEVEL_CONFIG_CREATE = 'honor_level_config_create';
    const API_HONOR_LEVEL_CONFIG_UPDATE = 'honor_level_config_update';
    const API_HONOR_LEVEL_CONFIG_GET = 'honor_level_config_get';
    const API_HONOR_LEVEL_SEND_LIST = 'honor_level_send_list';
    const API_HONOR_LEVEL_SEND = 'honor_level_send';
    const API_HONOR_LEVEL_SEND_BATCH = 'honor_level_send_batch';
    const API_HONOR_LEVEL_MANAGE_LIST = 'honor_level_manage_list';
    const API_HONOR_LEVEL_DISABLE = 'honor_level_disable';

    // 关系增值道具购买记录
    const API_PROP_CARD_BUY_RECORD_LIST = 'prop_card_buy_record_list';

    const API_APPROVE_GAME_BLACKLIST = 'approve_game_blacklist';
    const API_REJECT_GAME_BLACKLIST = 'reject_game_blacklist';

    // 推荐位配置
    const API_RECOMMEND_CONFIG_LIST = 'recommend_config_list';
    const API_RECOMMEND_CONFIG_CREATE = 'recommend_config_create';
    const API_RECOMMEND_CONFIG_MODIFY = 'recommend_config_modify';
    const API_RECOMMEND_CONFIG_DELETE = 'recommend_config_delete';
    const API_RECOMMEND_CONFIG_DETAIL = 'recommend_config_detail';

    // 日志查询
    const API_QUERY_APP_ADMIN_OPERATE_LOG = 'query_app_admin_operate_log';
    const API_QUERY_APP_ADMIN_OPERATE_LOG_DETAIL = 'query_app_admin_operate_log_detail';
    // 房间隐身特权下发
    const API_ISSUED_ROOM_STEALTH_PRIVILEGE = 'issued_room_stealth_privilege';
    // 贡献榜单匿名权益下发
    const API_SEND_RANK_ANONYMOUS_PRIVILEGE = 'send_rank_anonymous_privilege';
    // 财富等级隐藏权益下发
    const API_SEND_WEALTH_LV_HIDE_PRIVILEGE = 'send_wealth_lv_hide_privilege';

    //用户自主上传开屏
    const API_OPEN_SCREEN_CARD_SEND = 'open_screen_card_send'; //下发开屏卡
    const API_OPEN_SCREEN_CARD_LIST = 'open_screen_card_list'; //开屏卡列表
    const API_OPEN_SCREEN_CARD_EXPIRE = 'open_screen_card_expire'; //失效开屏卡
    const API_OPEN_SCREEN_CARD_AUDIT = 'open_screen_card_audit'; //审核开屏卡

    // 多语言配置
    const API_I18N_LIST_CONFIG = 'i18n_list_config';
    const API_I18N_CREATE_CONFIG = 'i18n_create_config';
    const API_I18N_GET_CONFIG = 'i18n_get_config';
    const API_I18N_BATCH_CREATE_CONFIG = 'i18n_batch_create_config';
    const API_I18N_BATCH_UPDATE_CONFIG = 'i18n_batch_update_config';
    const API_I18N_UPDATE_CONFIG = 'i18n_update_config';
    const API_I18N_DELETE_CONFIG = 'i18n_delete_config';
    const API_I18N_PUBLISH = 'i18n_publish';

    //上传日志
    const API_CREATE_UPLOAD_LOG_INSTRUCTION = 'i18n_create_upload_log_instruction';

    const API_OP_ACT_SCORE = 'op_act_score';
    const API_USER_CARD_SEND = 'user_card_send';
    const API_USER_CARD_EXPIRE = 'user_card_expire';
    const API_USER_CARD_ADUIT = 'user_card_audit';

    //互动表情素材
    const API_INTERACTIVE_EMOTICON_CREATE = 'interactive_emoticon_create';
    const API_INTERACTIVE_EMOTICON_EDIT = 'interactive_emoticon_edit';
    const API_INTERACTIVE_EMOTICON_LISTED = 'interactive_emoticon_listed';

    //语音房背景音效
    const API_ROOM_BACKGROUND_SOUND_EFFECT_CREATE = 'room_background_sound_effect_create';
    const API_ROOM_BACKGROUND_SOUND_EFFECT_EDIT = 'room_background_sound_effect_edit';
    const API_ROOM_BACKGROUND_SOUND_EFFECT_DEL = 'room_background_sound_effect_del';

    protected $apiConfig = [
        'domain' => ENV == 'dev' ? 'https://dev.partystar.cloud' : 'http://api.ps-app.private',
        'host' => ENV == 'dev' ? 'dev.partystar.cloud' : 'api.ps-app.private'
    ];

    public $apiList = [
        self::API_ACTIVE_ADD_BLACK_LIST => [
            'path' => '/go/internal/activity/addBlackList',
            'method' => 'post',
        ],
        self::API_ACTIVE_DEL_BLACK_LIST => [
            'path' => '/go/internal/activity/delBlackList',
            'method' => 'post',
        ],
        self::API_DIAMOND_LIST_MODIFY => [
            'path' => '/go/internal/activity/modifyDiamondList',
            'method' => 'post',
        ],
        self::API_SEND_DIAMOND_AWARD => [
            'path' => '/go/internal/activity/actSendDiamondAward',
            'method' => 'post',
        ],
        self::API_OP_ACT_SCORE => [
            'path' => '/go/internal/cms/opActScore',
            'method' => 'post',
        ],
        self::API_USER_CARD_SEND => [
            'path' => '/go/internal/cms/sendEmoticonCard',
            'method' => 'post',
        ],
        self::API_USER_CARD_EXPIRE => [
            'path' => '/go/internal/cms/expireEmoticonCard',
            'method' => 'post',
        ],
        self::API_USER_CARD_ADUIT => [
            'path' => '/go/internal/cms/auditEmoticon',
            'method' => 'post',
        ],
        self::API_OPEN_SCREEN_CARD_LIST => [
            'path' => '/go/internal/cms/queryOpenScreenCardList',
            'method' => 'post',
        ],
        self::API_OPEN_SCREEN_CARD_SEND => [
            'path' => '/go/internal/cms/sendOpenScreenCard',
            'method' => 'post',
        ],
        self::API_OPEN_SCREEN_CARD_EXPIRE => [
            'path' => '/go/internal/cms/expireOpenScreenCard',
            'method' => 'post',
        ],
        self::API_OPEN_SCREEN_CARD_AUDIT => [
            'path' => '/go/internal/cms/auditOpenScreen',
            'method' => 'post',
        ],
        self::API_MINI_CARD_SET => [
            'path' => '/gk/v1/item/itemCard/internal/setItemCard',
            'method' => 'post',
        ],
        self::API_MINI_CARD_SEND => [
            'path' => '/gk/v1/item/itemCard/internal/addItemCard',
            'method' => 'post',
        ],
        self::API_MINI_CARD_INVALID => [
            'path' => '/gk/v1/item/itemCard/internal/invalidUserItemCard',
            'method' => 'post',
        ],
        self::API_UPDATE_GIFT_STATUS => [
            'path' => '/go/internal/cms/updateGiftStatus',
            'method' => 'post',
        ],
        self::API_EDIT_GIFT_DESC => [
            'path' => '/go/internal/cms/editGiftDescription',
            'method' => 'post',
        ],
        self::API_UPDATE_PRICE_LEVEL => [
            'path' => '/go/internal/cms/updateUserExp?format=json',
            'method' => 'post',
        ],
        self::API_CMS_SMS_CODE => [
            'path' => '/go/internal/cms/smsCode?format=json',
            'method' => 'post',
        ],
        self::API_INVITE_CODE => [
            'path' => '/go/internal/cms/invitationCode?format=json',
            'method' => 'post',
        ],
        self::API_PRICE_LEVEL => [
            'path' => '/go/internal/cms/listUserExp?format=json',
            'method' => 'post',
        ],
        self::API_ROOM_TOP => [
            'path' => '/go/internal/cms/createRoomTop',
            'method' => 'post',
        ],
        self::API_ROOM_BOTTOM_CONFIG => [
            'path' => '/go/internal/cms/roomBottomConfig',
            'method' => 'post',
        ],
        self::API_ROOM_TOP_MODIFY => [
            'path' => '/go/internal/cms/editRoomTop',
            'method' => 'post',
        ],
        self::API_ROOM_TOP_CANCEL => [
            'path' => '/go/internal/cms/cancelRoomTop',
            'method' => 'post',
        ],
        self::API_GAME_COIN_MODIFY => [
            'path' => '/go/internal/game/coin',
            'method' => 'post',
        ],
        // 查询视频信息
        self::API_QUERY_VIDEO_INFO => [
            'path' => '/go/internal/cms/queryVideoInfo',
            'method' => 'post'
        ],
        // 设置sicbo大区开关
        self::API_SIC_BO_SWITCH => [
            'path' => '/go/internal/opconfig/setSicBoSwitch',
            'method' => 'post'
        ],
        // 设置sicbo配置
        self::API_SIC_BO_CONFIG => [
            'path' => '/go/internal/opconfig/setSicBoMeta',
            'method' => 'post'
        ],
        // 设置slot游戏大区开关
        self::API_SLOT_SWITCH => [
            'path' => '/go/internal/opconfig/setSlotSwitch',
            'method' => 'post'
        ],
        self::API_LUCKY_GIFT_SWITCH => [
            'path' => '/go/internal/opconfig/setLuckyGiftSwitch',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_DIVIDED_ADD => [
            'path' => '/go/internal/cms/addLuckyGiftDivided',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_DIVIDED_EDIT => [
            'path' => '/go/internal/cms/editLuckyGiftDivided',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_DIVIDED_DEL => [
            'path' => '/go/internal/cms/deletedLuckyGiftDivided',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_RATE_ADJUST_ADD => [
            'path' => '/go/internal/cms/addLuckyGiftRateAdjust',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_RATE_ADJUST_EDIT => [
            'path' => '/go/internal/cms/editLuckyGiftRateAdjust',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_RATE_ADJUST_DEL => [
            'path' => '/go/internal/cms/deletedLuckyGiftRateAdjust',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_RATE_ADD => [
            'path' => '/go/internal/cms/addLuckyGiftRate',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_RATE_EDIT => [
            'path' => '/go/internal/cms/editLuckyGiftRate',
            'method' => 'post',
        ],
        self::API_LUCKY_GIFT_RATE_DEL => [
            'path' => '/go/internal/cms/deletedLuckyGiftRate',
            'method' => 'post',
        ],
        self::API_USER_MEDAL_LESS_TIME => [
            'path' => '/go/internal/cms/operateUserMedal',
            'method' => 'post',
        ],
        self::API_USER_MEDAL_UPDATE_CONFIG => [
            'path'   => '/go/internal/cms/updateUserMedalConfig',
            'method' => 'post',
        ],
        self::API_RED_PACKET_CONFIG => [
            'path' => '/go/internal/opconfig/setRedpacketConfig',
            'method' => 'post',
        ],
        self::API_RED_PACKET_CONFIG_INFO => [
            'path' => '/go/internal/opconfig/getRedpacketConfig',
            'method' => 'post',
        ],
        self::API_GET_TEAM_PK_RECORD => [
            'path' => '/go/internal/teampk/getTeamPkRecord',
            'method' => 'get',
        ],
        self::API_GET_TEAM_PK_DIAMOND_RECORD => [
            'path' => '/go/internal/teampk/getTeamPkDiamondRecord',
            'method' => 'get',
        ],
        self::API_LUCKY_WHEEL_MODIFY => [
            'path' => '/go/internal/cms/luckyWheelConfigSave',
            'method' => 'post',
        ],
        self::API_LUCKY_LIMIT_CONFIG_LIST => [
            'path' => '/go/internal/opconfig/getLuckyFruitsLimitConfig',
            'method' => 'get',
        ],
        self::API_LUCKY_LIMIT_CONFIG_ADD => [
            'path' => '/go/internal/opconfig/addLuckyFruitsLimitConfig',
            'method' => 'post',
        ],
        self::API_LUCKY_LIMIT_CONFIG_MODIFY => [
            'path' => '/go/internal/opconfig/editLuckyFruitsLimitConfig',
            'method' => 'post',
        ],
        self::API_LUCKY_LIMIT_CONFIG_DEL => [
            'path' => '/go/internal/opconfig/deleteLuckyFruitsLimitConfig',
            'method' => 'post',
        ],
        self::API_PROBABILITY_GAME_SWITCH_LIST => [
            'path' => '/go/internal/cms/getProbabilityGameSwitch',
            'method' => 'post',
        ],
        self::API_PROBABILITY_GAME_SWITCH_MODIFY => [
            'path' => '/go/internal/cms/editProbabilityGameSwitch',
            'method' => 'post',
        ],
        self::API_CHATROOM_MATERIAL_LIST => [
            'path' => '/go/internal/cms/chatroomMaterialList',
            'method' => 'post',
        ],
        self::API_CHATROOM_MATERIAL_ADD => [
            'path' => '/go/internal/cms/addChatroomMaterial',
            'method' => 'post',
        ],
        self::API_CHATROOM_MATERIAL_EDIT => [
            'path' => '/go/internal/cms/editChatroomMaterial',
            'method' => 'post',
        ],
        self::API_CHATROOM_MATERIAL_DELETE => [
            'path' => '/go/internal/cms/delChatroomMaterial',
            'method' => 'post',
        ],

        self::API_CHATROOM_BACKGROUND_LIST => [
            'path' => '/go/internal/cms/chatroomBackgroundMallList',
            'method' => 'get',
        ],
        self::API_CHATROOM_BACKGROUND_ADD => [
            'path' => '/go/internal/cms/addRoomBackground',
            'method' => 'post',
        ],
        self::API_CHATROOM_BACKGROUND_EDIT => [
            'path' => '/go/internal/cms/editRoomBackground',
            'method' => 'post',
        ],
        self::API_CHATROOM_BACKGROUND_DELETE => [
            'path' => '/go/internal/cms/delRoomBackground',
            'method' => 'post',
        ],
        self::API_CHATROOM_BACKGROUND_DROP => [
            'path' => '/go/internal/cms/dropRoomBackground',
            'method' => 'post',
        ],
        self::API_CHATROOM_BACKGROUND_M_DROP => [
            'path' => '/go/internal/cms/mDropRoomBackground',
            'method' => 'post',
        ],
        self::API_USER_CHATROOM_BACKGROUND_LIST => [
            'path' => '/go/internal/cms/userChatroomBackgroundList',
            'method' => 'post',
        ],
        self::API_USER_CHATROOM_BACKGROUND_EDIT => [
            'path' => '/go/internal/cms/editUserRoomBackground',
            'method' => 'post',
        ],
        self::API_DROP_ROOM_BACKGROUND => [
            'path' => '/go/internal/cms/dropRoomBackground',
            'method' => 'post',
        ],
        self::API_M_DROP_ROOM_BACKGROUND => [
            'path' => '/go/internal/cms/mDropRoomBackground',
            'method' => 'post',
        ],
        self::API_GIVE_CERTIFICATION_SIGN => [
            'path' => '/go/internal/cms/giveCertificationSign',
            'method' => 'post',
        ],
        self::API_UPDATE_CERTIFICATION_SIGN => [
            'path' => '/go/internal/cms/updateCertificationSign',
            'method' => 'post',
        ],
        self::API_QUICK_GIFT_UPDATE => [
            'path' => '/go/internal/cms/editQuickGiftConfig',
            'method' => 'post',
        ],
        self::API_TEEN_PATTI_SWITCH => [
            'path' => '/go/internal/cms/updateTeenPattiSwitch',
            'method' => 'post'
            ],
        self::API_GREEDY_SWITCH => [
            'path' => '/go/internal/opconfig/setGreedySwitch',
            'method' => 'post',
        ],
        self::API_GREEDY_ENGINE => [
            'path' => '/go/internal/opconfig/setGreedyEngine',
            'method' => 'post',
        ],
        self::API_GREEDY_GLOBAL_RANK_SWITCH => [
            'path' => '/go/internal/opconfig/setGreedyGlobalRankSwitch',
            'method' => 'post',
        ],
        self::API_GREEDY_CONFIG => [
            'path' => '/go/internal/opconfig/setGreedyMeta',
            'method' => 'post',
        ],
        self::API_GREEDY_CONFIG_BIG_AREA_BOX => [
            'path' => '/go/internal/opconfig/setBigAreaGreedyBox',
            'method' => 'post',
        ],
        self::API_GREEDY_BOX_SWITCH => [
            'path' => '/go/internal/opconfig/setGreedyBoxSwitch',
            'method' => 'post',
        ],
        self::API_LOGIN_REGISTER_WHITE_LIST_ADD => [
            'path' => '/go/internal/opconfig/addLoginRegisterWhiteList',
            'method' => 'post',
        ],
        //管理后台发放自选靓号资格
        self::API_PRETTY_USER_CUSTOMIZE_CREATE => [
            'path' => '/go/internal/cms/giveCustomizePretty',
            'method' => 'post',
        ],
        //管理后台更新自选靓号资格
        self::API_PRETTY_USER_CUSTOMIZE_MODIFY => [
            'path' => '/go/internal/cms/updateCustomizePretty',
            'method' => 'post',
        ],

        //管理后台靓号商城
        self::API_PRETTY_COMMODITY_CREATE => [
            'path' => '/go/internal/cms/createPretty',
            'method' => 'post',
        ],
        //管理后台靓号商城
        self::API_PRETTY_COMMODITY_MODIFY => [
            'path' => '/go/internal/cms/editPretty',
            'method' => 'post',
        ],
        self::API_PRETTY_COMMODITY_SHELF => [
            'path' => '/go/internal/cms/batchUpdateOnSaleStatus',
            'method' => 'post',
        ],

        self::API_GIVE_PRETTY_UID_CREATE => [
            'path' => '/go/internal/cms/givePrettyUid',
            'method' => 'post',
        ],
        self::API_GIVE_PRETTY_UID_MODIFY => [
            'path' => '/go/internal/cms/updatePrettyUid',
            'method' => 'post',
        ],

        self::API_ROOM_ROCKET_SWITCH => [
            'path' => '/go/internal/cms/editBoomRocketSwitch',
            'method' => 'post',
        ],
        self::API_ROOM_ROCKET_CONFIG_LIST => [
            'path' => '/go/internal/cms/getBoomRocketConfigList',
            'method' => 'post',
        ],
        self::API_ROOM_ROCKET_CONFIG_INFO => [
            'path' => '/go/internal/cms/getBoomRocketConfigByBigAreaId',
            'method' => 'post',
        ],
        self::API_ROOM_ROCKET_CONFIG_EDIT => [
            'path' => '/go/internal/cms/saveBoomRocketConfig',
            'method' => 'post',
        ],
        self::API_ROOM_ROCKET_AWARD_CONFIG_EDIT => [
            'path' => '/go/internal/cms/saveBoomRocketAwardConfig',
            'method' => 'post',
        ],
        self::API_ROOM_ROCKET_AWARD_CONFIG => [
            'path' => '/go/internal/cms/getBoomRocketAwardConfig',
            'method' => 'post',
        ],
        self::API_POPUPS_CONFIG_ADD => [
            'path' => '/go/internal/opconfig/addPopupsConfig',
            'method' => 'post',
        ],
        // 重置支付密码
        self::API_RESET_PAY_PASS => [
            'path' => '/go/internal/cms/resetPayPassword',
            'method' => 'post',
        ],
        // 重置支付密码次数
        self::API_RESET_PAY_PASS_NUM => [
            'path' => '/go/internal/cms/resetPayPasswordReTryTimes',
            'method' => 'post',
        ],
        // 重置安全邮箱
        self::API_RESET_EMAIL => [
            'path' => '/go/internal/cms/resetSecureEmail',
            'method' => 'post',
        ],
        // 修改安全邮箱
        self::API_MODIFY_EMAIL => [
            'path' => '/go/internal/cms/modifySecureEmail',
            'method' => 'post',
        ],
        // 自定义房间背景卡片大区开关列表
        self::API_CUSTOM_ROOM_BACKGROUND_CARD_SWITCH => [
            'path' => '/go/internal/cms/customizeCardSwitchList',
            'method' => 'post',
        ],
        // 自定义房间背景卡片大区开关编辑
        self::API_CUSTOM_ROOM_BACKGROUND_CARD_SWITCH_EDIT => [
            'path' => '/go/internal/cms/saveCustomizeBgSwitch',
            'method' => 'post',
        ],
        // 自定义房间背景卡片列表
        self::API_CUSTOM_ROOM_BACKGROUND_CARD_LOG => [
            'path' => '/go/internal/cms/customizeCardLogList',
            'method' => 'post',
        ],
        // 自定义房间背景卡片发放
        self::API_CUSTOM_ROOM_BACKGROUND_CARD_LOG_ADD => [
            'path' => '/go/internal/cms/addCustomizeCard',
            'method' => 'post',
        ],
        // 自定义房间背景卡片背包列表
        self::API_CUSTOM_ROOM_BACKGROUND_CARD_PACK => [
            'path' => '/go/internal/cms/customizeCardList',
            'method' => 'post',
        ],
        // 自定义房间背景卡片背包删除
        self::API_CUSTOM_ROOM_BACKGROUND_CARD_PACK_DEL => [
            'path' => '/go/internal/cms/deleteCustomizeCard',
            'method' => 'post',
        ],
        // 新增超管账号
        self::ADD_SUPER_ADMIN => [
            'path' => '/go/internal/cms/addSuperAdmin',
            'method' => 'post',
        ],
        // 新增巡管账号
        self::ADD_PATROL_ACCOUNT => [
            'path' => '/go/internal/cms/addChatroomSuperAdmin',
            'method' => 'post',
        ],
        // 预期可见玩法大区等级配置列表
        self::API_PROBABILITY_GAME_BIG_AREA_CONFIG_LIST => [
            'path' => '/go/internal/cms/getProbabilityGameBigAreaConfig',
            'method' => 'post',
        ],
        // 预期可见玩法大区等级配置编辑
        self::API_PROBABILITY_GAME_BIG_AREA_CONFIG_EDIT => [
            'path' => '/go/internal/cms/editProbabilityGameBigAreaConfig',
            'method' => 'post',
        ],

        self::API_WELCOME_GIFTBAG_CREATE => [
            'path' => '/go/internal/cms/createGiftBag',
            'method' => 'post',
        ],

        self::API_WELCOME_GIFTBAG_MODIFY => [
            'path' => '/go/internal/cms/editGiftBag',
            'method' => 'post',
        ],

        self::API_WELCOME_BIGAREA_MODIFY => [
            'path' => '/go/internal/cms/updateBigAreaInviteGiftSwitch',
            'method' => 'post',
        ],
        self::API_AGENCY_HUNTER_GIFT_BAG_STATUS_MODIFY => [
            'path' => '/go/internal/cms/updateAgencyHunterGiftBagStatus',
            'method' => 'post',
        ],
        self::API_WELCOME_GIFTHUNTER_CREATE => [
            'path' => '/go/internal/cms/createAgencyHunterGiftBag',
            'method' => 'post',
        ],
        self::API_WELCOME_GIFTHUNTER_CREATE_BATCH => [
            'path' => '/go/internal/cms/batchAgencyHunterGiftBag',
            'method' => 'post',
        ],
        self::API_WELCOME_GIFTHUNTER_UPDATE_BATCH => [
            'path' => '/go/internal/cms/updateBatchAgencyHunterGiftBag',
            'method' => 'post',
        ],
        self::API_WELCOME_GIFTHUNTER_CREATE_BATCH_CONDITION => [
            'path' => '/go/internal/cms/batchAgencyHunterGiftBagByCondition',
            'method' => 'post',
        ],
        self::API_WELCOME_GIFTHUNTER_MODIFY => [
            'path' => '/go/internal/cms/editAgencyHunterGiftBag',
            'method' => 'post',
        ],

        self::API_VIP_SEND_BATCH_ADD => [
            'path' => '/go/internal/cms/batchAddVIP',
            'method' => 'post',
        ],
        self::API_VIP_SEND_CHECK => [
            'path' => '/go/internal/cms/vipSendCheck',
            'method' => 'post',
        ],

        //游戏优惠券列表
        self::GAME_COUPON_CONFIG_LIST => [
            'path' => '/go/internal/cms/queryCouponPageList',
            'method' => 'post',
        ],
        //游戏优惠券ALL列表
        self::GAME_COUPON_CONFIG_ALL_LIST => [
            'path' => '/go/internal/cms/queryCouponList',
            'method' => 'post',
        ],
        //游戏优惠券创建
        self::GAME_COUPON_CONFIG_CREATE => [
            'path' => '/go/internal/cms/createCoupon',
            'method' => 'post',
        ],
        //游戏优惠券编辑
        self::GAME_COUPON_CONFIG_MODIFY => [
            'path' => '/go/internal/cms/updateCoupon',
            'method' => 'post',
        ],
        //游戏优惠券-档位
        self::GAME_COUPON_CONFIG_AMOUNT => [
            'path' => '/go/internal/cms/queryCouponAmounts',
            'method' => 'post',
        ],
        //游戏优惠券-使用场景
        self::GAME_COUPON_CONFIG_SCENE => [
            'path' => '/go/internal/cms/queryCouponScenes',
            'method' => 'post',
        ],
        //游戏优惠券下发，扣减
        self::GAME_COUPON_ISSUED_OP => [
            'path' => '/go/internal/cms/couponOperate',
            'method' => 'post',
        ],
        // 贴纸素材管理-列表
        self::API_STICKER_RESOURCE_LIST => [
            'path' => '/go/internal/cms/getStickerResourceList',
            'method' => 'post',
        ],
        // 贴纸素材管理-添加
        self::API_STICKER_RESOURCE_ADD => [
            'path' => '/go/internal/cms/stickerResourceAdd',
            'method' => 'post',
        ],
        // 贴纸素材管理-编辑
        self::API_STICKER_RESOURCE_EDIT => [
            'path' => '/go/internal/cms/stickerResourceEdit',
            'method' => 'post',
        ],
        // 贴纸列表管理-列表
        self::API_STICKER_MANAGE_LIST => [
            'path' => '/go/internal/cms/getStickerManageList',
            'method' => 'post',
        ],
        // 贴纸列表管理-创建
        self::API_STICKER_MANAGE_ADD => [
            'path' => '/go/internal/cms/stickerManageAdd',
            'method' => 'post',
        ],
        // 贴纸列表管理-编辑
        self::API_STICKER_MANAGE_EDIT => [
            'path' => '/go/internal/cms/stickerManageEdit',
            'method' => 'post',
        ],
        //龙虎斗-大区开关
        self::API_DRAGON_TIGER_SWITCH => [
            'path' => '/go/internal/opconfig/setDragonTigerSwitch',
            'method' => 'post',
        ],
        //龙虎斗-大区开关
        self::API_DRAGON_TIGER_CONFIG => [
            'path'   => '/go/internal/opconfig/setDragonTigerMeta',
            'method' => 'post',
        ],
        // 新增房间皮肤配置
        self::API_ROOM_SKIN_CREATE => [
            'path' => '/go/internal/cms/createRoomSkin',
            'method' => 'post',
        ],
        // 删除房间皮肤配置
        self::API_ROOM_SKIN_DELETE => [
            'path' => '/go/internal/cms/delRoomSkin',
            'method' => 'post',
        ],
        // 下发房间皮肤配置
        self::API_ROOM_SKIN_SEND => [
            'path' => '/go/internal/cms/grantRoomSkinToUser',
            'method' => 'post',
        ],
        // 房间皮肤使用期限回收
        self::API_ROOM_SKIN_RECOVERY => [
            'path' => '/go/internal/cms/subRoomSkinUseTerm',
            'method' => 'post',
        ],
        // 1v1pk删除对战数据
        self::API_ONE_PK_OBJECT_DEL => [
            'path' => '/go/internal/activity/delOnepkObj',
            'method' => 'post',
        ],
        // 注册登录账号数限制管理
        self::API_UPDATE_REGISTER_LOGIN_CONFIG => [
            'path' => '/go/internal/opconfig/updateRegisterLoginConfig',
            'method' => 'post',
        ],
        // 赛马开关配置
        self::API_HORSE_RACE_SWITCH => [
            'path' => '/go/internal/opconfig/setHorseRaceSwitch',
            'method' => 'post',
        ],
        // 赛马参数配置
        self::API_HORSE_RACE_CONFIG => [
            'path' => '/go/internal/opconfig/setHorseRaceConfig',
            'method' => 'post',
        ],
        // 赛马引擎配置
        self::API_HORSE_RACE_ENGINE => [
            'path' => '/go/internal/opconfig/setHorseRaceEngine',
            'method' => 'post',
        ],
        // 注册登录账号数限制管理
        self::API_CUSTOM_GIFT_SWITCH_UPDATE => [
            'path' => '/go/internal/cms/updateGiftSwitch',
            'method' => 'post',
        ],
        // 创建开屏页
        self::API_OPEN_SCREEN_CREATE => [
            'path' => '/go/internal/cms/createOpenScreen',
            'method' => 'post',
        ],
        // 编辑开屏页
        self::API_OPEN_SCREEN_EDIT => [
            'path' => '/go/internal/cms/editOpenScreen',
            'method' => 'post',
        ],
        // 禁用开屏页
        self::API_OPEN_SCREEN_DISABLE => [
            'path' => '/go/internal/cms/disableOpenScreen',
            'method' => 'post',
        ],
        // 人脸审核-修改审核状态
        self::API_FACE_STATUS => [
            'path' => '/go/internal/cms/faceStatus',
            'method' => 'post',
        ],
        // 人脸库-替换人脸图
        self::API_FACE_CHANGE => [
            'path' => '/go/internal/cms/faceChange',
            'method' => 'post',
        ],
        // 人脸库-删除人脸图
        self::API_FACE_DELETE => [
            'path' => '/go/internal/cms/faceDelete',
            'method' => 'post',
        ],
        // 人脸库-替换UID
        self::API_FACE_REPLACE => [
            'path' => '/go/internal/cms/faceReplace',
            'method' => 'post',
        ],
        // 自定义贴纸素材列表
        self::API_CUSTOM_STICKER_RESOURCE_LIST => [
            'path' => '/go/internal/cms/getCustomStickerResourceList',
            'method' => 'post',
        ],
        // 自定义贴纸素材创建
        self::API_CUSTOM_STICKER_RESOURCE_ADD => [
            'path' => '/go/internal/cms/customStickerResourceAdd',
            'method' => 'post',
        ],
        // 自定义贴纸素材编辑
        self::API_CUSTOM_STICKER_RESOURCE_EDIT => [
            'path' => '/go/internal/cms/customStickerResourceEdit',
            'method' => 'post',
        ],
        // 自定义贴纸列表
        self::API_CUSTOM_STICKER_MANAGE_LIST=> [
            'path' => '/go/internal/cms/getCustomStickerManageList',
            'method' => 'post',
        ],
        // 自定义贴纸创建
        self::API_CUSTOM_STICKER_MANAGE_ADD => [
            'path' => '/go/internal/cms/customStickerManageAdd',
            'method' => 'post',
        ],
        // 自定义贴纸编辑
        self::API_CUSTOM_STICKER_MANAGE_EDIT => [
            'path' => '/go/internal/cms/customStickerManageEdit',
            'method' => 'post',
        ],
        // 自定义贴纸大区开关
        self::API_CUSTOM_STICKER_SWITCH => [
            'path' => '/go/internal/cms/setCustomStickerSwitch',
            'method' => 'post',
        ],
        // 游戏黑名单创建
        self::API_GAME_BLACK_LIST_CREATE => [
            'path' => '/go/internal/cms/createGameBlackList',
            'method' => 'post',
        ],
        // 游戏黑名单修改
        self::API_GAME_BLACK_LIST_UPDATE => [
            'path' => '/go/internal/cms/updateGameBlackList',
            'method' => 'post',
        ],
        // 游戏黑名单删除
        self::API_GAME_BLACK_LIST_DELETE => [
            'path' => '/go/internal/cms/deleteGameBlackList',
            'method' => 'post',
        ],
        // 创建置顶卡
        self::API_ROOM_TOP_CARD_CREATE => [
            'path' => '/go/internal/cms/createRoomTopCard',
            'method' => 'post',
        ],
        // 修改置顶卡
        self::API_ROOM_TOP_CARD_UPDATE => [
            'path' => '/go/internal/cms/updateRoomTopCard',
            'method' => 'post',
        ],
        // 发放置顶卡
        self::API_ROOM_TOP_CARD_SEND => [
            'path' => '/go/internal/cms/sendRoomTopCard',
            'method' => 'post',
        ],
        // 回收置顶卡
        self::API_ROOM_TOP_CARD_REMOVE => [
            'path' => '/go/internal/cms/recoverRoomTopCard',
            'method' => 'post',
        ],
        // 删除置顶卡
        self::API_ROOM_TOP_CARD_DELETE => [
            'path' => '/go/internal/cms/deleteRoomTopCard',
            'method' => 'post',
        ],
        // 设备封禁
        self::API_DEVICE_FORBIDDEN => [
            'path' => '/go/internal/cms/deviceForbidden',
            'method' => 'post',
        ],
        // lucky fruit 修改大区开关
        self::API_LUCKY_FRUIT_SWITCH => [
            'path' => '/go/internal/opconfig/setLuckyFruitsSwitch',
            'method' => 'post',
        ],
        // lucky fruit 修改参数配置
        self::API_LUCKY_FRUIT_CONFIG => [
            'path' => '/go/internal/opconfig/setLuckyFruitsConfig',
            'method' => 'post',
        ],
        // lucky fruit 初始化权重表
        self::API_LUCKY_FRUIT_WEIGHT_INIT => [
            'path' => '/go/internal/opconfig/initLuckyFruitsWeight',
            'method' => 'post',
        ],
        // lucky fruit 查询权重表
        self::API_LUCKY_FRUIT_WEIGHT_LIST => [
            'path' => '/go/internal/opconfig/getLuckyFruitsWeight',
            'method' => 'post',
        ],
        // lucky fruit 修改权重表
        self::API_LUCKY_FRUIT_WEIGHT_UPDATE => [
            'path' => '/go/internal/opconfig/editLuckyFruitsWeight',
            'method' => 'post',
        ],
        // lucky fruit删除权重表
        self::API_LUCKY_FRUIT_WEIGHT_DELETE => [
            'path' => '/go/internal/opconfig/delLuckyFruitsWeight',
            'method' => 'post',
        ],
        // 定制表情包tag添加
        self::API_EMOTICONS_TAG_CREATE => [
            'path' => '/go/internal/cms/createEmoticonsTag',
            'method' => 'post',
        ],
        // 定制表情包tag修改
        self::API_EMOTICONS_TAG_UPDATE => [
            'path' => '/go/internal/cms/updateEmoticonsTag',
            'method' => 'post',
        ],
        // 定制表情包tag删除
        self::API_EMOTICONS_TAG_DELETE => [
            'path' => '/go/internal/cms/deletedEmoticonsTag',
            'method' => 'post',
        ],
        // 定制表情包素材添加
        self::API_EMOTICONS_META_CREATE => [
            'path' => '/go/internal/cms/createEmoticonsMeta',
            'method' => 'post',
        ],
        // 定制表情包素材修改
        self::API_EMOTICONS_META_UPDATE => [
            'path' => '/go/internal/cms/updateEmoticonsMeta',
            'method' => 'post',
        ],
        // 定制表情包素材删除
        self::API_EMOTICONS_META_DELETE => [
            'path' => '/go/internal/cms/deletedEmoticonsMeta',
            'method' => 'post',
        ],
        // 定制表情包添加
        self::API_EMOTICONS_CREATE => [
            'path' => '/go/internal/cms/createEmoticons',
            'method' => 'post',
        ],
        // 定制表情包修改
        self::API_EMOTICONS_UPDATE => [
            'path' => '/go/internal/cms/updateEmoticons',
            'method' => 'post',
        ],
        // 定制表情包删除
        self::API_EMOTICONS_DELETE => [
            'path' => '/go/internal/cms/deletedEmoticons',
            'method' => 'post',
        ],
        // 定制表情包上下架
        self::API_EMOTICONS_DOWN => [
            'path' => '/go/internal/cms/upOrDownEmoticons',
            'method' => 'post',
        ],
        // 点赞素材添加
        self::API_LIKE_ICON_CREATE => [
            'path' => 'go/internal/cms/createLikeIcon',
            'method' => 'post',
        ],
        // 点赞素材修改
        self::API_LIKE_ICON_UPDATE => [
            'path' => 'go/internal/cms/editLikeIcon',
            'method' => 'post',
        ],
        // 站外链接跳转大区开关
        self::API_SET_URL_LINK_SWITCH => [
            'path' => '/go/internal/opconfig/setUrlLinkSwitch',
            'method' => 'post',
        ],
        // crash 大区开关
        self::API_ROCKET_CRASH_SWITCH => [
            'path' => '/go/internal/opconfig/setRocketCrashSwitch',
            'method' => 'post',
        ],
        // crash 参数配置
        self::API_ROCKET_CRASH_CONFIG => [
            'path' => '/go/internal/opconfig/setRocketCrashConfig',
            'method' => 'post',
        ],
        // crash 调控配置参数列表
        self::API_ROCKET_CRASH_LIMIT_CONFIG_LIST => [
            'path' => '/go/internal/opconfig/getRocketCrashLimitConfig',
            'method' => 'post',
        ],
        // crash 新增调控配置参数
        self::API_ROCKET_CRASH_LIMIT_CONFIG_ADD => [
            'path' => '/go/internal/opconfig/addRocketCrashLimitConfig',
            'method' => 'post',
        ],
        // crash 编辑调控配置参数
        self::API_ROCKET_CRASH_LIMIT_CONFIG_EDIT => [
            'path' => '/go/internal/opconfig/editRocketCrashLimitConfig',
            'method' => 'post',
        ],
        // crash 新增爆炸预期表列表
        self::API_ROCKET_CRASH_CONFIG_TIME_ODDS_LIST => [
            'path' => '/go/internal/opconfig/getRocketCrashTimeOdds',
            'method' => 'post',
        ],
        // crash 新增爆炸预期表数据
        self::API_ROCKET_CRASH_CONFIG_TIME_ODDS_ADD => [
            'path' => '/go/internal/opconfig/addRocketCrashTimeOdds',
            'method' => 'post',
        ],
        // crash 编辑爆炸预期表数据
        self::API_ROCKET_CRASH_CONFIG_TIME_ODDS_EDIT => [
            'path' => '/go/internal/opconfig/editRocketCrashTimeOdds',
            'method' => 'post',
        ],
        //表情包下发列表
        self::API_EMOTICONS_REWARD_LIST => [
            'path' => '/go/internal/cms/rewardEmoticonsList',
            'method' => 'post',
        ],
        self::API_EMOTICONS_REWARD_SEARCH_LIST => [
            'path' => '/go/internal/cms/rewardOperationList',
            'method' => 'post',
        ],
        self::API_EMOTICONS_REWARD_ADD => [
            'path' => '/go/internal/cms/rewardEmoticons',
            'method' => 'post',
        ],
        self::API_EMOTICONS_REWARD_REDUCE => [
            'path' => '/go/internal/cms/reduceEmoticonsTime',
            'method' => 'post',
        ],
        self::API_CREATE_CUSTOMIZED_EMOTICON => [
            'path' => '/go/internal/cms/createCustomizedEmoticon',
            'method' => 'post',
        ],
        self::API_UPDATE_CUSTOMIZED_EMOTICON => [
            'path' => '/go/internal/cms/updateCustomizedEmoticon',
            'method' => 'post',
        ],
        self::API_QUERY_CUSTOMIZED_EMOTICON => [
            'path' => '/go/internal/cms/queryCustomizedEmoticon',
            'method' => 'post',
        ],
        self::API_CREATE_CUSTOMIZED_EMOTICON_REWARD => [
            'path' => '/go/internal/cms/createCustomizedEmoticonReward',
            'method' => 'post',
        ],
        self::API_QUERY_CUSTOMIZED_EMOTICON_REWARD => [
            'path' => '/go/internal/cms/queryCustomizedEmoticonReward',
            'method' => 'post',
        ],
        self::API_DEACTIVATE_CUSTOMIZED_EMOTICON_REWARD => [
            'path' => '/go/internal/cms/deactivateCustomizedEmoticonReward',
            'method' => 'post',
        ],
        self::API_UPDATE_ONEPK_OBJ => [
            'path' => '/go/internal/activity/updateOnepkObj',
            'method' => 'post',
        ],
        self::API_SET_GIFT_WALL_CONFIG => [
            'path' => 'go/internal/cms/setGiftWallConfig',
            'method' => 'post',
        ],
        self::API_GET_GIFT_WALL_CONFIG => [
            'path' => 'go/internal/cms/getGiftWallConfig',
            'method' => 'post',
        ],
        self::API_SET_GIFT_WALL_WEEK_CONFIG => [
            'path' => 'go/internal/cms/setWeekConfig',
            'method' => 'post',
        ],
        self::API_GET_GIFT_WALL_WEEK_CONFIG => [
            'path' => 'go/internal/cms/getWeekConfig',
            'method' => 'post',
        ],
        self::API_GET_KV => [
            'path' => 'go/internal/cms/getKV',
            'method' => 'post',
        ],
        self::API_SET_KV => [
            'path' => 'go/internal/cms/setKV',
            'method' => 'post',
        ],
        self::API_GET_KV_HISTORY => [
            'path' => 'go/internal/cms/getKVHistory',
            'method' => 'post',
        ],
        self::API_TAROT_ODDS_LIST => [
            'path' => 'go/internal/cms/tarotOddsList',
            'method' => 'post',
        ],
        self::API_TAROT_ODDS_EDIT => [
            'path' => 'go/internal/cms/editTarotOdds',
            'method' => 'post',
        ],
        self::API_TAROT_TOTAL_LIMIT_CONFIG_LIST => [
            'path' => 'go/internal/cms/getTarotTotalLimitConfig',
            'method' => 'post',
        ],
        self::API_TAROT_TOTAL_LIMIT_CONFIG_ADD => [
            'path' => 'go/internal/cms/addTarotTotalLimitConfig',
            'method' => 'post',
        ],
        self::API_TAROT_TOTAL_LIMIT_CONFIG_EDIT => [
            'path' => 'go/internal/cms/editTarotTotalLimitConfig',
            'method' => 'post',
        ],
        self::API_TAROT_CONTRIBUTION_LIMIT_CONFIG_ADD => [
            'path' => 'go/internal/cms/addTarotContributionLimit',
            'method' => 'post',
        ],
        self::API_TAROT_CONTRIBUTION_LIMIT_CONFIG_EDIT => [
            'path' => 'go/internal/cms/editTarotContributionLimit',
            'method' => 'post',
        ],
        self::API_TAROT_CONTRIBUTION_LIMIT_CONFIG_LIST => [
            'path' => 'go/internal/cms/getTarotContributionLimit',
            'method' => 'post',
        ],
        self::API_ACT_WHEEL_LOTTERY_SET_AWARD => [
            'path' => 'go/internal/activity/actWheelLotterySetAward',
            'method' => 'post',
        ],
        self::API_ACT_WHEEL_LOTTERY_GET_WEIGHT_INFO => [
            'path' => 'go/internal/activity/actWheelLotteryGetWeightInfo',
            'method' => 'post',
        ],
        self::API_H5_RESOURCE_LIST => [
            'path' => 'go/internal/cms/queryResourceList',
            'method' => 'post',
        ],
        self::API_H5_RESOURCE_ADD => [
            'path' => 'go/internal/cms/createH5Resource',
            'method' => 'post',
        ],
        self::API_H5_RESOURCE_EDIT => [
            'path' => 'go/internal/cms/editH5Resource',
            'method' => 'post',
        ],
        self::API_PROP_CARD_ADD => [
            'path' => '/go/internal/cms/addPropCard',
            'method' => 'post',
        ],
        self::API_PROP_CARD_EDIT => [
            'path' => '/go/internal/cms/editPropCard',
            'method' => 'post',
        ],
        self::API_PROP_CARD_CONFIG_ADD => [
            'path' => '/go/internal/cms/addPropCardConfig',
            'method' => 'post',
        ],
        self::API_PROP_CARD_CONFIG_EDIT => [
            'path' => '/go/internal/cms/editPropCardConfig',
            'method' => 'post',
        ],
        self::API_REPORT_LIST => [
            'path' => '/go/internal/cms/reportList',
            'method' => 'post',
        ],
        self::API_BAN_USER => [
            'path' => '/go/internal/cms/banUser',
            'method' => 'post',
        ],
        self::API_BAN_LOG => [
            'path' => '/go/internal/cms/banUserLog',
            'method' => 'post',
        ],
        self::API_GET_USER_DEVICE_INFO => [
            'path' => '/go/internal/cms/getUserDeviceInfo',
            'method' => 'post',
        ],
        self::API_USER_FORBIDDEN_USER => [
            'path' => 'go/internal/cms/forbiddenUser',
            'method' => 'post',
        ],
        self::API_USER_VIP_LEVEL => [
            'path' => 'go/internal/cms/getUserVip',
            'method' => 'post',
        ],
        self::API_HORSE_VALUE_LIST => [
            'path' => '/go/internal/cms/queryGameContributionLimitConfigList',
            'method' => 'post',
        ],
        self::API_HORSE_VALUE_ADD => [
            'path' => '/go/internal/cms/addGameContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_HORSE_VALUE_EDIT => [
            'path' => '/go/internal/cms/editGameContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_GAME_TOTAL_LIMIT_CONFIG_LIST => [
            'path' => '/go/internal/cms/queryGameTotalLimitConfigList',
            'method' => 'post',
        ],
        self::API_GAME_TOTAL_LIMIT_CONFIG_ADD => [
            'path' => '/go/internal/cms/addGameTotalLimitConfig',
            'method' => 'post',
        ],
        self::API_GAME_TOTAL_LIMIT_CONFIG_EDIT => [
            'path' => '/go/internal/cms/editGameTotalLimitConfig',
            'method' => 'post',
        ],
        self::API_GAME_CONTRIBUTION_LIMIT_CONFIG_LIST => [
            'path' => '/go/internal/cms/queryGameContributionLimitConfigList',
            'method' => 'post',
        ],
        self::API_GAME_CONTRIBUTION_LIMIT_CONFIG_ADD => [
            'path' => '/go/internal/cms/addGameContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_GAME_CONTRIBUTION_LIMIT_CONFIG_EDIT => [
            'path' => '/go/internal/cms/editGameContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_COMMODITY_SEND => [
            'path' => 'go/internal/cms/sendCommodity',
            'method' => 'post',
        ],
        self::API_INTIMATE_RELATION_PAY_CONFIG_EDIT => [
            'path' => '/go/internal/cms/editIntimateRelationPayConfig',
            'method' => 'post',
        ],
        self::API_DIAMOND_LIST => [
            'path' => '/go/internal/activity/getDiamondList',
            'method' => 'post',
        ],
        // 发放解封卡
        self::API_PROP_CARD_SEND => [
            'path' => '/go/internal/cms/sendPropCard',
            'method' => 'post',
        ],
        // 回收解封卡
        self::API_PROP_CARD_REMOVE => [
            'path' => '/go/internal/cms/recyclePropCard',
            'method' => 'post',
        ],
        // 家族
        self::API_REMOVE_FAMILY_MEMBER => [
            'path' => '/go/internal/family/kickOutFamilyMember?format=json',
            'method' => 'post',
        ],
        self::API_MODIFY_FAMILY => [
            'path' => '/go/internal/family/setFamilyData?format=json',
            'method' => 'post',
        ],
        self::API_SET_FAMILY_LV => [
            'path' => '/go/internal/family/setFamilyLv?format=json',
            'method' => 'post',
        ],
        self::API_DISMISS_FAMILY => [
            'path' => '/go/internal/family/dissloveFamily?format=json',
            'method' => 'post',
        ],
        // 礼包人群发放
        self::API_AGENCY_HUNTER_GIFT_BAG_BY_USER_TYPE_BATCH => [
            'path' => '/go/internal/cms/batchAgencyHunterGiftBagByUserType',
            'method' => 'post',
        ],
        self::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_LIST => [
            'path' => '/go/internal/cms/querySlotContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_ADD => [
            'path' => '/go/internal/cms/addSlotContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_EDIT => [
            'path' => '/go/internal/cms/editSlotContributionLimitConfig',
            'method' => 'post',
        ],
        self::API_SLOT_CONTRIBUTION_LIMIT_CONFIG_TEST => [
            'path' => '/go/internal/cms/simulateBet',
            'method' => 'post',
        ],
        self::API_SLOT_ITEM_WEIGHT_LIST => [
            'path' => '/go/internal/cms/querySlotItemWeight',
            'method' => 'post',
        ],
        self::API_SLOT_ITEM_WEIGHT_EDIT => [
            'path' => '/go/internal/cms/editSlotItemWeight',
            'method' => 'post',
        ],
        self::API_GREEDY_SKIN_LIST => [
            'path' => '/go/internal/opconfig/queryGreedySkinList',
            'method' => 'post',
        ],
        self::API_GREEDY_SKIN_ADD => [
            'path' => '/go/internal/opconfig/createGreedySkin',
            'method' => 'post',
        ],
        self::API_GREEDY_SKIN_EDIT => [
            'path' => '/go/internal/opconfig/editGreedySkin',
            'method' => 'post',
        ],
        self::API_GREEDY_SKIN_DEL => [
            'path' => '/go/internal/opconfig/delGreedySkin',
            'method' => 'post',
        ],
        self::API_GREEDY_SKIN_EXPIRE => [
            'path' => '/go/internal/opconfig/expireGreedySkin',
            'method' => 'post',
        ],
        self::API_MULTI_ANCHOR_AWARD_CONFIG_LIST => [
            'path' => '/go/internal/cms/queryMultiAnchorAwardConfigList',
            'method' => 'post',
        ],
        self::API_MULTI_ANCHOR_AWARD_CONFIG_UPDATE => [
            'path' => '/go/internal/cms/updateMultiAnchorAwardConfig',
            'method' => 'post',
        ],
        self::API_MULTI_ANCHOR_AWARD_CONFIG_GET => [
            'path' => '/go/internal/cms/getMultiAnchorAwardConfig',
            'method' => 'post',
        ],
        self::API_GREEDY_BOX_ODDS_LIST => [
            'path'   => '/go/internal/cms/queryGameItemOddsList',
            'method' => 'post',
        ],
        self::API_GREEDY_BOX_ODDS_EDIT => [
            'path'   => '/go/internal/cms/editGameItemOdds',
            'method' => 'post',
        ],
        self::API_VIDEO_LIVE_STOP => [
            'path'   => '/go/internal/cms/adminClose',
            'method' => 'post',
        ],
        self::API_FORBID_ROOM => [
            'path' => 'go/internal/cms/banRoom',
            'method' => 'post',
        ],
        self::API_UNFORBID_ROOM => [
            'path' => 'go/internal/cms/unbanRoom',
            'method' => 'post',
        ],
        self::API_ROOM_COVER_CHANGE => [
            'path' => '/go/internal/cms/changeRoomIcon',
            'method' => 'post',
        ],
        self::API_ROOM_CLOSE => [
            'path' => '/go/internal/cms/closeRoom',
            'method' => 'post',
        ],
        self::API_ACT_HONOUR_WALL_ADD => [
            'path' => 'go/internal/cms/createActHonourWall',
            'method' => 'post',
        ],
        self::API_ACT_HONOUR_WALL_EDIT => [
            'path' => 'go/internal/cms/editActHonourWall',
            'method' => 'post',
        ],
        self::API_ACT_HONOUR_WALL_DEL => [
            'path' => 'go/internal/cms/delActHonourWall',
            'method' => 'post',
        ],
        self::API_BIND_MOBILE => [
            'path' => '/go/internal/account/bindMobile',
            'method' => 'post',
        ],
        self::API_UPDATE_USER_COUNTRY => [
            'path'   => '/go/internal/account/reLocateUserCountry',
            'method' => 'post',
        ],
        self::API_OPERATE_USER_VIP => [
            'path' => '/go/internal/cms/operateUserVip',
            'method' => 'post',
        ],

        self::API_PK_PROP_CARD_FIRST_GIFT_CONFIG_UPDATE => [
            'path' => 'go/internal/cms/updatePkPropCardFirstGiftConfig',
            'method' => 'post',
        ],
        //设置炫彩配置
        self::API_NAME_ID_LIGHTING_CONFIG_SET => [
            'path' => '/gk/v1/item/lighting/internal/setNameIdLightingConfig',
            'method' => 'post',
        ],
        //查询炫彩配置
        self::API_NAME_ID_LIGHTING_CONFIG_LIST => [
            'path' => '/gk/v1/item/lighting/internal/listNameIdLightingConfig',
            'method' => 'post',
        ],
        //查询炫彩下发记录
        self::API_NAME_ID_LIGHTING_LOG_LIST => [
            'path' => '/gk/v1/item/lighting/internal/listNameIdLightingLog',
            'method' => 'post',
        ],
        //炫彩下发
        self::API_NAME_ID_LIGHTING_ADD => [
            'path' => '/gk/v1/item/lighting/internal/addNameIdLighting',
            'method' => 'post',
        ],
        //查询用户炫彩资源
        self::API_USER_NAME_ID_LIGHTING_LIST => [
            'path' => '/gk/v1/item/lighting/internal/listUserNameIdLighting',
            'method' => 'post',
        ],
        //失效用户炫彩资源
        self::API_USER_NAME_ID_LIGHTING_INVALID => [
            'path' => '/gk/v1/item/lighting/internal/invalidUserNameIdLighting',
            'method' => 'post',
        ],


        //荣誉等级配置列表
        self::API_HONOR_LEVEL_CONFIG_LIST   => [
            'path'   => '/go/internal/honor_level/configList?format=json',
            'method' => 'post',
        ],
        //荣誉等级配置创建
        self::API_HONOR_LEVEL_CONFIG_CREATE => [
            'path'   => '/go/internal/honor_level/configCreate?format=json',
            'method' => 'post',
        ],
        //荣誉等级配置更新
        self::API_HONOR_LEVEL_CONFIG_UPDATE => [
            'path'   => '/go/internal/honor_level/configUpdate?format=json',
            'method' => 'post',
        ],
        //根据入参等级获取对应的荣誉等级配置
        self::API_HONOR_LEVEL_CONFIG_GET    => [
            'path'   => '/go/internal/honor_level/getConfig?format=json',
            'method' => 'post',
        ],
        //荣誉等级下发记录列表
        self::API_HONOR_LEVEL_SEND_LIST     => [
            'path'   => '/go/internal/honor_level/sendList?format=json',
            'method' => 'post',
        ],
        //荣誉等级下发接口(单个)
        self::API_HONOR_LEVEL_SEND          => [
            'path'   => '/go/internal/honor_level/send?format=json',
            'method' => 'post',
        ],
        //荣誉等级批量下发
        self::API_HONOR_LEVEL_SEND_BATCH    => [
            'path'   => '/go/internal/honor_level/batchSend?format=json',
            'method' => 'post',
        ],

        //用户荣誉等级管理列表
        self::API_HONOR_LEVEL_MANAGE_LIST   => [
            'path'   => '/go/internal/honor_level/userHonorLevelManageList?format=json',
            'method' => 'post',
        ],
        //失效用户的荣誉等级
        self::API_HONOR_LEVEL_DISABLE       => [
            'path'   => '/go/internal/honor_level/userHonorLevelDisable?format=json',
            'method' => 'post',
        ],
        // 关系增值道具购买记录列表
        self::API_PROP_CARD_BUY_RECORD_LIST => [
            'path'   => '/go/internal/cms/queryPropCardBuyRecord?format=json',
            'method' => 'post',
        ],
        // 多语言配置
        self::API_I18N_LIST_CONFIG => [
            'path' => '/gk/v1/internal/i18n/listConfig',
            'method' => 'get',
        ],
        self::API_I18N_CREATE_CONFIG => [
            'path' => '/gk/v1/internal/i18n/createConfig',
            'method' => 'post',
        ],
        self::API_I18N_GET_CONFIG => [
            'path' => '/gk/v1/internal/i18n/getConfig',
            'method' => 'get',
        ],
        self::API_I18N_BATCH_CREATE_CONFIG => [
            'path' => '/gk/v1/internal/i18n/batchCreateConfig',
            'method' => 'post',
        ],
        self::API_I18N_BATCH_UPDATE_CONFIG => [
            'path' => '/gk/v1/internal/i18n/batchUpdateConfig',
            'method' => 'post',
        ],
        self::API_I18N_UPDATE_CONFIG => [
            'path' => '/gk/v1/internal/i18n/updateConfig',
            'method' => 'post',
        ],
        self::API_I18N_DELETE_CONFIG => [
            'path' => '/gk/v1/internal/i18n/deleteConfig',
            'method' => 'post',
        ],
        self::API_I18N_PUBLISH => [
            'path' => '/gk/v1/internal/i18n/publish',
            'method' => 'get',
        ],
        self::API_APPROVE_GAME_BLACKLIST => [
            'path' => '/go/internal/cms/approveGameBlackList?format=json',
            'method' => 'post',
        ],
        self::API_REJECT_GAME_BLACKLIST => [
            'path' => '/go/internal/cms/rejectGameBlackList?format=json',
            'method' => 'post',
        ],
        // 推荐位配置列表/搜索
        self::API_RECOMMEND_CONFIG_LIST => [
            'path'   => '/go/internal/recommend_config/list?format=json',
            'method' => 'post',
        ],
        // 推荐位配置创建
        self::API_RECOMMEND_CONFIG_CREATE => [
            'path'   => '/go/internal/recommend_config/create?format=json',
            'method' => 'post',
        ],
        // 推荐位配置编辑
        self::API_RECOMMEND_CONFIG_MODIFY => [
            'path'   => '/go/internal/recommend_config/update?format=json',
            'method' => 'post',
        ],
        // 推荐位配置删除
        self::API_RECOMMEND_CONFIG_DELETE => [
            'path'   => '/go/internal/recommend_config/delete?format=json',
            'method' => 'post',
        ],
        // 推荐位配置详情
        self::API_RECOMMEND_CONFIG_DETAIL => [
            'path'   => '/go/internal/recommend_config/detail?format=json',
            'method' => 'post',
        ],
        self::API_ISSUED_ROOM_STEALTH_PRIVILEGE => [
            'path' => '/go/internal/cms/issuedRoomStealthPrivilege',
            'method' => 'post',
        ],
        self::API_SEND_RANK_ANONYMOUS_PRIVILEGE => [
            'path' => '/go/internal/cms/sendRankAnonymous',
            'method' => 'post',
        ],
        self::API_SEND_WEALTH_LV_HIDE_PRIVILEGE => [
            'path' => '/go/internal/cms/sendWealthLvHide',
            'method' => 'post',
        ],
        // 日志查询列表/搜索
        self::API_QUERY_APP_ADMIN_OPERATE_LOG => [
            'path'   => '/go/internal/cms/queryAppAdminOperateLog',
            'method' => 'post',
        ],
        // 日志查询详情
        self::API_QUERY_APP_ADMIN_OPERATE_LOG_DETAIL => [
            'path'   => '/go/internal/cms/queryAppAdminOperateLogDetail',
            'method' => 'post',
        ],
        self::API_CREATE_UPLOAD_LOG_INSTRUCTION => [
            'path' => '/go/internal/cms/createUploadLogInstruction',
            'method' => 'POST'
        ],
        //互动表情素材
        self::API_INTERACTIVE_EMOTICON_CREATE => [
            'path' => '/go/internal/cms/createInteractiveEmoticon',
            'method' => 'POST'
        ],
        self::API_INTERACTIVE_EMOTICON_EDIT => [
            'path' => '/go/internal/cms/editInteractiveEmoticon',
            'method' => 'POST'
        ],
        self::API_INTERACTIVE_EMOTICON_LISTED => [
            'path' => '/go/internal/cms/opInteractiveEmoticonListed',
            'method' => 'POST'
        ],
        //语音房背景音效
        self::API_ROOM_BACKGROUND_SOUND_EFFECT_CREATE => [
            'path' => '/go/internal/cms/soundEffectCreate',
            'method' => 'POST'
        ],
        self::API_ROOM_BACKGROUND_SOUND_EFFECT_EDIT => [
            'path' => '/go/internal/cms/soundEffectUpdate',
            'method' => 'POST'
        ],
        self::API_ROOM_BACKGROUND_SOUND_EFFECT_DEL => [
            'path' => '/go/internal/cms/soundEffectDelete',
            'method' => 'POST'
        ],
    ];
    protected function serviceConfig(): array
    {
        $config = $this->apiConfig;
        $config['options'] = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'connect_timeout' => 5,
            'timeout' => 40,
        ];

        $config['retry'] = [
            'max' => 1,
            'delay' => 100,
        ];

        return $config;
    }

    protected function decode(Response $response = null, $code = 200): array
    {
        if ($response) {
            return [json_decode($response->getBody(), true), $response->getStatusCode()];
        }

        return [null, 500];
    }
}