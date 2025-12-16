<?php

namespace Imee\Service\Domain\Service\Csms;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Csms\Context\Databoard\DailyContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ScheduleContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ScheduleOpContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\StaffListContext;
use Imee\Service\Domain\Service\Csms\Process\Databoard\CreateOrModifyProcess;
use Imee\Service\Domain\Service\Csms\Process\Databoard\ExamDetailProcess;
use Imee\Service\Domain\Service\Csms\Process\Databoard\ExamProcess;
use Imee\Service\Domain\Service\Csms\Process\Databoard\ScheduleProcess;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class ExamService
{
    use UserInfoTrait;

    /**
     * @var static
     */
    public static $examService;

    /**
     * @var StaffListContext
     */
    public $staffContext;

    /**
     * @var DailyContext
     */
    public $dailyContext;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!self::$examService instanceof static) {
            self::$examService = new static();
        }
        return self::$examService;
    }

    /**
     * @param array $params
     * @return void
     */
    public function initStaffContext(array $params)
    {
        $this->staffContext = new StaffListContext($params);
    }

    /**
     * @param array $params
     * @return void
     */
    public function initDailyContext(array $params)
    {
        $this->dailyContext = new DailyContext($params);
    }

    /**
     * 获取 审核业务数据看板 列表
     * @return array
     */
    public function getUserList($lang = 'zh_cn')
    {
        $process = new ExamProcess();
        $data = $process->userList($this->staffContext);
        return translate_output($data, $lang);
    }

    /**
     * @return array
     */
    public function dayListWithCount($lang = 'zh_cn')
    {
        $data = $this->dayList();
        $total = $this->getDayNum();
        $data = translate_output($data, $lang);
        return ['data' => $data, 'total' => $total];
    }

    /**
     * 审核项日维度统计
     * @return array
     */
    public function dayList()
    {
        $process = new ExamDetailProcess();
        return $process->handleList($this->dailyContext);
    }

    /**
     * 获取 审核项日看板 数量
     * @return int
     */
    public function getDayNum()
    {
        $process = new ExamDetailProcess();
        return $process->handleNum($this->dailyContext);
    }

    /**
     * 审核项日维度明细统计
     * @return array
     */
    public function dayDetailList($lang = 'zh_cn')
    {
        $process = new ExamDetailProcess();
        $result = $process->handleDetailList($this->dailyContext);
        return translate_output($result, $lang);
    }

    /**
     * 列表
     * @param ScheduleContext $context
     * @param string $lang
     * @return array
     */
    public function getScheduleList(ScheduleContext $context, $lang = 'zh_cn')
    {
        $process = new ScheduleProcess($context);
        $result = $process->handle();
        return translate_output($result, $lang);
    }

    /**
     * 新增
     * @param ScheduleOpContext $context
     */
    public function createSchedule(ScheduleOpContext $context)
    {
        $context = new ScheduleOpContext($context->toArray());
        $this->modifySchedule($context);
    }

    /**
     * 修改
     * @param ScheduleOpContext $context
     */
    public function modifySchedule(ScheduleOpContext $context)
    {
        $process = new CreateOrModifyProcess($context);
        $process->handle();
    }

    /**
     * 查找员工
     * @return array
     */
    public function getStaff($lang = 'zh_cn')
    {
        $res = CmsUser::getListByWhere([['system_id', '=', CMS_USER_SYSTEM_ID]]);
        $newStaff = [];
        if ($res) {
            foreach ($res as $re) {
                $newStaff[] = [
                    'value' => $re['user_id'],
                    'label' => $re['user_name'],
                ];
            }
        }
        return translate_output($newStaff, $lang);
    }

    public function config($lang = 'zh_cn')
    {
        $format = [];
        $bigArea = XsBigarea::getAreaList();
        if ($bigArea) {
            $format['from_big_area'] = array(
                [
                    'label' => '全部',
                    'value' => '',
                ]
            );
            $tmp = [];
            foreach ($bigArea as $k => $v) {
                $tmp['label'] = $v['cn_name'];
                $tmp['value'] = $v['name'];
                $format['from_big_area'][] = $tmp;
            }
        }
        // 审核项
        $choice_list = CsmsChoice::handleList(array(
            'columns' => ['choice', 'choice_name'],
            'state'   => 1,
        ));
        if ($choice_list) {
            $format['choice_list'] = array(
                [
                    'label' => '全部',
                    'value' => '',
                ],
                [
                    'label' => '全部审核审核项',
                    'value' => 'audit_choice',
                ],
                [
                    'label' => '全部客服审核项',
                    'value' => 'kefu_choice',
                ],
            );
            $tmp = [];
            foreach ($choice_list as $k => $v) {
                $tmp['label'] = $v['choice_name'];
                $tmp['value'] = $v['choice'];
                $format['choice_list'][] = $tmp;
            }
        }
        // 员工名称
        $userChoice = CsmsUserChoice::handleList(array(
            'columns' => ['distinct user_id'],
            'state'   => 1,
        ));
        $userIds = array_column($userChoice, 'user_id');
        $users = [];
        if ($userIds) {
            $users = CmsUser::getListByWhere([['user_id', 'in', $userIds]], 'user_name,user_id');
        }
        if ($users) {
            $format['users'] = array(
                [
                    'label' => '全部',
                    'value' => '',
                ]
            );
            $tmp = [];
            foreach ($users as $k => $v) {
                $tmp['label'] = $v['user_name'];
                $tmp['value'] = $v['user_id'];
                $format['users'][] = $tmp;
            }
        }
        foreach ($format as &$item) {
            foreach ($item as &$ii) {
                $ii['label'] = __T($ii['label'], [], $lang);
            }
        }
        return $format;
    }
}
