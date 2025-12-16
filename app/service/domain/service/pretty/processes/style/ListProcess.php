<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\Style;

use Imee\Service\Domain\Context\Pretty\Style\ListContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 列表
 */
class ListProcess extends NormalListAbstract
{
    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsCustomizePrettyStyle::class;
        $this->query = XsCustomizePrettyStyle::query();
    }


    protected function buildWhere()
    {
        if (!empty($this->context->id)) {
            $this->where['condition'][] = "id=:id:";
            $this->where['bind']['id'] = $this->context->id;
        }

        if (!empty($this->context->name)) {
            $this->where['condition'][] = "name like :name:";
            $this->where['bind']['name'] = '%' . $this->context->name . '%';
        }
        if (is_numeric($this->context->disabled)) {
            $this->where['condition'][] = "disabled=:disabled:";
            $this->where['bind']['disabled'] = $this->context->disabled;
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
            $tmp['style_type'] = strval($tmp['style_type']);
            $format[] = $tmp;
        }
        return $format;
    }
}
