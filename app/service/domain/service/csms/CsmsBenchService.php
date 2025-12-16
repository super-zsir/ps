<?php


namespace Imee\Service\Domain\Service\Csms;



use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsModules;
use Imee\Service\Domain\Service\Csms\Context\Csmsbench\ClearTaskContext;
use Imee\Service\Domain\Service\Csms\Context\Csmsbench\UserModuleContext;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\ClearTaskProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\GetConfigProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\GetTaskListProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\GetTaskProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\MultPassProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\UserModuleProcess;
use Imee\Service\Domain\Service\Csms\Process\Csmsbench\UserWorkModuleProcess;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Rpc\PsService;
use OSS\OssUpload;


class CsmsBenchService
{

	use CsmsTrait;

	/**
	 * 获取员工审核工作台模块选项权限
	 * @param UserModuleContext $context
	 * @return array
	 */
	public function userModule($params = [])
	{
		$context = new UserModuleContext($params);
		$process = new UserModuleProcess($context);
		return $process->handle();
	}


	public function workModule($params = [])
	{
		$context = new UserModuleContext($params);
		$process = new UserWorkModuleProcess($context);
		return $process->handle();
	}


	public function getConfig($params = [])
	{
		$process = new GetConfigProcess($params);
		return $process->handle();
	}


	/**
	 * 获取任务
	 * @param array $params
	 * @return mixed
	 */
	public function getTask($params = [])
	{
		$process = new GetTaskProcess($params);
		return $process->handle();
	}


