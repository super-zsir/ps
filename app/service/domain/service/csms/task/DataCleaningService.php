<?php

namespace Imee\Service\Domain\Service\Csms\Task;

use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsChoiceField;
use Imee\Models\Xss\CsmsTaskLog;
use Imee\Service\Domain\Service\Csms\Choice\ChoiceFieldService;
use Imee\Service\Domain\Service\Csms\Choice\ChoiceService;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

/**
 * 内容接收
 * Class CsmsPushService
 * @package Imee\Service\Domain\Service\Csms\Task
 */
class DataCleaningService
{
    use CsmswarningTrait;
    use CsmsTrait;
	use UserInfoTrait;


	public $type;

	/**
	 * @var array 清洗元数据
	 */
	public $checkData = [];


	/**
	 * @var array 格式化数据
	 */
	public $formatData = [];

	/**
	 * @var array 清洗后数据
	 */
	public $cleanData = [];



	public $taskId;


	public $choiceInfo = [];

	/**
	 * @param $data
	 * @param string $type
	 */
	public function handle($data, $type = 'nsq')
	{
	    $this->type = $type;
		$this->init($data);
		switch ($type){
			case CsmsConstant::CSMS_NSQ:
				$this->nsqClean();
				break;
			case 'kafka':
				$this->kafkaClean();
				break;
			case 'api':
				$this->apiClean();
				break;
			case 'rpc':
				$this->rpcClean();
				break;
			case 'binlog':
				$this->binlogClean();
				break;
			default:
				break;
		}

		// 生成清洗数据
		$this->cleanData();

//if(ENV == 'dev'){
//	print_r($this->checkData);
//	print_r($this->formatData);
//	print_r($this->cleanData);
//}
		// 增加清洗日志 - 不论是否成功
		$this->addLog();

		// 数据清洗异常、发预警消息
		if (!$this->cleanData || !isset($this->cleanData['fields']) || !$this->cleanData['fields']) {
		    // 如果审核项已下线 不发预警
            if($this->choiceInfo && $this->choiceInfo['state'] != CsmsConstant::STATE_NORMAL){
                return [];
            }
            $this->cleanError();
			return [];
		}

		return $this->cleanData;
	}

	/**
	 * 初始化
	 * @param $data
	 */
	public function init($data)
	{
		$this->initTaskid();
		$this->checkData = $data;
	}

	/**
	 * 初始化taskid
	 */
	public function initTaskid()
	{
		$this->taskId = md5(json_encode($this->checkData).uniqid());
	}




	/**
	 * nsq 来源数据清洗
	 * 包含 binlog、textnsq、csmsnsq的
	 */
	public function nsqClean()
	{
		$data = $this->checkData;
		if (isset($data['cmd'])) {
			switch ($data['cmd']){
				case "add":
					$this->addFormat();
					break;
				case "csms.push":
					$this->pushFormat();
					break;
				default:
					Helper::console(array_merge($data, [CsmsConstant::CSMS_MSG => '内容安全nsq数据接入异常']));
			}
		}
	}


	/**
	 * kafka 来源数据清洗
	 * @param array $data
	 */
	public function kafkaClean()
	{

	}

	public function apiClean()
	{

	}


	public function rpcClean()
	{

	}



	/**
	 * binlog转换数据
	 */
	public function binlogClean()
	{

	}




	/**
	 * 旧nsq转换数据
	 */
	public function addFormat()
	{

	}

