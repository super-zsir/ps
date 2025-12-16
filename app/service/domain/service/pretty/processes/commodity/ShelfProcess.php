<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Commodity;

use Imee\Service\Domain\Context\Pretty\Commodity\ShelfContext;

use Imee\Service\Rpc\PsService;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCommodityPrettyInfo;
use Imee\Exception\Operate\PrettyCommodityException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Service\Helper;

/**
 * 上下架
 */
class ShelfProcess
{
    private $context;
    private $prettyInfos;

    public function __construct(ShelfContext $context)
    {
        $this->context = $context;
    }

    private function verify()
    {
        $this->prettyInfos = XsCommodityPrettyInfo::find([
            'conditions' => 'id in({ids:array})',
            'bind' => [
                'ids' => $this->context->id,
            ],
        ])->toArray();
        if (count($this->prettyInfos) != count($this->context->id)) {
            PrettyCommodityException::throwException(PrettyCommodityException::DATA_NOEXIST_ERROR);
        }
    }

    public function handle()
    {
        $this->verify();

        $data = [
            'pretty_uid_list' => array_column($this->prettyInfos, 'pretty_uid'),
            
            'on_sale_status' => (int)$this->context->onSaleStatus,
            
        ];
        
        [$res, $msg] = (new PsService())->prettyCommodityShelf($data);
        
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        foreach ($this->prettyInfos as $v) {
            $data = [
                'on_sale_status' => (int)$this->context->onSaleStatus
            ];
            OperateLog::addOperateLog([
                'before_json'  => '',
                'content'      => '上下架',
                'after_json'   => $data,
                'type'         => BmsOperateLog::TYPE_OPERATE_LOG,
                'model'        => (new XsCommodityPrettyInfo)->getSource(),
                'model_id'     => $v['pretty_uid'],
                'action'       => BmsOperateLog::ACTION_UPDATE,
                'operate_id'   => Helper::getSystemUid(),
            
                'operate_name' => Helper::getSystemUserInfo()['user_name'],
            ]);
        }
    }
}
