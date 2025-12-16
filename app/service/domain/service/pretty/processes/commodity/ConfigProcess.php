<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Commodity;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCommodityPrettyInfo;

/**
 * 列表
 */
class ConfigProcess
{
    public function getArea()
    {
        $format = [];
        $list = XsBigarea::getAreaList();
        foreach ($list as $area) {
            $tmp['label'] = $area['cn_name'];
            $tmp['value'] = $area['name'];
            $format[] = $tmp;
        }
        return $format;
    }

    public function getOnSaleStatus()
    {
        $format = [];
        
        foreach (XsCommodityPrettyInfo::$displayOnSaleStatus as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }
}
