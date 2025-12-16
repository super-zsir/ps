<?php

namespace Imee\Controller\Audit\Databoard;

use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Csms\ExamService;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\AuditValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\CreateScheduleValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\ExamStaffValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\ExamValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\KpiModifyValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\ModifyScheduleValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\ScheduleValidation;
use Imee\Service\Domain\Service\Csms\Validation\Databoard\TimeBoardValidation;
use Imee\Service\Domain\Service\Csms\Context\Databoard\AuditContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ExamContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\KpiModifyContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ScheduleContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ScheduleOpContext;
use Imee\Service\Helper;

/**
 * 客服满意度统计
 */
class ExamController extends BaseController
{
    /**
     * @page exam
     * @name 业务数据看板-员工看板
     * @point 员工看板
     */
    public function indexAction()
    {
        $getData = $this->request->get();
        ExamStaffValidation::make()->validators($getData);
        $service = new ExamService();
        $service->initStaffContext($getData);
        $data = $service->getUserList($this->lang);
        return $this->outputSuccess($data);
    }

	/**
	 * @page exam
	 * @point 下拉数据
	 */
	public function configAction()
	{
		$service = new ExamService();
		$data = $service->config($this->lang);
		return $this->outputSuccess($data);
	}

    /**
     * @page exam
     * @point 审核员工班次管理
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function scheduleAction()
    {
        ScheduleValidation::make()->validators($this->request->get());
        $context = new ScheduleContext($this->request->get());
        $service = new ExamService();
        $result = $service->getScheduleList($context, $this->lang);
        return $this->outputSuccess($result);
    }

    /**
     * @page exam
     * @point 出勤列表新增
     */
    public function createAction()
    {
        CreateScheduleValidation::make()->validators($this->request->get());
        $contextArr = array_merge($this->request->get(), [
            'op_uid' => $this->uid,
        ]);
        $context = new ScheduleOpContext($contextArr);
        $service = new ExamService();
        $service->createSchedule($context);
        return $this->outputSuccess();
    }

    /**
     * @page exam
     * @point 出勤列表修改
     */
    public function modifyAction()
    {
        ModifyScheduleValidation::make()->validators($this->request->get());
        $contextArr = array_merge($this->request->get(), [
            'op_uid' => $this->uid,
        ]);
        $context = new ScheduleOpContext($contextArr);
        $service = new ExamService();
        $service->modifySchedule($context);
        return $this->outputSuccess();
    }

	/**
	 * @page exam
	 * @point 员工列表
	 * @return \Phalcon\Http\ResponseInterface
	 */
	public function staffAction()
	{
		$service = new ExamService();
		$result = $service->getStaff($this->lang);
		return $this->outputSuccess(['data' => $result]);
	}

    /**
     * @page exam_detail
     * @name 业务数据看板-审核项详情看板
     * @point 审核项详情看板
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function dayBoardAction()
    {
        $getData = $this->request->get();
        ExamStaffValidation::make()->validators($getData);
        $service = ExamService::getInstance();
        $service->initDailyContext($getData);
        return $this->outputSuccess($service->dayListWithCount($this->lang));
    }

    /**
     * @page exam_day_detail
     * @name 业务数据看板-审核项分日看板
     * @point 审核项分日看板
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function dailyDetailBoardAction()
    {
        $getData = $this->request->get();
        ExamStaffValidation::make()->validators($getData);
        $service = ExamService::getInstance();
        $service->initDailyContext($getData);
        return $this->outputSuccess($service->dayDetailList($this->lang));
    }

    /**
     * @page exam
     * @point 考核指标管理
     */
    public function effectionAction()
    {
        $getData = $this->request->getPost();
        $kanbanService = KanbanService::getInstance();
        $kanbanService->initListContext($getData);
        $result = $kanbanService->kpiList();
        $total = $kanbanService->kpiTotal();
        return $this->outputSuccess(['list' => $result, 'total' => $total]);
    }

    /**
     * @page exam
     * @point 新增|编辑考核量指标
     */
    public function addAuditAction()
    {
        $postData = $this->request->getPost();
        AuditValidation::make()->validators($postData);
        $kanbanService = KanbanService::getInstance();
        $context = new AuditContext($postData);
        $kanbanService->manageKpi($context, array('operator_id' => $this->uid));
        return $this->outputSuccess();
    }

    /**
     * @page exam
     * @point 新增|编辑错审指标
     */
    public function addExamAction()
    {
        $postData = $this->request->getPost();
        ExamValidation::make()->validators($postData);
        $kanbanService = KanbanService::getInstance();
        $context = new ExamContext($postData);
        $kanbanService->manageKpi($context, array('operator_id' => $this->uid));
        return $this->outputSuccess();
    }

    /**
     * @page exam
     * @point 新增|编辑审核时长指标
     */
    public function addAuditTimeAction()
    {
        $postData = $this->request->getPost();
        KpiModifyValidation::make()->validators($postData);
        $kanbanService = KanbanService::getInstance();
        $context = new KpiModifyContext($postData);
        $kanbanService->manageKpi($context, array('operator_id' => $this->uid));
        return $this->outputSuccess();
    }

    /**
     * @page exam
     * @point 审核指标修改日志
     * @return array
     * @throws \ReflectionException
     */
    public function getEfLogAction()
    {
        $postData = $this->request->get();
        $kanbanService = KanbanService::getInstance();
        $kanbanService->initLogContext($postData);
        $result = $kanbanService->kpiLogList();
        $total = $kanbanService->kpiLogTotal();
        return $this->outputSuccess(['list' => $result, 'total' => $total]);
    }

    /**
     * @page exam_time_detail
     * @name 业务数据看板-审核项分时看板
     * @point 审核项分时看板
     */
    public function verifyTimeAction()
    {
        $postData = $this->request->get();
        TimeBoardValidation::make()->validators($postData);
        $kanbanService = KanbanService::getInstance();
        $kanbanService->initTimeContext($postData);
        return $this->outputSuccess($kanbanService->timeBoardList());
    }
}