	/**
	 * 审核列表
	 * @return array
	 * @throws \ReflectionException
	 */
	public function tasklist($params = [])
	{
		$module = isset($params['module']) ? $params['module'] : '';
		$choice = isset($params['choice']) ? $params['choice'] : '';
		$type = $params['type'] ?? '';
		if (!$module || (!$choice && !$type)) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::TASKLIST_PARAM_ERROR);
		}
		$state = (isset($params['state']) && $params['state'] ) ? $params['state'] : 3;

		// 类型审核 - 支持 多个审核项同时查看
        if($type){
            $power = $this->getStaffPower($params['admin']);
            if(!$power || !isset($power[$module]) || !$power[$module]){
                CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_POWER_NOTEXIST);
            }
            if(isset($params['choice']) && $params['choice']){
                $params['choices'] = [$params['choice']];
            }else{
                $params['choices'] = $power[$module];
            }
        }else{
            $params['choices'] = [$choice];
        }


		if (!$state || $state == 3) {
			$process = new GetTaskListProcess($params);
			$list = $process->handle();
		} else {
			$list = $this->getCheckedList($params);
		}
		return $list;
	}


	/**
	 * 获取已审列表
	 * @param array $where
	 * @return array
	 */
	public function getCheckedList($where = [])
	{
		$user_id = isset($where['admin']) ? $where['admin'] : '';
		$appId = isset($where['app_id']) ? $where['app_id'] : 0;
		$module = isset($where['module']) ? $where['module'] : '';
		$where['app_ids'] = $this->getAllowAppIds($appId, $user_id);

		$flag = $this->isValidAppIds($where['app_ids']);
		if (!$flag) {
			return ['total' => 0, 'data' => []];
		}

		$task_class = CsmsBaseService::getInstance($module);
		$list = $task_class->getCheckedList($where);
		return $list;
	}



	/**
	 * 清理任务
	 * @param array $params
	 * @return bool
	 */
	public function clearTask($params = [])
	{
		$context = new ClearTaskContext($params);
		$process = new ClearTaskProcess($context);
		return $process->handle();
	}

	public function multPass($params = [])
	{
		$process = new MultPassProcess($params);
		return $process->handle();
	}


	// ===============================================

    /**
     * 模块 - 类型 工作台
     * @param array $params
     * @return array
     */
    public function typeBench($params = [])
    {
        $data = [];
        $admin = $params['admin'];
        $power = $this->getStaffPower($admin);
        if(!$power){
            return ['data' => [], 'total' => 0];
        }
        $modules = array_keys($power);
        $modulesInfo = CsmsModules::find([
            'conditions' => 'module in ({modules:array})',
            'bind' => [
                'modules' => $modules
            ]
        ])->toArray();
        $modulesInfo = array_column($modulesInfo, null, 'module');
        foreach ($power as $module => $choices){
            $choicesInfo = CsmsChoice::find([
                'conditions' => 'choice in ({choice:array})',
                'bind' => [
                    'choice' => $choices
                ]
            ])->toArray();
            $one = [
                'module' => $module,
                'module_name' => $modulesInfo[$module]['module_name']
            ];
            $types = [];
            foreach ($choicesInfo as $choice){
                $types[] = $choice['type'];
            }
            $one['types'] = array_unique($types);
            $data[] = $one;
        }

        $back = [];
        foreach ($data as $value){
            foreach ($value['types'] as $type){
                $back[] = [
                    'module' => $value['module'],
                    'module_name' => $value['module_name'],
                    'type' => $type,
                    'type_name' => CsmsConstant::$csms_type[$type]
                ];
            }
        }

        return ['data' => $back, 'total' => count($back)];
    }


    /**
     * 获取审核记录附加详情
     */
    public function attach($params = [])
    {
        $id = $params['id'] ?? 0;
        if(!$id) CsmsWorkbenchException::throwException(CsmsWorkbenchException::PARAMS_ERROR);
        $csms = CsmsAudit::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $id
            ]
        ]);
        if(!$csms) CsmsWorkbenchException::throwException(CsmsWorkbenchException::DATA_NOT_EXIST);
        // 视频封面审核
        if($csms->choice == 'live_video_screen'){
            $api = new PsService();
            $data = [
                'pk_value' => (string)$csms->pk_value,
                'uid' => (int)$csms->uid
            ];

            // 本地模拟数据
            if(ENV == 'dev'){
                $res = [
                    'success' => true,
                    'msg' => '成功',
                    'token' => '006b9a2fe88a3cd4f55ad6ebef7ac8cac30IAAbLbzPVxT/kyIqg2f5nt22Or1cIWDxvdDOb/zm4Y47l6iZfgi6g3hpEABjKIYFiVxrYwEAAQCJXGtj',
                    'channel_name' => '120014717',
                    'uid' => (int)20035707,
                    'app_id' => 'b9a2fe88a3cd4f55ad6ebef7ac8cac30',
                    'cloud_record_path' => (rand(1,10) % 2) ? 'https://partying.oss-ap-southeast-1.aliyuncs.com/cloudrecord/09b25886e549327449e279bc364ffc52_20221102-6z6KfFVH2q9025SewhkB_0.mp4' : ''
                ];
            }else{
                $res = $api->getVideoInfo($data);
            }

            // 获取视频信息错误
            if (empty($res)) {
                CsmsWorkbenchException::throwException(CsmsWorkbenchException::LIVE_VIDEO_INFO_ERROR);
            }
            // 是否获取成功
            if(isset($res['success'])){
                if($res['success']){
                    // 将cloud_record_path 做授权访问
                    $bucket = OssUpload::PS_IMAGE;
                    $upload = new OssUpload($bucket);
                    $client = $upload->client();
                    if($res['cloud_record_path']){
                        $parsePath = parse_url($res['cloud_record_path']);
                        $res['cloud_record_path'] = $client->signUrl($bucket, trim($parsePath['path'], '/'), 1800);
                    }
                    $res['uid'] = (int)$res['uid'];
                    return $res;
                }else{
                    CsmsWorkbenchException::throwException(CsmsWorkbenchException::LIVE_VIDEO_INFO_ERROR, $res, ($res['msg'] ?? '视频信息获取失败'));
                }
            }else{
                CsmsWorkbenchException::throwException(CsmsWorkbenchException::LIVE_VIDEO_INFO_ERROR);
            }
        }else{
            CsmsWorkbenchException::throwException(CsmsWorkbenchException::CHOICE_INFO_ERROR);
        }
    }

}