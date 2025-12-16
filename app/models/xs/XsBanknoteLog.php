<?php

namespace Imee\Models\Xs;

class XsBanknoteLog extends BaseModel
{
    const OP_REASON = [
        0 => 'BILL_TYPE_OP_REASON_START',
        1 => 'BILL_TYPE_OP_REASON_CHARM_EXCHANGE_DIAMOND',   // 魅力兑换钻石
        2 => 'BILL_TYPE_OP_REASON_CHARM_EXCHANGE_BANKNOTE',   // 魅力兑换现金
        3 => 'BILL_TYPE_OP_REASON_BANKNOTE_EXCHANGE_DIAMOND', // 现金兑换钻石
        4 => 'BILL_TYPE_OP_REASON_DIAMOND_EXCHANGE_COIN',   // 钻石兑换金币
        5 => 'BILL_TYPE_OP_REASON_DIAMOND_EXCHANGE_CHIP',   // 钻石兑换筹码
        6 => 'BILL_TYPE_OP_REASON_CHARM_CONVERT_DIAMOND',  // 魅力值转换为钻石
        7 => 'BILL_TYPE_OP_REASON_BANKNOTE_EXCHANGE_DIAMOND_TO_OTHER',  // 现金兑换钻石
        8 => 'BILL_TYPE_OP_REASON_BANKNOTE_EXCHANGE_DIAMOND_FROM_OTHER',   // 现金兑换钻石
        9 => 'BILL_TYPE_OP_REASON_BEAN_CONVERT_DIAMOND',  // 金豆转钻石
        10 => 'BILL_TYPE_OP_REASON_TICK_CLEAN_CHARM',      // 薪资结算扣除
        11 => 'BILL_TYPE_OP_REASON_REFUND_CHARM',         // 退款操作
        12 => 'BILL_TYPE_OP_REASON_ACTIVITY_REWARD_DIAMOND',  // 活动奖励钻石
        13 => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION',  // 官方扣除
        14 => 'BILL_TYPE_OP_BANKNOTE_EXCHANGE_DIAMOND',  // 现金兑换钻石，现金账单
        15 => 'BILL_TYPE_OP_BANKNOTE_EXCHANGE_SELF',  // 提现给自己
        16 => 'BILL_TYPE_OP_BANKNOTE_TRANSFER_OTHER',  // 现金给他人
        17 => 'BILL_TYPE_OP_BANKNOTE_RECEIVE',  // 收到他人转账
        18 => 'BILL_TYPE_OP_BANKNOTE_EXCHANGE_FAILED_REFUND',  // 现金提现失败退还
        19 => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_CHARM', // 官方扣除魅力值
        20 => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_DIAMOND', // 官方扣除钻石
        21 => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_AGENT', // 官方扣除币商钻石
        22 => 'BILL_TYPE_OP_REASON_OFFICIAL_DEDUCTION_BANKNOTE', // 官方扣除薪资
        43 => 'BILL_TYPE_CHAT_CHARM_EXCHANGE_DIAMOND', // 语音房魅力兑换钻石
        44 => 'BILL_TYPE_LIVE_CHARM_EXCHANGE_DIAMOND', // 视频房魅力兑换钻石
        45 => 'BILL_TYPE_CHAT_CHARM_EXCHANGE_BANKNOTE', // 语音房魅力兑换现金
        46 => 'BILL_TYPE_LIVE_CHARM_EXCHANGE_BANKNOTE', // 视频房魅力兑换现金
        65 => 'BILL_TYPE_BACKGROUND_BANKNOTE', // 薪资结算
        66 => 'BILL_TYPE_BACKGROUND_REWARD', // 官方奖励
        140 => 'BILL_TYPE_BANKNOTE_START_WITHDRAW', // 发起薪资代付提现
        141 => 'BILL_TYPE_BANKNOTE_PARTIAL_WITHDRAW', // 部分提现失败退回
        142 => 'BILL_TYPE_BANKNOTE_WITHDRAW', // uid提现
    ];

    const OP_TRANSLATE = [
        0 => '',
        1 => '魅力兑换钻石',
        2 => '魅力兑换现金',
        3 => '现金兑换钻石',
        4 => '钻石兑换金币',
        5 => '钻石兑换筹码',
        6 => '魅力值转换为钻石',
        7 => '现金兑换钻石',
        8 => '现金兑换钻石',
        9 => '金豆转钻石',
        10 => '薪资结算扣除',
        11 => '退款操作',
        12 => '活动奖励钻石',
        13 => '官方扣除',
        14 => '现金兑换钻石，现金账单',
        15 => '提现给自己',
        16 => '现金给他人',
        17 => '收到他人转账',
        18 => '现金提现失败退还',
        19 => '官方扣除魅力值',
        20 => '官方扣除钻石',
        21 => '官方扣除币商钻石',
        22 => '官方扣除薪资',
        43 => '语音房魅力兑换钻石',
        44 => '视频房魅力兑换钻石',
        45 => '语音房魅力兑换现金',
        46 => '视频房魅力兑换现金',
        65 => '薪资结算',
        66 => '官方奖励',
        166 => '现金划转-发放',
        167 => '现金划转-扣减',
    ];
}