	/**
	 * 新格式转换数据
	 */
	public function pushFormat()
	{
		if(isset($this->checkData['data']) && $this->checkData['data']){
			$checkData = $this->checkData['data'];
			// 数据检查 - TODO
			if(!isset($checkData['choice']) || !isset($checkData['pk_value']) || !$checkData['uid']) return false;
			if(!isset($checkData['content'])) return false;

			$afterArr = array_filter(array_column($checkData['content'], 'after'));
			if(empty($afterArr)){
				return false;
			}

			$choiceService = new ChoiceService();
			$choiceInfo = $choiceService->info([
				'choice' => $checkData['choice']
			]);

			// 审核项未配置，发预警 - TODO CSMS
			if(!$choiceInfo){
				return false;
			}
			$this->choiceInfo = $choiceInfo;

			// 审核项下线，数据还在进入 - TODO CSMS
			if($choiceInfo['state'] != CsmsConstant::STATE_NORMAL){
				return false;
			}

			$checkData['type'] = $choiceInfo['type'];

			// 获取审核项字段
			$choiceFieldService = new ChoiceFieldService();
			$choiceField = $choiceFieldService->info([
				'choice' => $checkData['choice']
			]);

			// TODO CSMS 审核项字段未配置
			if(!$choiceField){
				return false;
			}

			foreach ($checkData['content'] as $checkField){

				// 获取指定审核项 指定字段 配置的field_id
				$choiceFieldBind = [
					'state' => CsmsConstant::STATE_NORMAL,
					'choice' => $checkData['choice'] ?? '',
					'field' => $checkField['field'] ?? '',
                    'type' => $checkField['type'] ?? '',
                    'orderBy' => 'sort',
                    'columns' => ['id', 'type']
				];
                $choiceFieldBind = $this->filter($choiceFieldBind);
				$choiceFieldInfo = CsmsChoiceField::handleList($choiceFieldBind);
				if(!$choiceFieldInfo) continue;
				if(count($choiceFieldInfo) > 1){
					// TODO CSMS 抛预警，一个字段多个 类型，必须指定
					continue;
				}
				$choiceFieldInfo = current($choiceFieldInfo);
				$checkField['field_id'] = $choiceFieldInfo['id'];
				$checkField['type'] = $choiceFieldInfo['type'];
				$checkData['fields'][] = $checkField;
			}

			unset($checkData['content']);

			$this->formatData = $checkData;
		}

	}


	/**
	 * 将组装数据转换为清洗数据
	 */
	public function cleanData()
	{
		if($this->formatData){
			$formatData = $this->formatData;
			// 任务ID
			$formatData['taskid'] = $this->taskId;
			// 任务优先级
			$formatData['level'] = $this->checkData['data']['level'] ?? 0;
			// 先审后发命中的风控规则ID
			$formatData['strategy'] = $this->checkData['data']['strategy'] ?? 0;
			// 根据UID 补充app_id

            $formatDataUser = $this->getUserInfoModel([$formatData['uid']])->handle();
			$formatData['app_id'] = (isset($this->checkData['data']['app_id']) && $this->checkData['data']['app_id']) ? $this->checkData['data']['app_id'] : $formatDataUser[$formatData['uid']]['app_id'];
			$uid = $this->checkData['data']['uid'] ?? 0;
			if($uid){
				$user = $this->getUserInfoModel([$uid])->handle();
				$user = $user[$uid] ?? [];
				$formatData['sex'] = $user['sex'] ?? 0;
			}else{
				$formatData['sex'] = 0;
			}

			$this->cleanData = $formatData;
		}
	}



	/**
	 * 增加清洗日志
	 */
	public function addLog()
	{
        $csmsTaskLog = new CsmsTaskLog();
        $log = [
            'app_id' => $this->cleanData['app_id'] ?? 0,
            'choice' => $this->checkData['data']['choice'] ?? '',
            'taskid' => $this->taskId,
            'pk_value' => $this->checkData['data']['pk_value'] ?? 0,
            'uid' => $this->checkData['data']['uid'] ?? 0,
            'check_data' => json_encode($this->checkData),
            'format_data' => json_encode($this->formatData),
            'clean_data' => json_encode($this->cleanData),
            'dateline' => time()
        ];
        $csmsTaskLog->save($log);
	}


	/**
	 * 清理错误预警
	 */
	public function cleanError()
	{
		$content = <<<STR
csms数据清理异常
> taskId: {taskId}
> checkData: {checkData}
> formatData: {formatData}
> cleanData: {cleanData}
> DATE: {date}
STR;
		$wechatMsg = str_replace(
			['{taskId}', '{checkData}', '{formatData}', '{cleanData}', '{date}'],
			[$this->taskId, json_encode($this->checkData), json_encode($this->formatData), json_encode($this->cleanData),  date('Y-m-d H:i:s')],
			$content
		);
		$this->sendCsms($wechatMsg);
	}



}