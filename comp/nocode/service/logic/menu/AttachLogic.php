<?php

namespace Imee\Comp\Nocode\Service\Logic\Menu;

use Imee\Comp\Nocode\Models\Cms\NocodeSchemaConfig;
use Imee\Comp\Nocode\Service\Context\Menu\AttachContext;

class AttachLogic
{
    /**
     * @var AttachContext $context
     */
    private $context;

    private $list;

    public function __construct(AttachContext $context)
    {
        $this->context = $context;
    }


    public function handle()
    {
        $this->getList();

        $role = $this->context->data;

        if (!isset($role['pages']) || empty($role['pages'])) {
            return [];
        }

        foreach ($role['pages'] as $k => $v) {
            $controller = $v['controller'] ? array_pop(explode('/', $v['controller'])) : '';
            if (in_array($controller, $this->list)) {
                $role['pages'][$k]['flag'] = 2;
                $role['pages'][$k]['ncid'] = $controller;
                $role['pages'][$k]['path'] = '/nocode/preview?ncid=' . $controller;
            } else {
                $role['pages'][$k]['flag'] = 0;
                $role['pages'][$k]['ncid'] = '';
            }
        }

        return $role;
    }

    protected function getList()
    {
        $list = NocodeSchemaConfig::getListByWhere([['system_id', '=', SYSTEM_ID]], 'ncid');

        $this->list = array_column($list, 'ncid');
    }
}