<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\UserCustomize;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Service\Domain\Context\Pretty\UserCustomize\CreateContext;

use Imee\Service\Rpc\PsService;
use Imee\Exception\ApiException;
use Imee\Exception\Operate\PrettyUserCustomizeException;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsUserCustomizePretty;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xs\XsUserPretty;
use Imee\Service\Helper;

/**
 * 新增
 */
class CreateProcess
{
    use UserInfoTrait;
    use CsmsTrait;
    private $context;

    public function __construct(CreateContext $context)
    {
        $this->context = $context;
    }

    private function verify()
    {
        $uids = array_map(function($item) {
            return intval(trim($item));
        }, explode(',', $this->context->uidStr));
        $oriCount = count($uids);
        $uids = array_filter($uids, function ($v) {
            return is_numeric($v);
        });

        if (count($uids) != $oriCount) {
            //报错
            PrettyUserCustomizeException::throwException(PrettyUserCustomizeException::UIDS_RULE_ERROR);
        }
        $uidMap = $this->getUserInfoModel($uids)->handle();

        if (count($uidMap) != $oriCount) {
            //报错
            PrettyUserCustomizeException::throwException(PrettyUserCustomizeException::UID_NOEXIST_ERROR);
        }

        $user = CmsUser::findOne($this->context->adminId);
        // 验证后台用户是否有给用户发放靓号的权限
        $adminBigArea = explode(',', $user['bigarea']);
        $uidBigArea = $this->getUserBigAreaModel($uids);

        // 对比后台用户权限
        $diffBigArea = array_diff(array_values(array_unique($uidBigArea)), $adminBigArea);
        if ($diffBigArea) {
            $bigAreaMap = XsBigarea::$_bigAreaMap;
            $diffBigAreaStr = array_map(function($item) use ($bigAreaMap) {
                return $bigAreaMap[$item] ?? '';
            }, $diffBigArea);

            list($code, $msg) = PrettyUserCustomizeException::BIG_AREA_ERROR;
            $msg = sprintf($msg, '【' .implode(',', $diffBigAreaStr) . '】');
            throw new PrettyUserCustomizeException($msg, $code);
        }

        //类型校验
        $styleModel = XsCustomizePrettyStyle::findOne($this->context->customizePrettyId);
        if (!$styleModel) {
            PrettyUserCustomizeException::throwException(PrettyUserCustomizeException::TYPE_NOEXIST_ERROR);
        }
        if ($styleModel['disabled'] != XsCustomizePrettyStyle::DISABLED_NO) {
            throw new ApiException(ApiException::MSG_ERROR, "该类型【{$styleModel['id']} - {$styleModel['name']}】已被禁用，无法提交");
        }
        
        if (!XsUserPretty::hasLengthPurview($user)) {
            if ($styleModel['short_limit'] > 0 && $styleModel['short_limit'] <= XsUserPretty::PRETTY_LENGTH 
                || $styleModel['ar_short_limit'] > 0 && $styleModel['ar_short_limit'] <= XsUserPretty::PRETTY_LENGTH 
                || $styleModel['tr_short_limit'] > 0 && $styleModel['tr_short_limit'] <= XsUserPretty::PRETTY_LENGTH 
            ) {
                throw new ApiException(ApiException::MSG_ERROR, '你需要申请【1位&2位数靓号】权限，才能发放对应靓号');
            }
        }
        
    }

    public function handle()
    {
        $this->verify();
        $uids = array_map(function($item) {
            return intval(trim($item));
        }, explode(',', $this->context->uidStr));

        $creator = Helper::getAdminName($this->context->adminId);

        $data = [
            'uid_list' => $uids,
            'customize_pretty_id' => (int)$this->context->customizePrettyId,
            'pretty_validity_day' => (int)$this->context->prettyValidityDay,
            'qualification_expire_dateline' => (int)($this->context->qualificationExpireDay * 86400 + time()),
            'remark' => $this->context->remark ? $this->context->remark : '',
            'give_type' => $this->context->giveType ? $this->context->giveType : 0,
            'creator'=> $creator,
        ];

        $sendNum = $this->context->sendNum ?: 1;
        // 要发多次
        for ($i = 0; $i < $sendNum; $i++) {
            [$res, $msg] = (new PsService())->prettyUserCustomizeCreate($data);
            if (!$res) {
                list($code, $initMsg) = PrettyUserCustomizeException::CURL_FAIL_ERROR;
                $msg = sprintf($initMsg, $msg);
                throw new PrettyUserCustomizeException($msg, $code);
            }

            //push消息
//            foreach ($uids as $uid) {
//                $msg = $this->_translate($uid, "恭喜您获得自选靓号资格，请在%s天内进行使用");
//
//                $msg = sprintf($msg, $this->context->qualificationExpireDay);
//                NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
//                    'cmd' => 'system.message',
//                    'data' => array(
//                        'from' => 0,
//                        'uid' => $uid,
//                        'message' => $msg,
//                        'extra' => [
//                            'type' => 'qualified_for_customize_pretty_uid',
//                            'jump_url' => 'https://partystars.app.link/ps-link/special-id-self',
//                        ],
//                    )
//                ));
//            }

            foreach ($uids as $_uid) {
                if ($_uid) {
                    OperateLog::addOperateLog([
                        'before_json'  => '',
                        'content'      => '新增',
                        'after_json'   => $data,
                        'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
                        'model'        => (new XsUserCustomizePretty)->getSource(),
                        'model_id'     => 0,
                        'uid'          => $_uid,
                        'action'       => BmsOperateLog::ACTION_ADD,
                        'operate_id'   => $this->context->adminId,
                        'operate_name' => $creator,
                    ]);
                }
            }
            usleep(1000 * 20);
        }
    }
}
