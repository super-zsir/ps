<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\User;

use Imee\Service\Domain\Context\Pretty\User\ExpireContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsUserPretty;
use Imee\Models\Xs\XsUserIndex;
use Imee\Exception\Operate\PrettyUserException;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Rpc\PsService;
use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsOperateHistory;

/**
 * 列表
 */
class ExpireProcess
{
    use UserInfoTrait;
    private $context;
    private $model;
    public function __construct(ExpireContext $context)
    {
        $this->context = $context;
    }

    
    private function verify()
    {
        $this->model = XsUserPretty::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $this->context->id,
            ],
        ]);

        if (empty($this->model)) {
            //报错
            PrettyUserException::throwException(PrettyUserException::DATA_NOEXIST_ERROR);
        }

        if ($this->model->expire_time <= time()) {
            PrettyUserException::throwException(PrettyUserException::PRETTYUSER_HAS_EXPIRE_ERROR);
        }

        return;
    }

    public function handle()
    {
        $this->verify();
        $data = [
            'id' => (int)$this->context->id,
            'pretty_uid' => $this->model->pretty_uid,
            'expire_time' => time() - 1,
        ];
        [$res, $msg] = (new PsService())->givePrettyUidModify($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $id = $this->context->id;
        
        $data['expire_time'] = date('Y-m-d H:i:s', $data['expire_time']);
        $data['expire'] = 1;
        
        BmsOperateHistory::insertLog(
            BmsOperateHistory::PRETTY_NUM,
            $id,
            $data,
            Helper::getSystemUid()
        );
    }
}
