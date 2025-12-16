<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\UserContext;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xs\XsUserSafeMobile;
use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xss\XsChatMessageNew;
use Imee\Models\Xsst\XsstUserForbiddenCheck;
use Imee\Models\Xsst\XsstSessionForbiddenReasonLog;
use Imee\Models\Xsst\XsstUserForbiddenReasonLog;
use Imee\Models\Xsst\XsstSessionForbiddenLog;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Exception\Audit\RiskCheckException;
use Imee\Service\Domain\Service\Risk\RiskUserService;
use Imee\Service\Helper;

/**
 * 封禁用户操作
 */
class UserProcess
{
    use UserInfoTrait;
    private $forbiddenLogModel = null;
    private $userinfo = [];
    private $safeMobile;
    private $stateCfgList = [
        XsUserProfile::DELETE_NORMAL => [
            XsUserProfile::DELETE_CANNOT_SEARCH,
            XsUserProfile::DELETE_FORBID_CHAT,
            XsUserProfile::DELETE_FORBID_LOGIN,
        ],

        XsUserProfile::DELETE_CANNOT_SEARCH => [
            XsUserProfile::DELETE_NORMAL,
            XsUserProfile::DELETE_FORBID_CHAT,
            XsUserProfile::DELETE_FORBID_LOGIN,
        ],

        XsUserProfile::DELETE_FORBID_CHAT => [
            XsUserProfile::DELETE_NORMAL,
            XsUserProfile::DELETE_FORBID_LOGIN,
        ],

        XsUserProfile::DELETE_FORBID_LOGIN => [
            XsUserProfile::DELETE_NORMAL,
            XsUserProfile::DELETE_FORBID_CHAT,
            XsUserProfile::DELETE_FORBID_LOGIN,
        ],
    ];

    private $forbiddenSourceClass = [
        // 'check' => CheckProxy::class,
    ];

    private $forbiddenSourceContext = [
        // 'check' => CheckContext::class,
    ];

    private $configFunc = [
        XsUserProfile::DELETE_NORMAL => 'normal',
        XsUserProfile::DELETE_CANNOT_SEARCH => 'cannotSearch',
        XsUserProfile::DELETE_FORBID_CHAT => 'forbidChat',
        XsUserProfile::DELETE_FORBID_LOGIN => 'forbidLogin',
    ];

    protected $context;

    public function __construct(UserContext $context)
    {
        $this->context = $context;
    }

    private function verify()
    {
        if ($this->context->opCheck) {
            $forbiddenLogModel = XsstUserForbiddenCheck::findFirst([
                'conditions' => 'log_id = :log_id:',
                'bind' => [
                    'log_id' => $this->context->opCheck
                ]
            ]);
            if ($forbiddenLogModel) {
                list($code, $msg) = RiskCheckException::DATA_DONE_ERROR;
                throw new RiskCheckException($msg, $code);
            }
        }

        //判断用户存在否
        $userinfoMap = $this->getUserInfoModel([$this->context->uid])->handle();
        if (!isset($userinfoMap[$this->context->uid])) {
            list($code, $msg) = RiskCheckException::USER_NOEXIST_ERROR;
            throw new RiskCheckException($msg, $code);
        }
        $this->userinfo = $userinfoMap[$this->context->uid];

        if (!in_array($this->context->deleted, $this->stateCfgList[$this->userinfo['deleted']])) {
            //当前状态不允许被扭转到指定状态
            list($code, $msg) = RiskCheckException::CHANGE_STATUS_ERROR;
            throw new RiskCheckException($msg, $code);
        }


        if ($this->context->deleted > XsUserProfile::DELETE_NORMAL &&
            $this->context->deleted < XsUserProfile::DELETE_FORBID_LOGIN && $this->context->macneed > 0) {
            list($code, $msg) = RiskCheckException::CURRENT_STATE_NOALLOW_HANDLE_ERROR;
            throw new RiskCheckException($msg, $code);
        }

        if ($this->context->macneedphone == 1) {
            $safeMobileModel = XsUserSafeMobile::findFirst([
                'conditions' => 'uid = :uid:',
                'bind' => [
                    'uid' => $this->context->uid
                ]
            ]);
            if (!$safeMobileModel) {
                list($code, $msg) = RiskCheckException::USER_NO_SAFE_MOBILE_ERROR;
                throw new RiskCheckException($msg, $code);
            }
            $this->safeMobile = $safeMobileModel->mobile;
        }
    }

