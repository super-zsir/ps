<?php

namespace Imee\Comp\Nocode\Service\Logic\Model;


use Imee\Comp\Nocode\Models\Cms\NocodeModelConfig;
use Imee\Comp\Nocode\Service\Context\Model\ListContext;

/**
 * 获取模型列表
 */
class ListLogic
{
    /**
     * @var ListContext $context
     */
    private $context;

    /**
     * @var NocodeModelConfig $model
     */
    private $model = NocodeModelConfig::class;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $data = $this->model::getListByWhere([], 'name,comment');
        return $data;
    }
}