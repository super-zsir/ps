<?php

namespace Imee\Comp\Nocode\Service\Logic;

use Imee\Comp\Nocode\Service\Context\Schema\SchemaJsonContext;
use Imee\Comp\Nocode\Service\Logic\Schema\ParseLogic;
use Imee\Helper\Traits\SingletonTrait;

class SchemaJsonLogic
{
    use SingletonTrait;
    
    public function parse(array $params): array
    {
        $context = new SchemaJsonContext($params);
        $logic = new ParseLogic($context);
        return $logic->handle();
    }
}