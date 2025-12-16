<?php

namespace Imee\Comp\Nocode\Apijson\Replace;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;

abstract class AbstractReplace
{
    /** @var ConditionEntity */
    protected $condition;
    public function __construct(ConditionEntity $condition)
    {
        $this->condition = $condition;
    }

    public function handle(): ?array
    {
        return $this->process();
    }

    abstract protected function process();
}