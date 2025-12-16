<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Sensitive;

use Imee\Comp\Common\Sdk\SdkFilter;
use Imee\Service\Domain\Context\Audit\Sensitive\ListContext;
use Imee\Service\Helper;

/**
 * 列表
 */
class ListProcess
{
    /**
     * @var ListContext
     */
    private $context;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
    }

    private function buildWhere()
    {
        $where['app'] = APP_ID;


        if (!empty($this->context->type)) {
            $where['type'] = $this->context->type;
        }

        if (!empty($this->context->subType)) {
            $where['sub_type'] = $this->context->subType;
        }

        if (!empty($this->context->cond)) {
            $where['cond'] = $this->context->cond;
        }

        if (is_numeric($this->context->deleted)) {
            $where['status'] = (int)$this->context->deleted + 1;
        }

        if (is_numeric($this->context->vague)) {
            $where['vague'] = (int)$this->context->vague + 1;
        }

        if (!empty($this->context->text)) {
            $where['name'] = $this->context->text;
        }

        if (is_numeric($this->context->danger)) {
            $where['danger'] = (int)$this->context->danger + 1;
        }

        if (!empty($this->context->language)) {
            $where['language'] = $this->context->language;
        }

        return $where;
    }

   
    public function handle()
    {

        $languageCfg = Helper::getLanguageArr();
        
        $result = [
            'data' => [],
            'total' => 0,
        ];
        $where = $this->buildWhere();

        $filter = new SdkFilter();

        $item = $filter->getDirtys($where, $this->context->offset, $this->context->limit);

        if (empty($item) || !isset($item['data']) || empty($item['data'])) {
            return $result;
        }
       
        if (isset($item['data']['total'])) {
            $count = $item['data']['total'];
        } else {
            $count = count($item['data']['dirty_date']);
        }
     
        foreach ($item['data']['dirty_meta_date']['type_list'] as $type_value) {
            $type_list[$type_value['value']] = $type_value['label'];
        }
        foreach ($item['data']['dirty_meta_date']['cond_list'] as $cond_value) {
            $cond_list[$cond_value['value']] = $cond_value['label'];
        }
        foreach ($item['data']['dirty_date'] as $k => &$v) {
            
            $v['id'] = (int)$this->context->offset + $k + 1;
            $v['vague'] = isset($v['vague']) && $v['vague']==1 ? 1 : 0;
            $v['vague_text'] = isset(CommonConst::$dirtyTextDeleted[$v['vague']]) ?
                CommonConst::$vague[$v['vague']] : '';
            $v['vague'] = (string)$v['vague'];
            $v['type_text'] = $type_list[$v['type']] ?? '';
            $v['cond_text'] = str_replace(array_keys($cond_list), array_values($cond_list), $v['cond']);
            $v['cond'] = explode(',', $v['cond']);
            $v['dateline'] = date('Y-m-d H:i:s', $v['dateline']);
            $v['deleted'] = $v['delete'] ?? 0;
            $v['deleted_text'] = CommonConst::$dirtyTextDeleted[$v['deleted']] ?? '';
            
            $v['deleted'] = (string)$v['deleted'];
            
            $v['danger'] = $v['danger'] ?? 0;
            $v['danger_text'] = CommonConst::$danger[$v['danger']] ?? '';
            $v['danger'] = (string)$v['danger'];
            
            $v['display_accurate'] = CommonConst::$displayAccurate[$v['accurate']] ?? '';
            $v['display_language'] = isset($v['language']) && isset($languageCfg[$v['language']]) ?
                $languageCfg[$v['language']] : '';
            
            $v['accurate'] = (string)$v['accurate'];
        }

        return [
            'total' => $count,
            'data' => $item['data']['dirty_date'],
        ];
    }
}
