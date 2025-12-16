<?php

namespace Imee\Service\Domain\Service\Pretty;

use Imee\Service\Domain\Context\Pretty\Commodity\ListContext;
use Imee\Service\Domain\Service\Pretty\Processes\Commodity\ListProcess;

use Imee\Service\Domain\Context\Pretty\Commodity\CreateContext;
use Imee\Service\Domain\Context\Pretty\Commodity\ModifyContext;
use Imee\Service\Domain\Service\Pretty\Processes\Commodity\CreateOrModifyProcess;
use Imee\Service\Domain\Context\Pretty\Commodity\ShelfContext;
use Imee\Service\Domain\Service\Pretty\Processes\Commodity\ShelfProcess;

use Imee\Models\Xs\XsCommodityPrettyInfo;

class PrettycommodityService
{
    public function getList($params)
    {
        $context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }
    
    public function create($params)
    {
        $context = new CreateContext($params);

        return $this->modify($context->toArray());
    }

    public function modify($params)
    {
        $context = new ModifyContext($params);
        $process = new CreateOrModifyProcess($context);
        return $process->handle();
    }

    public function shelfon($params)
    {
        $params['on_sale_status'] = XsCommodityPrettyInfo::ON_SALE_STATUS_ON;
        return $this->shelf($params);
    }

    public function shelfoff($params)
    {
        $params['on_sale_status'] = XsCommodityPrettyInfo::ON_SALE_STATUS_OFF;
        
        return $this->shelf($params);
    }

    private function shelf($params)
    {
        $context = new ShelfContext($params);
        $process = new ShelfProcess($context);
        return $process->handle();
    }

    public function getInfo($params)
    {
        $buildParams = [
            'id' => $params['id'],
            'page' => 1,
            'limit' => 1,
        ];
        $res = $this->getList($buildParams);
        
        return isset($res['data'][0]) ? $res['data'][0] : [];
    }
}
