<?php
/**
 * 后台操作日志记录
 */

namespace Imee\Models\Xsst;

class BmsOperateLog extends BaseModel
{
    protected static $primaryKey = 'id';
    protected $allowEmptyStringArr = ['before_json', 'after_json'];

    const TYPE_OPERATE_LOG = 0;//运营后台操作日志
    const TYPE_CLI_LOG = 1;

    const ACTION_ADD = 0;//添加
    const ACTION_UPDATE = 1;//修改
    const ACTION_DEL = 2;//删除
    const ACTION_REVIEW = 3;//审核

    //操作日志来源展示
    public static $modelMapping = [
        'CmsUser'                   => 'CMS用户管理',
        'xs_bigarea_red_packet'     => '红包玩法配置',
        'xs_lucky_wheel_config'     => 'lucky wheel玩法配置',
        'background_goods'          => '背景商品管理',
        'chatroom_background'       => '背景管理',
        'background_knapsack'       => '背景背包查询',
        'background_send'           => '背景发放',
        'XsstUidWhiteList'          => '用户类白名单',
        'game_limit'                => '预期玩法可见配置',
        'diceplayodds'              => 'Dice预期配置',
        'diceplayparams'            => 'Dice参数配置',
        'diceplayregionswitch'      => 'Dice大区开关',
        'certificationmaterials'    => '认证素材管理',
        'quickgiftconfig'           => '快捷礼物配置',
        'teenpattiplayscale'        => 'Teen Patti玩法档位配置',
        'teenpattiplaygear'         => 'Teen Patti分成比例配置',
        'teenpattiplayregionswitch' => 'Teen Patti玩法大区开关',
        'pushcontent'               => 'Push文案管理',
        'pushplan'                  => 'Push推送计划',
        'pushrule'                  => 'Push规则配置',
        'greedyboxparams'           => 'GreedyStar 宝箱参数配置',
        'greedyboxregionswitch'     => 'Greedystar 宝箱开关',
        'greedyparams'              => 'GreedyStar 参数配置',
        'greedyplayregionswitch'    => 'GreedyStar 玩法大区开关',
        'greedyweight'              => 'GreedyStar 权重预期配置',
        'logindevicewhitelist'      => '登陆设备白名单',
        'xs_customize_pretty_style' => '靓号管理-自选靓号类型',
        'xs_commodity_pretty_info'  => '靓号管理-靓号商城管理',
        'xs_user_customize_pretty'  => '靓号管理-自选靓号发放管理',
        'roomrocketregionswitch'    => '爆火箭玩法大区开关',
        'roomrocketconfig'          => '爆火箭任务配置',
        'homepagepop'               => '首页弹窗',
        'privatemsgpurviewlevel'    => '私信权限等级管理',
        'coderedpacketswitch'       => '口令红包大区开关',
        'coderedpacketnum'          => '口令红包数量配置',
        'redpacketbigarea'          => '普通红包大区开关',
        'redpacketnum'              => '普通红包数量配置',
        'loginipblacklist'          => '登陆设备IP黑名单',
        'paypass'                   => '支付密码管理',
        'custombgccardpack'         => '自定义背景卡背包',
        'custombgccardsend'         => '自定义背景卡下发',
        'custombgccardswitch'       => '自定义房间背景大区开关',
        'showoriginuidswitch'       => '是否展示原始ID',
        'guestrelationjumpswitch'   => '客态个人主页粉丝跳转管理',
        'greedylevel'               => 'Greedy 等级大区配置',
        'sicbolevel'                => 'SicBo 等级大区配置',
        'slotlevel'                 => 'Slot 等级大区配置',
        'probabilityplaying'        => '预期玩法可见配置',
        'welcomehuntergift'         => '迎新礼包管理-礼包下发',
        'welcomegiftbag'            => '迎新礼包管理-礼包配置',
        'stickerlist'               => '特效列表管理',
        'stickerresource'           => '特效素材管理',
        'dragontigerlevel'          => 'Dragon Tiger等级大区配置',
        'dragontigerodds'           => '龙虎斗预期配置',
        'dragontigerparams'         => '龙虎斗参数配置',
        'dragontigerregionswitch'   => '龙虎斗大区开关',
        'roomskinconfig'            => '房间皮肤配置',
        'roomskinsearch'            => '房间皮肤查询',
        'videoquickgift'            => '视频房快捷礼物配置',
        'registerloginlimit'        => '注册登录账号数限制管理',
        'devicewhitelist'           => '设备类白名单',
        'customgiftswitch'          => '定制礼物玩法大区开关',
        'openscreen'                => '开屏页配置',
        'gamesystemweb'             => '游戏配置',
        'activitytaskgameplay'      => '任务玩法',
        'gamecouponissued'          => '游戏优惠券下发',
        'customstickerregionswitch' => '贴纸大区开关',
        'customstickerresource'     => '贴纸素材管理',
        'customstickerlist'         => '贴纸列表管理',
        'payactivitymanage'         => '充值活动管理',
        'payactivityperiodmanage'   => '活动周期配置',
        'payactivityaward'          => '配置档位及奖励',
        'userface'                  => '主播人脸库',
        'userfaceaudit'             => '人脸审核记录',
        'bigareaboxconfig'          => 'Greedy 大区宝箱配置',
        'gameplayblacklist'         => '玩法黑名单',
        'roomtopcardconfig'         => '置顶卡配置',
        'roomtopcardsearch'         => '置顶卡查询',
        'luckyfruitlevel'           => 'Lucky Fruit等级大区配置',
        'luckyfruitparams'          => 'Lucky Fruit参数配置',
        'luckyfruitregionswitch'    => 'Lucky Fruit大区开关',
        'luckyfruitweight'          => 'Lucky Fruit权重配置',
        'luckyfruitweighttab'       => 'Lucky Fruit权重Tab配置',
        'roomtopcardsend'           => '置顶卡发放',
        'emoticons'                 => '表情包上架',
        'emoticonsmaterial'         => '表情包素材配置',
        'emoticonstag'              => '表情包标签配置',
        'bigareaspecialboxconfig'   => '白名单用户宝箱掉落配置',
        'roomludoregionswitch'      => '房间内ludo大区开关',
        'roomcarromregionswitch'    => '房间内carrom大区开关',
        'roombilliardregionswitch'  => '房间内billiard大区开关',
        'livevideolikematerial'     => '视频直播点赞素材管理',
        'outlinkjumpregionswitch'   => '站外链接跳转大区开关',
        'outlinkjumpwhitelist'      => '外部链接白名单',
        'giftwallweekconfig'        => '限时礼物设置自动',
        'crashlevel'                => 'Crash Level',
        'crashregionswitch'         => 'Crash Region',
        'crashodds'                 => 'Crash Odds',
        'crashparameters'           => 'Crash Parameters',
        'crashtotal'                => 'Crash Total',
        'crashvalue'                => 'Crash Value',
        'crashovertime'             => 'Crash Overtime',
        'videoconfigfilemanage'     => '视频直播配置文件管理'
    ];

