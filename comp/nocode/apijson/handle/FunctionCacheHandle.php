<?php
namespace Imee\Comp\Nocode\Apijson\Handle;

class FunctionCacheHandle extends AbstractHandle
{
    const CACHE_KEY = '@cache';

    public function handle()
    {
        $condition = $this->condition->getCondition();
        if (isset($condition[self::CACHE_KEY])) {
            // The value is already read in AbstractMethod, here we just unset it.
            $this->unsetKey[] = self::CACHE_KEY;
        }
    }

    protected function buildModel()
    {
        // This handle does not need to build a model.
    }
} 