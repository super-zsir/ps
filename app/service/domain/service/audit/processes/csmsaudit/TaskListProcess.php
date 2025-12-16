<?php


namespace Imee\Service\Domain\Service\Audit\Processes\Csmsaudit;

use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Es\ESConfig;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\CsmsAudioLog;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoice;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

class TaskListProcess extends NormalListAbstract
{
	use UserInfoTrait;
	use CsmsTrait;


	protected $context;
	protected $masterClass;
	protected $leftClass;
	protected $query;
	protected $esPrimaryKey = 'id';

	public function __construct(PageContext $context)
	{
		$this->context = $context;
		$this->masterClass = CsmsAudit::class;
		$this->dataFrom = ESConfig::MYSQL;
		$this->query = CsmsAudit::query();
	}


	protected function buildWhere()
	{

		$where = [];
		$esWhere = [];
		if (!empty($this->context->beginTime)) {
			$dateline = strtotime($this->context->beginTime);
			$where[] = " $this->masterClass.dateline >= {$dateline} ";
		} else {
            $dateline = strtotime(date('Y-m-d', time())) - 3 * 86400;
			$where[] = " $this->masterClass.dateline >= {$dateline} ";
		}
		$esWhere['dateline >='] = $dateline;

		if (!empty($this->context->endTime)) {
			$dateline = strtotime($this->context->endTime) + 86400;
			$where[] = " $this->masterClass.dateline < {$dateline} ";
		} else {
			$dateline = strtotime(date('Y-m-d', time())) + 86400;
			$where[] = " $this->masterClass.dateline < {$dateline} ";
		}
		$esWhere['dateline <'] = $dateline;



		if (is_numeric($this->context->deleted) && in_array($this->context->deleted, [
				CsmsAudit::DELETED_INIT,
				CsmsAudit::DELETED_PASS,
				CsmsAudit::DELETED_REFUSE,
                CsmsAudit::DELETED_DELETE,
                CsmsAudit::DELETED_CALLBACK,
			])) {
			$where[] = " $this->masterClass.deleted = {$this->context->deleted} ";
			$esWhere['deleted'] = $this->context->deleted;
		}

		if (is_numeric($this->context->deleted2) && in_array($this->context->deleted2, [
				CsmsAudit::DELETED2_INIT,
				CsmsAudit::DELETED2_PASS,
				CsmsAudit::DELETED2_REFUSE,
                CsmsAudit::DELETED_DELETE,
                CsmsAudit::DELETED_CALLBACK,
			])) {
			$where[] = " $this->masterClass.deleted2 = {$this->context->deleted2} ";
			$esWhere['deleted2'] = $this->context->deleted2;
		}

		if (is_numeric($this->context->deleted3) && in_array($this->context->deleted3, [
				CsmsAudit::DELETED3_INIT,
				CsmsAudit::DELETED3_PASS,
				CsmsAudit::DELETED3_REFUSE,
                CsmsAudit::DELETED_DELETE,
                CsmsAudit::DELETED_CALLBACK,
			])) {
			$where[] = " $this->masterClass.deleted3 = {$this->context->deleted3} ";
			$esWhere['deleted3'] = $this->context->deleted3;
		}

		if ($this->context->isSecond) {
			$where[] = " $this->masterClass.deleted > 0 ";
			$esWhere['deleted >'] = CsmsAudit::DELETED_INIT;
		}

		if ($this->context->isFinal) {
			$where[] = " $this->masterClass.deleted > 0 ";
			$where[] = " $this->masterClass.deleted2 > 0 ";
			$esWhere['deleted >'] = CsmsAudit::DELETED_INIT;
			$esWhere['deleted2 >'] = CsmsAudit::DELETED2_INIT;
		}


        if(!empty($this->context->choices)){
            $where[] = "$this->masterClass.choice in ('".implode("','", $this->context->choices)."')";
        }

		if(!empty($this->context->pkValue)){
			$where[] = "$this->masterClass.pk_value = '{$this->context->pkValue}'";
		}

		if (!empty($this->context->reason)) {
			$reason = addslashes($this->context->reason);
			$where[] = " $this->masterClass.reason like '%{$reason}%' ";
			$esWhere['reason like'] = $reason;
		}

		if (!empty($this->context->reason2)) {
			$reason = addslashes($this->context->reason2);
			$where[] = " $this->masterClass.reason2 like '%{$reason}%' ";
			$esWhere['reason2 like'] = $reason;
		}

		if (!empty($this->context->content)) {
			$content = addslashes($this->context->content);
			$where[] = " $this->masterClass.value like '%{$content}%' ";
			$esWhere['value like'] = $content;
		}

		if (!empty($this->context->sex)) {
			$where[] = " $this->masterClass.sex = '{$this->context->sex}' ";
			$esWhere['sex'] = $this->context->sex;
		}

		if (!empty($this->context->ids)) {
			$where[] = " $this->masterClass.id in (" . implode(',', $this->context->ids) . ") ";
			$esWhere['in']['id'] = $this->context->ids;
		}

        /**
         * language 权限
         */
//        $languagePower = [];
//		$languages = $this->getStaffLanguage();
//		if($languages){
//		    if($this->context->language){
//		        if(in_array($this->context->language, $languages)){
//		            $languagePower = [$this->context->language];
//                }else{
//		            $languagePower = [];
//                }
//            }else{
//                $languagePower = $languages;
//            }
//        }
//        $where[] = " $this->masterClass.language in ('".implode("','", $languagePower)."')";

        if($this->context->language){
            $where[] = "$this->masterClass.language = '{$this->context->language}'";
            $esWhere['language'] = $this->context->language;
        }


        /**
         * area 权限
         */
        $areaPower = [];
        $area = $this->getStaffArea();
        if($area){
            if($this->context->area){
                if(in_array($this->context->area, $area)){
                    $areaPower = [$this->context->area];
                }else{
                    $areaPower = [];
                }
            }else{
                $areaPower = $area;
            }
        }
        $where[] = " $this->masterClass.area in ('".implode("','", $areaPower)."')";


//		if (!empty($this->context->appIds)) {
//			$where[] = " $this->masterClass.app_id in (" . implode(',', $this->context->appIds) . ") ";
//			$esWhere['in']['app_id'] = $this->context->appIds;
//		}

        if(!empty($this->context->uid)){
            $where[] = "$this->masterClass.uid = :uid:";
            $this->where['bind']['uid'] = trim($this->context->uid);
        }


        // 机审结果搜索 - 不是通过的统统认为文本审核
        if($this->context->machine){
            if($this->context->machine == CsmsAudit::MACHINE_PASS){
                $where[] = "$this->masterClass.machine = :machine:";
                $this->where['bind']['machine'] = (int)$this->context->machine;
            }else{
                $where[] = "$this->masterClass.machine in (0, 2, 3, 4)";
//                $this->where['bind']['machine'] = CsmsAudit::MACHINE_PASS;
            }

        }


		$this->where['condition'] = $where;
		$this->esWhere = $esWhere;
	}




