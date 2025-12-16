<?php


namespace Imee\Service\Domain\Service\Audit\Processes\Csmsrecheck;

use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Staff\NewTaskContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

/**
 * 内容管理系统 - 工作台复审
 * Class NewTaskProcess
 * @package Imee\Service\Domain\Service\Audit\Processes\Csmsaudit
 */
class NewTaskProcess
{

    use CsmsTrait;

	protected $context;



	public function __construct(NewTaskContext $context)
	{
		$this->context = $context;
	}


	public function handle()
	{
		$conditions = [];
		$bind = [];


		$conditions[] = "deleted2 = :deleted2:";
		$bind['deleted2'] = CsmsAudit::DELETED2_INIT;


		$conditions[] = "dateline >= :time:";
		$bind['time'] = strtotime('-7 day');


		if ($this->context->power) {
			$conditions[] = "choice in ({choice:array})";
			$bind['choice'] = $this->context->power;
		}

//		if (isset($this->context->where['app_ids']) && !empty($this->context->where['app_ids'])) {
//			$conditions[] = "app_id in ({app_ids:array})";
//			$bind['app_ids'] = $this->context->where['app_ids'];
//		}

        $oldIds = $this->context->oldIds;
        if($oldIds){
            $conditions[] = "id not in ({oldIds:array})";
            $bind['oldIds'] = $oldIds;
        }

//        $languages = $this->getStaffLanguage();
//        if($languages){
//            $conditions[] = "language in ({language:array})";
//            $bind['language'] = $languages;
//        }

        $area = $this->getStaffArea();
        if($area){
            $conditions[] = "area in ({area:array})";
            $bind['area'] = $area;
        }


		$task = CsmsAudit::find([
			'conditions' => implode(' and ', $conditions),
			'bind' => $bind,
			'order' => 'level desc,tasktime asc',
			'limit' => $this->context->num
		])->toArray();

		$data = array_column($task, 'id');
		return $data;
	}


}