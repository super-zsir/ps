<?php

namespace Imee\Service\Domain\Service\Csms;

use Imee\Service\Domain\Service\Csms\Context\Saas\InitDataContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FieldContext;
use Imee\Service\Domain\Service\Csms\Process\Saas\InitDataProcess;

/**
 * saas
 * Class SaasService
 * @package Imee\Service\Domain\Service\Csms
 */
class SaasService
{
    /**
     * 初始化入宽表数据
     */
    public function initData($params = [])
    {
        if (!$params) {
            return false;
        }
        $params['field_contexts'] = [];
        if (isset($params['fields'])) {
            foreach ($params['fields'] as $fields) {
                $params['field_contexts'][] = new FieldContext($fields);
            }
        }
        $context = new InitDataContext($params);
        $process = new InitDataProcess($context);
        $result = $process->handle();
        return $result;
    }
}
