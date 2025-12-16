<?php

namespace Imee\Comp\Nocode\Service\Logic\Form;

use Imee\Comp\Nocode\Service\Context\Form\ListContext;
use Imee\Comp\Nocode\Models\Cms\NocodeSchemaConfig;
use Imee\Service\Helper;

/**
 * 表单列表
 */
class ListLogic
{
    /**
     * @var ListContext $context
     */
    private $context;

    /**
     * @var NocodeSchemaConfig $model
     */
    private $model = NocodeSchemaConfig::class;
    
    /**
     * 构造函数
     * @param ListContext $context
     */
    public function __construct(ListContext $context)
    {
        $this->context = $context;
    }

    /**
     * 处理逻辑
     * @return array
     */
    public function handle(): array
    {
        $page = (int) $this->context->page;
        $limit = (int) $this->context->limit;
        $ncid = $this->context->ncid;

        $conditions = [
            ['system_id', '=', SYSTEM_ID],
        ];

        $ncid && $conditions[] = ['ncid', '=', $ncid];

        $data = $this->model::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
        
        if (empty($data['data'])) {
            return $data;
        }

        foreach($data['data'] as &$item) {
            $item['create_time'] = $item['create_time'] > 0 ? Helper::now($item['create_time']) : '';
            $item['update_time'] = $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';
        }

        return $data;
    }
}