<?php

/**
 * Nsq topic配置
 */

namespace Imee\Helper\Constant;

class NsqConstant
{
    // lookup
    const Nsq_Lookup = 'lookup'; // 业务
    const Nsq_Lookup_Admin = 'lookup_admin'; // 后台

    // nsd
    const Nsq = 'nsq'; // 业务
    const Nsq_Admin = 'nsq_admin'; // 后台

    // topic
    const TOPIC_XS_CIRCLE = 'xs.circle';
    const TOPIC_XS_TEXT_SCAN = 'xs.text.scan';
    const TOPIC_XS_ADMIN = 'xs.admin';
    const TOPIC_XS_CMD = 'xs.cmd';
    const TOPIC_XS_DEMO = 'xs.demo';
    const TOPIC_XS_BETA_CRON = 'xs.beta.cron';
    const TOPIC_FMS_BETA_CRON = 'fms.beta.cron';
    const TOPIC_ADMIN_REVIEW = 'admin.review';
    const TOPIC_COMPANY_WECHAT = 'company.wechat';
    const TOPIC_CENSOR = 'censor';
    const TOPIC_CSMS_REVIEW = 'csms.review';
    const TOPIC_CSMS_NSQ = 'csms.nsq';
    const TOPIC_CSMS_AUDIO = 'csms.audio';
    const TOPIC_CSMS_BINLOG = 'csms.binlog';
    const TOPIC_CSMS_CHECK = 'csms.check';
    const TOPIC_RS_CIRCLE_TAG = 'rs.circle.tag.es';
    const TOPIC_XS_CHATROOM_MESSAGE = 'xs.chatroom.message';
    const TOPIC_SUPER_IMAGE = 'super.image';
    const TOPIC_KING_ACTIVITY = 'admin.king_active_msg';
    const TOPIC_ADMIN_DIRTY = 'admin.dirty';                    // 敏感词触发

    const TOPIC_IM_NOTIFY = 'im.notify';
    const TOPIC_XS_CHAT_MESSAGE = 'xs.chat.message';
    const TOPIC_XS_AUTO_CHAT = 'xs.auto.chat';
    const TOPIC_XS_CHAT_MESSAGE_EX = 'xs.chat.message.ex';
    const TOPIC_ADMIN_HTTP_PROXY = 'admin.http.proxy';
    const TOPIC_XS_AUTO_CHAT_OLD = 'xs.auto.chat.old';
    const TOPIC_GID_ACCOUNT = 'binlog.accout.relationship';
    const TOPIC_CHAT_MESSAGE_GROUP = 'xs.chat.message.group';
    const TOPIC_USER_PUSH_MESSAGE = 'xs.user_push_message';
    const TOPIC_XS_LIVE_MESSAGE = 'xs.live.message';
    const TOPIC_ADMIN_IM_MSG = 'admin.im.msg';
    const TOPIC_XSS_NEW_USER_VALID = 'xss.new_user_valid';
    const TOPIC_ADMIN_SERVICE = 'admin.service';
    const TOPIC_XS_CHATROOM_PACKAGE = 'xs.xs_chatroom_package';

    const TOPIC_ADMIN_DLOCALCLICK = 'admin.dlocalclick';
    const TOPIC_USER_COUNTRY = 'user.country';
    const TOPIC_PROPERTY= 'xs.property';

    const TOPIC_XSST_USER_MONEY_DAY = 'xsst.xsst_user_money_day';



    public static $forwardToCircle = array(

        // ==== 业务 ====
        self::TOPIC_RS_CIRCLE_TAG       => self::Nsq,
        self::TOPIC_XS_ADMIN            => self::Nsq,
        self::TOPIC_ADMIN_REVIEW        => self::Nsq,
        self::TOPIC_COMPANY_WECHAT      => self::Nsq,
        self::TOPIC_XS_CIRCLE           => self::Nsq,
        self::TOPIC_XS_TEXT_SCAN        => self::Nsq,
        self::TOPIC_XS_CMD              => self::Nsq,
        self::TOPIC_XS_DEMO             => self::Nsq,
        self::TOPIC_FMS_BETA_CRON       => self::Nsq,
        self::TOPIC_XS_CHATROOM_MESSAGE => self::Nsq,
        self::TOPIC_XS_CHAT_MESSAGE     => self::Nsq,
        self::TOPIC_XS_AUTO_CHAT        => self::Nsq,
        self::TOPIC_XS_CHAT_MESSAGE_EX  => self::Nsq,
        self::TOPIC_ADMIN_DIRTY         => self::Nsq,
        self::TOPIC_XS_LIVE_MESSAGE     => self::Nsq,
        self::TOPIC_ADMIN_SERVICE       => self::Nsq,
        self::TOPIC_USER_COUNTRY        => self::Nsq,
        self::TOPIC_PROPERTY            => self::Nsq,

        // ==== 后台 ====
        self::TOPIC_XS_BETA_CRON        => self::Nsq_Admin,
        self::TOPIC_CENSOR              => self::Nsq_Admin,
        self::TOPIC_CSMS_REVIEW         => self::Nsq_Admin,
        self::TOPIC_CSMS_NSQ            => self::Nsq,
        self::TOPIC_CSMS_AUDIO          => self::Nsq_Admin,
        self::TOPIC_CSMS_BINLOG         => self::Nsq_Admin,
        self::TOPIC_CSMS_CHECK          => self::Nsq_Admin,
        self::TOPIC_SUPER_IMAGE         => self::Nsq,
        self::TOPIC_IM_NOTIFY           => self::Nsq_Admin,
        self::TOPIC_ADMIN_HTTP_PROXY    => self::Nsq_Admin,
        self::TOPIC_XS_AUTO_CHAT_OLD    => self::Nsq_Admin,
        self::TOPIC_KING_ACTIVITY       => self::Nsq_Admin,
        self::TOPIC_GID_ACCOUNT         => self::Nsq_Admin,
        self::TOPIC_CHAT_MESSAGE_GROUP  => self::Nsq_Admin,
        self::TOPIC_USER_PUSH_MESSAGE   => self::Nsq_Admin,
        self::TOPIC_ADMIN_IM_MSG        => self::Nsq_Admin,
        self::TOPIC_XSS_NEW_USER_VALID  => self::Nsq_Admin,
        self::TOPIC_XS_CHATROOM_PACKAGE  => self::Nsq_Admin,
        self::TOPIC_ADMIN_DLOCALCLICK  => self::Nsq_Admin,
        self::TOPIC_XSST_USER_MONEY_DAY => self::Nsq_Admin,
    );
}
