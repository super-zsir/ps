<?php

namespace Imee\Service\Domain\Service\Pretty;

use Imee\Service\Domain\Context\Pretty\User\ListContext;
use Imee\Service\Domain\Service\Pretty\Processes\User\ListProcess;

use Imee\Service\Domain\Context\Pretty\User\CreateContext;
use Imee\Service\Domain\Context\Pretty\User\ModifyContext;
use Imee\Service\Domain\Service\Pretty\Processes\User\CreateOrModifyProcess;
use Imee\Service\Domain\Context\Pretty\User\ExpireContext;
use Imee\Service\Domain\Service\Pretty\Processes\User\ExpireProcess;
use Imee\Service\Domain\Context\Pretty\User\HistoryContext;
use Imee\Service\Domain\Service\Pretty\Processes\User\HistoryProcess;
use Imee\Service\Domain\Service\Pretty\Processes\User\InfoLogProcess;

class PrettyuserService
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

    public function expire($params)
    {
        $context = new ExpireContext($params);
        $process = new ExpireProcess($context);
        return $process->handle();
    }

    public function getHistory($params)
    {
        $context = new HistoryContext($params);
        $process = new HistoryProcess($context);
        return $process->handle();
    }

    public function getInfoLog($params)
    {
        $context = new HistoryContext($params);
        $process = new InfoLogProcess($context);
        return $process->handle();
    }

    public function handleParams(array $params): array
    {
        // 全角数字转半角数字
        if (!empty($params['pretty_uid'])) {
            $params['pretty_uid'] = mb_convert_kana($params['pretty_uid'], 'n', 'UTF-8');
        }

        return $params;
    }
}