    public function handle()
    {
        //check数据
        $this->verify();
        $mac = '';
        $imei = '';
        $did = '';
        if ($this->context->mac && ($this->context->deleted == 0 || $this->context->macneed > 0)) {
            $mac = $this->context->mac;
        }
        if ($this->context->imei && ($this->context->deleted == 0 || $this->context->macneed > 0)) {
            $imei = $this->context->imei;
        }
        if ($this->context->did && ($this->context->deleted == 0 || $this->context->macneed > 0)) {
            $did = $this->context->did;
        }

        $reason = $this->_translate($this->context->uid, $this->context->reason);

        if ($this->context->macneedphone == 1) {
            $this->macNeedPhonehandle($mac, $imei, $did);
        } else {
            $this->macNoNeedPhonehandle($mac, $imei, $did, $reason);
        }

        if ($this->context->source) {
            if ($this->context->deleted == 3 && $this->context->duration > 0) {
                $this->_forbiddenLog(array(
                    'type' => 2,
                    'op_id' => Helper::getSystemUid(),
                    'handle_id' => $this->context->uid,
                    'source' => $this->context->source,
                    'language' => $this->context->language
                ));
            }
            //会话封禁原因记录
            XsstSessionForbiddenReasonLog::saveRows(array(
                'reason' => $reason,
                'source' => $this->context->source,
                'uid' => $this->context->uid,
                'language' => $this->context->language
            ));
        }

        //风险用户的封禁日志,同source
        if ($this->context->forbiddenSource == XsUserForbiddenLog::FORBIDDEN_SOURCE_USER_REAUDIT && $this->context->deleted == 3 && $this->context->duration > 0) {
            RiskUserService::record(
                $this->context->uid,
                Helper::getSystemUid(),
                1,
                $this->context->ruleType,
                $this->context->duration,
                $reason,
                $this->context->language
            );
        }

        if (!empty($op_check)) { //存在op check 就是封禁复查审核
            $status = $this->context->deleted <= 1 ? 3 : 2;
            XsstUserForbiddenCheck::userForbiddenModify(Helper::getSystemUid(), $this->context->uid, $op_check, $status, $reason);
        }
        if (empty($language)) {
            $user = XsUserSettings::findFirst($this->context->uid);
            if ($user) {
                $language = $user->language;
            }
        }

        //有点冗余，其实可以让波哥那边处理
        XsstUserForbiddenReasonLog::saveRows(array(
            'reason' => $this->context->reason,
            'uid' => $this->context->uid,
            'language' => $language,
            'op_id' => Helper::getSystemUid(),
        ));

        //境外诈骗团伙
        if ($this->context->reason == "境外诈骗团伙") {
            //查出与用户聊过天的（中文区用户）
            $res = XsChatMessageNew::find(array(
                "columns" => "DISTINCT to_user_id",
                "conditions" => "from_user_id = :from_user_id:",
                "bind" => array(
                    "from_user_id" => $this->context->uid
                )
            ))->toArray();
            if (!empty($res)) {
                $profile = XsUserProfile::findFirst($this->context->uid);
                foreach ($res as $v) {
                    $language = XsUserSettings::getLanguage($v['to_user_id']);
                    if ($language == 'zh_tw' || $language == 'zh_cn') {
                        $msg = "亲爱的用户，我们通过系统识别，您的好友/联系人 【@{$profile->name} @ID：{$this->context->uid}】可能是境外诈骗团伙成员，Partying特此提醒您，在聊天交友过程中，谨慎加第三方聊天软件，不要进行任何第三方资金转账，充值操作（包括虚拟货币、数字货币、彩票、博彩等形式）。建议增强自我防范意识，避免上当受骗！";
                        $this->_sendMessage($v['to_user_id'], $msg);
                    }
                }
            }
        }
    }

    private function _sendMessage($uid, $message = '', $from = 0)
    {
        if ($uid < 1 || !$message) {
            return false;
        }
        NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
            'cmd' => 'system.message',
            'data' => array(
                'from' => $from,
                'uid' => $uid,
                'message' => $message
            )
        ));
        return true;
    }

    private function macNeedPhonehandle($mac, $imei, $did)
    {
        $uidArray = XsUserSafeMobile::findUids($this->safeMobile);
        if (empty($uidArray)) {
            return;
        }
        foreach ($uidArray as $k => $v) {
            $reason = $this->_translate($v, $this->context->reason);
            $tmpData = array(
                'uid' => intval($v),
                'deleted' => $this->context->deleted,
                'duration' => $this->context->duration,
                'reason' => $reason,
                'mark' => '（因为' . $this->context->uid . '安全手机号关联封禁)' . $this->context->mark,
                'op' => Helper::getSystemUid(),
                'mac' => '',
                'imei' => '',
                'did' =>'',
                'device' => 0,
                'check' => 0,
                'source' => $this->context->forbiddenSource
            );

            if ($v == $this->context->uid) {
                $tmpData['mark'] = $this->context->mark;
                $tmpData['mac'] = $mac;
                $tmpData['imei'] = $imei;
                $tmpData['did'] = $did;
                $tmpData['device'] = $this->context->macneed > 0 ? 1 : 0;
                $tmpData['check'] = $this->context->opCheck > 0 ? 1 : 0 ;
            }

            NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
                'cmd' => 'forbidden',
                'data' => $tmpData
            ));
        }
    }

    private function macNoNeedPhonehandle($mac, $imei, $did, $reason)
    {
        NsqClient::publish(NsqConstant::TOPIC_XS_CMD, array(
            'cmd' => 'forbidden',
            'data' => array(
                'uid' => $this->context->uid,
                'deleted' => $this->context->deleted,
                'duration' =>$this->context->duration,
                'reason' => $reason,
                'mark' => $this->context->mark,
                'op' => Helper::getSystemUid(),
                'mac' => $mac,
                'imei' => $imei,
                'did' => $did,
                'device' => $this->context->macneed > 0 ? 1 : 0,
                'check' => $this->context->opCheck > 0 ? 1 : 0,
                'source' => $this->context->forbiddenSource
            )
        ));
    }

    /**
     * 根据用户的地区翻译成对应的言语
     * @param $uid
     * @param $text
     */
    protected function _translate($uid, $text)
    {
        $uarea = XsUserSettings::findFirst($uid);
        $lang  = 'zh_cn';
        if ($uarea) {
            $lang = $uarea->language;
        }
        return __T($text, [], strtolower($lang));
    }

    protected function _forbiddenLog($data)
    {
        $user = XsUserProfile::findFirstValue($data['handle_id']);
        if (empty($user)) {
            return false;
        }
        $data['new_user'] = 0;
        if ((time() - $user->dateline) <= 7 * 86400) {
            $data['new_user'] =  1;
        }
        $data['role'] = $user->role;
        return XsstSessionForbiddenLog::saveRows($data);
    }
}
