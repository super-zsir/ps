<?php

namespace Imee\Service\Domain\Service\Csms\Process\Saas\Plugins;

use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsFieldScene;
use Imee\Models\Xss\CsmsFieldSceneMachine;
use Imee\Models\Xss\CsmsServicer;
use Imee\Models\Xss\CsmsServicerScene;
use Imee\Service\Domain\Service\Csms\Context\Saas\InitDataContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FieldContext;
use Imee\Service\Domain\Service\Csms\RiskService;

abstract class AbstractPlugin
{
	/**
	 * 检测结果
	 * @var string[]
	 */
	public $result = [
		'machine' => CsmsAudit::MACHINE_IDENTIFY,
		'tags' => '',
		'reason' => ''
	];


    /**
     * @var FieldContext
     */
    protected $context;

    /**
     * @var InitDataContext
     */
    protected $initContext;

    /**
     * @var RiskService
     */
    protected $riskService;
    
    protected $reason = '';

	protected $filterMachine = [];

    public function __construct(FieldContext $context, InitDataContext $initContext, $riskService)
    {
        $this->context = $context;
        $this->initContext = $initContext;
        $this->riskService = $riskService;
        $this->init();
		$this->machineValid();
    }

    public function getReason()
    {
        return $this->reason;
    }

	/**
	 * 获取机审结果
	 * @return string[]
	 */
    public function getResult()
    {
    	return $this->result;
    }

    abstract protected function init();

    // 获取服务商信息
    protected function getServicerMap()
    {
        $map = [];
        $model = CsmsFieldScene::findFirst([
            'conditions' => 'fid = :id: and state=:state:',
            'bind' => [
                'id' => $this->context->fieldId,
                'state' => CsmsFieldScene::STATE_NORMAL
            ],
        ]);
        if (!$model || !$model->sid) {
            //没有配置不做处理
            return $map;
        }
        // 获取服务商场景配置
        $servicerSceneList = CsmsServicerScene::find([
            'conditions' => 'id in({ids:array}) and state=:state:',
            'bind' => [
                'ids' => explode(',', $model->sid),
                'state' => CsmsServicerScene::STATE_NORMAL,
            ],
        ])->toArray();
        if (!$servicerSceneList) {
            return $map;
        }
        $sceneMap = [];
        foreach ($servicerSceneList as $v) {
            $sceneMap[$v['sid']][] = $v['mark'];
        }
        // 整理不同服务商 不同场景配置
        $serviceIds = array_keys($sceneMap);
        $servicerList = CsmsServicer::find([
            'conditions' => 'id in({ids:array}) and state=:state:',
            'bind' => [
                'ids' => $serviceIds,
                'state' => CsmsServicer::STATE_NORMAL,
            ],
            'order' => 'id asc',
        ])->toArray();
        if (!$servicerList) {
            return $map;
        }
        foreach ($servicerList as $v) {
            $tmp = [
                'id' => $v['id'],
                'mark' => $v['mark'],
                'scenes' => $sceneMap[$v['id']],
            ];
            $map[$v['id']] = $tmp;
        }
        return $map;
    }

	/**
	 * 机审代替人审
	 * @return string[]|void
	 */
	protected function machineValid()
	{
		// 字段
		$fieldId = $this->context->fieldId;
		// 获取检测场景
		$fieldScene = CsmsFieldScene::handleOne(array(
			'fid' => $fieldId,
			'columns' => ['fid','sid','id'],
			'state' => CsmsFieldScene::STATE_NORMAL
		));
		if (!$fieldScene) {
			return $this->result;
		}
		// 机审代替人审配置加载
		$machine = CsmsFieldSceneMachine::handleList(array(
			'field_scene_id' => $fieldScene->id,
			'state' => CsmsFieldSceneMachine::STATE_NORMAL,
			'orderBy' => 'sort',
			'columns' => ['field_scene_id', 'scene_ids', 'valid_state', 'type', 'id']
		));
		if (!$machine) {
			return $this->result;
		}
		$scene_ids = array_column($machine, 'scene_ids');
		$scene_ids_str = implode(',', $scene_ids);
		$scene_arr = explode(',', $scene_ids_str);
		$serverScene = CsmsServicerScene::handleList(array(
			'id_array' => $scene_arr,
			'columns' => ['id','mark']
		));
		$serverScene = array_column($serverScene, 'mark', 'id');
		if ($this->result) {
			foreach ($machine as $item) {
				// 多个机审代替人审配置
				$valid = true;
				$scene_ids = explode(',', $item['scene_ids']);
				foreach ($scene_ids as $sce) {
					if (!in_array($sce, $this->filterMachine)) {
						// 前面服务未通过
						if (!isset($serverScene[$sce]) || !isset($this->result[$serverScene[$sce]]) || $this->result[$serverScene[$sce]]['machine'] != $item['valid_state']) {
							// 不满足
							$valid = false;
						} else {
							// 满足
							$this->filterMachine[] = $sce;
						}
					}
				}
				if ($valid) {
					$this->result['machine_valid'] = true; // 机审代替人审生效
					$this->result['type'] = $item['type'];
					break;
				}

			}
		}
	}
}
