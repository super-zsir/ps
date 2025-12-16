<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Commodity;

use Imee\Service\Domain\Context\Pretty\Commodity\ListContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsCommodityPrettyInfo;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Models\Xsst\BmsOperateLog;

/**
 * 列表
 */
class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;
    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsCommodityPrettyInfo::class;
        $this->query = XsCommodityPrettyInfo::query();
    }


    protected function buildWhere()
    {
        if (!empty($this->context->id)) {
            $this->where['condition'][] = "id=:id:";
            $this->where['bind']['id'] = $this->context->id;
        }

        if (!empty($this->context->prettyUid)) {
            $this->where['condition'][] = "pretty_uid = :pretty_uid:";
            $this->where['bind']['pretty_uid'] = $this->context->prettyUid;
        }

        if (!empty($this->context->supportArea)) {
            $this->where['condition'][] = "FIND_IN_SET(:support_area:, support_area)";

            $this->where['bind']['support_area'] = $this->context->supportArea;
        }

        if (is_numeric($this->context->onSaleStatus)) {
            $this->where['condition'][] = "on_sale_status = :on_sale_status:";
            $this->where['bind']['on_sale_status'] = $this->context->onSaleStatus;
        }
        if (!empty($this->context->maxId)) {
            $this->where['condition'][] = "id < :max_id:";
            $this->where['bind']['max_id'] = $this->context->maxId;
        }
    }

    protected function formatList($items)
    {
        $format = [];

        if (empty($items)) {
            return $format;
        }
        foreach ($items as $item) {
            $tmp = $item->toArray();
            $tmp['display_on_sale_status'] = isset(XsCommodityPrettyInfo::$displayOnSaleStatus[$tmp['on_sale_status']])
                ? XsCommodityPrettyInfo::$displayOnSaleStatus[$tmp['on_sale_status']] : '';
            $tmp['support_area'] = explode(',', $tmp['support_area']);
            $tmp['display_support_area'] = implode(',', array_map(function ($v) {
                return XsBigarea::getBigAreaCnName($v);
            }, $tmp['support_area']));

            $tmp['create_dateline'] = $tmp['create_dateline'] > 0 ? date('Y-m-d H:i:s', $tmp['create_dateline']) : '';
            $tmp['dateline'] = $tmp['dateline'] > 0 ? date('Y-m-d H:i:s', $tmp['dateline']) : '';

            $priceInfos = json_decode($tmp['price_info'], true);
            for ($i = 1; $i <= 3; $i++) {
                $tmp['effective_day' . $i] = isset($priceInfos[$i - 1]['effective_day']) ?
                    $priceInfos[$i - 1]['effective_day'] : '';
                $tmp['price' . $i] = isset($priceInfos[$i - 1]['price']) ? $priceInfos[$i - 1]['price'] : '';
            }
            $tmp['price_info'] = $priceInfos ?? [];

            $tmp['staff_name'] = '';
            $tmp['staff_id'] = 0;
            //操作人
            $logInfo = BmsOperateLog::getFirstLogList($item->getSource(), $tmp['id']);
            if ($logInfo) {
                $tmp['staff_name'] = $logInfo[$tmp['id']]['operate_name'];
                $tmp['staff_id'] = $logInfo[$tmp['id']]['operate_id'];
            }
            
            $format[] = $tmp;
        }
        return $format;
    }
}
