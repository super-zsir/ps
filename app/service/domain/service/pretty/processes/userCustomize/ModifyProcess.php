<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\UserCustomize;

use Imee\Service\Domain\Context\Pretty\UserCustomize\ModifyContext;

use Imee\Service\Rpc\PsService;
use Imee\Exception\ApiException;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Models\Xs\XsUserCustomizePretty;
use Imee\Exception\Operate\PrettyUserCustomizeException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Service\Helper;

/**
 * 新增
 */
class ModifyProcess
{
    use CsmsTrait;
    private $context;
    private $model;

    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    private function verify()
    {
        $this->model = XsUserCustomizePretty::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $this->context->id,
            ],
        ]);
        if (empty($this->model)) {
            PrettyUserCustomizeException::throwException(PrettyUserCustomizeException::DATA_NOEXIST_ERROR);
        }
    }

    public function handle()
    {
        $this->verify();
        $adminId  = Helper::getSystemUid();
        $operator = Helper::getAdminName($adminId??'');
        $data = [
            'id' => (int)$this->context->id,
            'pretty_validity_day' => (int)$this->context->prettyValidityDay,
            'qualification_expire_dateline' => (int)strtotime($this->context->qualificationExpireDateline),
            'operator'=>$operator,
        ];
        [$res, $msg] = (new PsService())->prettyUserCustomizeModify($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        //push消息
        if (strtotime($this->context->qualificationExpireDateline) != $this->model->qualification_expire_dateline) {
            if (strtotime($this->context->qualificationExpireDateline) > time()) {
                $msg = $this->_translate($this->model->uid, "您序号为%s的自选靓号资格使用有效期已经更新，请注意查看");
            } else {
                $msg = $this->_translate($this->model->uid, "您序号为%s的自选靓号资格已经到期无法使用");
            }
            
                
            $msg = sprintf($msg, $this->context->id);
            NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                'cmd' => 'system.message',
                'data' => array(
                    'from' => 0,
                    'uid' => $this->model->uid,
                    'message' => $msg,
                    'extra' => [
                        'type' => 'qualified_for_customize_pretty_uid',
                        'jump_url' => 'https://partystars.app.link/ps-link/special-id-self',
                    ],
                )
            ));
        }
        

        OperateLog::addOperateLog([
            'before_json'  => '',
            'content'      => '修改',
            'after_json'   => $data,
            'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
            'model'        => $this->model->getSource(),
            'model_id'     => $this->model->id,
            'uid'          =>  $this->model->uid,
            'action'       => BmsOperateLog::ACTION_UPDATE,
            'operate_id'   => $adminId,
            'operate_name' => $operator,
        ]);
    }
}