    /**
     * 获取最近的一条操作日志
     * @param $model
     * @param $modelId
     * @return array
     */
    public static function getFirstLogList($model, $modelId): array
    {
        if (!$modelId || !$model) {
            return [];
        }

        if (!is_array($modelId)) {
            $modelId = [$modelId];
        }

        $condition = [];
        $condition[] = ['model', '=', $model];
        $condition[] = ['model_id', 'in', $modelId];
        $data = self::getListByWhere($condition, 'model_id,operate_id,operate_name,created_time', 'id desc');

        $res = [];
        foreach ($data as $val) {
            if (!empty($res[$val['model_id']])) {
                continue;
            }
            $res[$val['model_id']] = $val;
        }

        return $res;
    }

    /**
     * 获取指定操作类型的最近一条日志
     * @param $model
     * @param $modelId
     * @param $action
     * @return array
     */
    public static function getFirstLogListByAction($model, $modelId, $action): array
    {
        if (!$modelId || !$model) {
            return [];
        }

        if (!is_array($modelId)) {
            $modelId = [$modelId];
        }

        $condition = [];
        $condition[] = ['model', '=', $model];
        $condition[] = ['model_id', 'in', $modelId];
        $condition[] = ['action', '=', $action];
        $data = self::getListByWhere($condition, 'model_id,operate_id,operate_name,created_time', 'id desc');

        $res = [];
        foreach ($data as $val) {
            if (!empty($res[$val['model_id']])) {
                continue;
            }
            $res[$val['model_id']] = $val;
        }

        return $res;
    }
}
