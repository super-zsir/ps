<?php

namespace Imee\Service\Domain\Service\Audit\Report;

use Imee\Helper\Constant\AuditConstant;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Domain\Service\Audit\Report\Context\UserReportContext;
use Imee\Service\Domain\Service\Audit\Report\Exception\BaseException;
use Imee\Service\Domain\Service\Audit\Report\Processes\UserReportProcess;
use Imee\Service\Helper;

class UserReportService
{
    use SingletonTrait;

    /**
     * @var UserReportContext
     */
    private $context;

    /**
     * @param array $params
     * @return $this
     */
    public function setContext(array $params): UserReportService
    {
        $this->context = new UserReportContext($params);
        return $this;
    }

    /**
     * 用户举报列表
     * @return array
     */
    public function userReportList(): array
    {
        $this->context->setParams(['rid' => 0]);
        $process = new UserReportProcess();
        return $process->handle($this->context);
    }

    /**
     * 审核
     * @return void
     */
    public function checkUserReport()
    {
        if ($this->context->id < 1 || $this->context->state < 1) {
            BaseException::throwException(BaseException::PARAMS_ERROR);
        }
        if ($this->context->state == 3 && !$this->context->reason) {
            BaseException::throwException(BaseException::REJECT_NEED_REASON);
        }
        $process = new UserReportProcess();
        $logs = [];
        if (!empty($this->context->ids) && $this->context->state == 2) {
            $ids = explode(',', $this->context->ids);
            foreach ($ids as $k => $v) {
                $process->check($v, $this->context, $logs);
            }
        } else {
            $process->check($this->context->id, $this->context, $logs);
        }
        if ($logs) {
            $process->addLogBatch($logs);
        }
    }

    /**
     * 房间举报列表
     * @return array
     */
    public function roomReportList(): array
    {
        $this->context->setParams(['rid_lg' => 1]);
        $process = new UserReportProcess();
        return $process->handle($this->context);
    }

    /**
     * @return array
     */
    public function operateList()
    {
        $process = new UserReportProcess();
        return $process->getLog($this->context);
    }

    public function reportConfig()
    {
        $format = [];
        $bigArea = XsBigarea::getAllNewBigArea();
        if ($bigArea) {
            $tmp = [];
            foreach ($bigArea as $k => $v) {
                $tmp['label'] = $v;
                $tmp['value'] = $k;
                $format['from_big_area'][] = $tmp;
            }
        }
        $tmp = [];
        foreach (AuditConstant::REPORT_STATE as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['state'][] = $tmp;
        }
        $tmp = [];
        foreach (AuditConstant::REPORT_TYPE as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['type'][] = $tmp;
        }
        $language = Helper::getLanguageArr();
        $tmp = [];
        foreach ($language as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format['language'][] = $tmp;
        }
        return $format;
    }
}