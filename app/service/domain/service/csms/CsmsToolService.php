<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Service\Domain\Service\Csms\Context\Csmstool\AudioListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\ChangeListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\ImageListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\PushTestContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\ReviewListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\ReviewRetryContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\SpamListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\TaskListContext;
use Imee\Service\Domain\Service\Csms\Context\Csmstool\TaskRetryContext;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\AudioListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\ChangeListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\CsmsToolConfigProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\ImageListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\PushTestProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\ReviewListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\ReviewRetryProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\SpamListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\TaskListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmstool\TaskRetryProcess;

/**
 * 内容安全管理-研发工具
 * Class CsmsToolService
 * @package Imee\Service\Domain\Service\Csms
 */
class CsmsToolService
{


    /**
     * 任务配置
     * @param array $params
     */
    public function taskConfig($params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }

    /**
     *
     * @param array $params
     */
    public function taskList($params = [])
    {
        $context = new TaskListContext($params);
        $process = new TaskListProcess($context);
        return $process->handle();
    }


    /**
     * 任务重试
     * @param array $params
     */
    public function taskRetry(array $params = [])
    {
        $context = new TaskRetryContext($params);
        $process = new TaskRetryProcess($context);
        return $process->handle();
    }



    public function changeConfig($params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }


    public function changeList($params = [])
    {
        $context = new ChangeListContext($params);
        $process = new ChangeListProcess($context);
        return $process->handle();
    }


    /**
     * 审核外显配置
     * @param array $params
     */
    public function reviewConfig(array $params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }


    /**
     * 审核外显日志
     * @param array $params
     */
    public function reviewList($params = [])
    {
        $context = new ReviewListContext($params);
        $process = new ReviewListProcess($context);
        return $process->handle();
    }


    /**
     * 审核外显补发
     * @param array $params
     */
    public function reviewRetry($params = [])
    {
        $context = new ReviewRetryContext($params);
        $process = new ReviewRetryProcess($context);
        return $process->handle();
    }


    public function imageConfig($params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }


    public function imageList($params = [])
    {
        $context = new ImageListContext($params);
        $process = new ImageListProcess($context);
        return $process->handle();
    }


    public function spamConfig($params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }

    public function spamList($params = [])
    {
        $context = new SpamListContext($params);
        $process = new SpamListProcess($context);
        return $process->handle();
    }


    public function audioConfig($params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }

    public function audioList($params = [])
    {
        $context = new AudioListContext($params);
        $process = new AudioListProcess($context);
        return $process->handle();
    }


    public function pushConfig($params = [])
    {
        $process = new CsmsToolConfigProcess();
        return $process->handle();
    }


    public function pushTest($params = [])
    {
        $context = new PushTestContext($params);
        $process = new PushTestProcess($context);
        return $process->handle();
    }

}