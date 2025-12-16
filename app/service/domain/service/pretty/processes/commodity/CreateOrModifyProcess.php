<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Commodity;

use Imee\Service\Domain\Context\Pretty\Commodity\ModifyContext;

use Imee\Service\Rpc\PsService;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCommodityPrettyInfo;
use Imee\Exception\Operate\PrettyCommodityException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Service\Helper;

/**
 * 新增
 */
class CreateOrModifyProcess
{
    private $context;

    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    private function verify()
    {
        if (!$this->context->id) {
            $model = XsCommodityPrettyInfo::findFirst([
                'conditions' => 'pretty_uid = :pretty_uid:',
                'bind' => [
                    'pretty_uid' => $this->context->prettyUid,
                ],
            ]);
            if ($model) {
                //报错
                PrettyCommodityException::throwException(PrettyCommodityException::PRETTYUSER_REPEAT_ERROR);
            }
            return;
        }
        $model = XsCommodityPrettyInfo::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $this->context->id,
            ],
        ]);
        if (!$model) {
            //报错
            PrettyCommodityException::throwException(PrettyCommodityException::DATA_NOEXIST_ERROR);
        }
        if ($this->context->prettyUid != $model->pretty_uid) {
            //报错
            PrettyCommodityException::throwException(PrettyCommodityException::DATA_DIFF_ERROR);
        }
    }

    public function handle()
    {
        $id = 0;
        $this->verify();

        $data = [
            'pretty_uid' => $this->context->prettyUid,
            'weight' => (int)$this->context->weight,
            'support_area' => implode(',', $this->context->supportArea),
            'on_sale_status' => $this->context->onSaleStatus ?
                (int)$this->context->onSaleStatus : XsCommodityPrettyInfo::ON_SALE_STATUS_OFF,
            'price_list' => $this->context->priceInfo
        ];
        if (!$this->context->id) {
            [$res, $msg, $id] = (new PsService())->prettyCommodityCreate($data);
        } else {
            $id = $this->context->id;
            [$res, $msg] = (new PsService())->prettyCommodityModify($data);
        }
        
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        
        OperateLog::addOperateLog([
            'before_json'  => '',
            'content'      => $this->context->id ? '修改' : '新增',
            'after_json'   => $data,
            'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
            'model'        => (new XsCommodityPrettyInfo)->getSource(),
            'model_id'     => $id,
            'action'       => $this->context->id ? BmsOperateLog::ACTION_UPDATE : BmsOperateLog::ACTION_ADD,
            'operate_id'   => Helper::getSystemUid(),
            
            'operate_name' => Helper::getSystemUserInfo()['user_name'],
        ]);
    }
}
