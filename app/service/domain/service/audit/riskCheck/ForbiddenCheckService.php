<?php

namespace Imee\Service\Domain\Service\Audit\RiskCheck;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\HistoryContext;
use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\ListContext;
use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\ModifyContext;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\HistoryProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\ListProcess;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\ModifyProcess;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\DurationContext;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\DurationProcess;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\UserlogContext;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\UserlogProcess;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\UserContext;
use Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck\UserProcess;

class ForbiddenCheckService
{
    public function getList($params)
    {
        $context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }

    public function getConfig()
    {
        $process = new ConfigProcess();
        return $process->handle();
    }

    public function modify($params)
    {
        $context = new ModifyContext($params);
        $process = new ModifyProcess($context);
        return $process->handle();
    }

    public function getDuration($params)
    {
        $context = new DurationContext($params);
        $process = new DurationProcess($context);
        return $process->handle();
    }

    public function getUserlog($params)
    {
        $context = new UserlogContext($params);
        $process = new UserlogProcess($context);
        return $process->handle();
    }

    // public function checkdid(CheckdidContext $context)
    // {
    //     $process = new CheckdidProcess($context);
    //     return $process->handle();
    // }

    // public function repairdid(RepairdidContext $context)
    // {
    //     $process = new RepairdidProcess($context);
    //     return $process->handle();
    // }

    // public function cleandid(CleandidContext $context)
    // {
    //     $process = new CleandidProcess($context);
    //     return $process->handle();
    // }

    public function getHistory($params)
    {
        $context = new HistoryContext($params);
        $process = new HistoryProcess($context);
        return $process->handle();
    }
     public function user($params)
     {
         $context = new UserContext($params);
         $process = new UserProcess($context);
         return $process->handle();
     }
}