	/**
	 * 列表
	 * @param $items
	 * @return array|mixed
	 */
	protected function formatList($items)
	{
		if($items){
			$items = is_array($items) ? $items : $items->toArray();

            if(empty($items)){
                return $items;
            }

			$uids = array_column($items, 'uid');
			// 获取用户信息
            $userInfos = $this->getUserInfoModel($uids)->handle();
			// 获取音频信息
            $csmsAudioLogs = [];
			$taskids = array_filter(array_column($items, 'taskid'));
			if($taskids){
                $csmsAudioLogs = CsmsAudioLog::find([
                    'conditions' => 'taskid in ({taskids:array})',
                    'bind' => [
                        'taskids' => $taskids
                    ]
                ])->toArray();
            }


            // 获取用户 违规信息
            $userDanger = $this->getUserDanger($uids);
            // 审核项信息
            $all_choice = array_unique(array_column($items, 'choice'));
            $choiceList = CsmsChoice::handleList(array(
                'choice' => $all_choice,
                'columns' => ['choice_name', 'choice'],
            ));
            $choiceList = array_column($choiceList, 'choice_name', 'choice');
			foreach ($items as &$item){
				$item['app_name'] = Helper::getAppName($item['app_id']);
				$item['choice_name'] = $choiceList[$item['choice']] ?? '';

				$item['userinfo'] = isset($userInfos[$item['uid']]) ? $userInfos[$item['uid']] : [];
				$item['usericon'] = isset($item['userinfo']['user_icon']) ? $item['userinfo']['user_icon'] : '';

				// 计算审核用时
				$item['admin'] = $item['op'];

				$item['check_time'] = $item['op_dateline'] ? ($item['op_dateline'] - $item['dateline']) : '';
				$item['status'] = $item['deleted'];

				if($this->context->isSecond){
					$item['admin'] = $item['op2'];
					$item['check_time'] = $item['op_dateline2'] ? ($item['op_dateline2'] - $item['op_dateline']) : '';
					$item['status'] = $item['deleted2'];
				}
				if($this->context->isFinal){
					$item['admin'] = $item['op3'];
					$item['check_time'] = $item['op_dateline3'] ? ($item['op_dateline3'] - $item['op_dateline2']) : '';
					$item['status'] = $item['deleted3'];
				}
				$item['admin'] = $item['admin'] ?: '';
				$item['status_name'] = CsmsConstant::$csms_state[$item['status']];

				$item['origin'] = json_decode($item['origin'], true);
				$item['value'] = json_decode($item['value'], true);
				// 模型审核结果

				$item['machine_name'] = CsmsAudit::$machine_state[$item['machine']];
				$item['date'] = date('Y-m-d', $item['dateline']);
				$item['dateline'] = date('H:i:s', $item['dateline']);

                if($item['origin']){
                    foreach ($item['origin'] as &$itemOrigin){
                        if(isset($itemOrigin['value']) && is_array($itemOrigin['value']) && $itemOrigin['value']){
                            foreach ($itemOrigin['value'] as &$oneOrigin){
                                $oneOrigin = $this->getTypeValue($oneOrigin, $itemOrigin['type'], $item);
                            }
                        }
                    }
                }

				if($item['value']){
				    foreach ($item['value'] as &$itemValue){
				        if(isset($itemValue['value']) && is_array($itemValue['value']) && $itemValue['value']){
				            foreach ($itemValue['value'] as &$oneValue){
				                $oneValue = $this->getTypeValue($oneValue, $itemValue['type'], $item);
                            }
                        }
                    }
                }

				if($csmsAudioLogs){
					foreach ($csmsAudioLogs as $csmsAudioLog){
						if($csmsAudioLog['taskid'] == $item['taskid']){
							$csmsAudioLog['type'] = 'audio';
							$item['machine_info'][] = $csmsAudioLog;
						}
					}
				}
                // 用户违规信息统计
                $item['user_danger'] = $userDanger[$item['uid']] ?? ['refuse' => 0, 'forbidden' => 0];
			}

            // 获取审核人员名称
			$adminUids = array_column($items, 'admin');
			$admins = $this->getStaffBaseInfos(array_values($adminUids));
			foreach ($items as &$item){
			    $item['admin_name'] = isset($admins[$item['admin']]) ? $admins[$item['admin']]['user_name'] : '';
            }
		}
		return $items;
	}






}
