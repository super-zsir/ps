<?php


namespace Imee\Service\Domain\Service\Csms\Task;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Models\Xss\CsmsReviewLog;
use Imee\Service\Domain\Service\Csms\Process\Callback\TextCsmsProcess;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;
use Imee\Service\Helper;

class ReviewBaseService
{

    use CsmswarningTrait;

    public $stage;

	// 原始数据 - csmsaudit
    public $data;
    // 外显数据
    public $reviewData;
    // 返回结果
    public $result = [
        'state' => false,               // true 发送成功， false 失败
        'message' => '',                // 发送失败错误信息
        'result' => [                   // 业务具体返回结果

        ]
    ];
    // 外显配置
    public $choiceReview;


    public $textChoice = [
        'xs_user_name',
        'xs_user_sign',
        'xs_user_icon',
        'xs_user_photos',
        'xs_chatroom_name',
        'xs_chatroom_description',
//        'xs_chatroom_icon',
        'xs_fleet_name',
        'xs_fleet_description',
//        'xs_fleet_icon',
    ];



    /**
     * @param $data
     */
	public function handle($data, $stage = '', $reviewConfig = [])
    {
        $this->data = $data;
        $this->stage = $stage;

        $choice = $data['choice'] ?? '';

        // 从PT 后台传回来的配置
        if($reviewConfig){
            $choiceReview = $reviewConfig;
        }else{
            $choiceReview = $this->getChoiceReview($choice, $stage);
        }


        // 没有配置外显 - TODO CSMS 发预警
        if (!$choiceReview || !$choiceReview['review']) {
            return false;
        }

        // 外显组装数据
        $this->reviewData();

        // 外显配置
        $this->choiceReview = $choiceReview;
        $choice = camel_case($choice);

        // 外显处理
        if ($choiceReview['review'] != 'csms') {
            // 验证外显配置是否正确
            $this->otherReview($data, $choiceReview);
        } else {

            // 转老系统的
            if(in_array($data['choice'], $this->textChoice)){
                $this->textChoicePush();
            }else if(method_exists($this, $choice)){
                // 自己处理 - csms
                $this->{$choice}();
            }else{
                // 自己处理脚本还没写，都敢配置，是不是胆大了
                $content = <<<STR
csmsreview 外显未配置
> 审核项: {choice}
> 审核阶段: {stage}
> 外显配置: {choicereview}
> 数据: {data}
> 日期: {date}
STR;
                $wechatMsg = str_replace(
                    ['{choice}', '{stage}', '{choicereview}', '{data}', '{date}'],
                    [$data['choice'], $stage, json_encode($choiceReview, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')],
                    $content
                );
                $this->sendCsms($wechatMsg);

            }
        }


        // api 接口返回
        if($reviewConfig){
            return $this->result;
        }

        $this->addReviewLog();


        // 发送预警
        if(!$this->result['state']){
            $errcontent = <<<STR
csms审核外显失败【!!!重要，请及时查看!!!】
> 审核项: {choice}
> 审核阶段: {stage}
> 外显配置: {choicereview}
> 数据: {data}
> 结果: {result}
> 日期: {date}
STR;
            $wechatMsg = str_replace(
                ['{choice}', '{stage}', '{choicereview}', '{data}', '{result}', '{date}'],
                [$data['choice'], $stage, json_encode($choiceReview, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($this->result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')],
                $errcontent
            );
            $this->sendCsms($wechatMsg);
        }

        // 如果接口有返回retry，并且是false，表示不重试了
        if(isset($this->result['retry']) && ($this->result['retry'] == 2)){
            return true;
        }



        return $this->result['state'];

    }


    /**
     * 组装外显数据
     */
    public function reviewData()
    {
        $this->reviewData = [
            'choice' => (string)$this->data['choice'],
//            'status' => (int)$this->data['deleted'],
            'pk_value' => (string)$this->data['pk_value'],
            'uid' => (int)$this->data['uid'],
            'review' => (int)$this->data['review'],
            'origin' => json_decode($this->data['origin'], true),
            'value' => json_decode($this->data['value'], true),
            'uniqid' => uniqid().rand(1, 100),
//            'admin' => $this->data['op'],
            'extra' => json_decode($this->data['extra'], true)
        ];
        // 组装status
        $this->reviewStatus();
    }



    /**
     * nsq kafka 等方式处理
     * @param $data
     * @param $choiceReview
     */
    public function otherReview($data, $choiceReview)
    {
        // 外显方式
        $method = $choiceReview['review'];
        // 外显配置
        $config = $choiceReview['info'];
        $configJson = json_decode($config, true);
        $cmd = $configJson['cmd'] ?? $data['choice'];
        $review = [
            'cmd' => $cmd,
            'data' => $this->reviewData
        ];
        return $this->{$method}($review, $config);
    }








	/**
	 * 获取审核外显配置
	 * @param $choice
	 * @param $stage
	 * @return \Phalcon\Mvc\Model
	 */
	public function getChoiceReview($choice, $stage)
	{
		// 判断外显方式 - 如果是nsq kafka api 统一处理
		$choiceReview = CsmsChoiceStage::findFirst([
			'conditions' => 'choice = :choice: and stage = :stage: and state = :state:',
			'bind' => [
				'choice' => $choice,
				'stage' => $stage,
				'state' => 1
			]
		]);
		return $choiceReview ? $choiceReview->toArray() : [];
	}


	/**
	 * nsq 发送
	 * @param $data
	 * @param $config
	 */
	public function nsq($data, $config)
	{
		$config = json_decode($config, true);
		$topic = $config['topic'] ?? '';
		if($topic){
			$publish = NsqClient::csmsPublish($topic, $data);
			// 打印日志
			if($publish){
				$data[CsmsConstant::CSMS_MSG] = 'nsq审核消息发送失败';
				$this->result['state'] = CsmsReviewLog::STATE_FAILED;
				$this->result['message'] = $publish;
			}else{
				$data[CsmsConstant::CSMS_MSG] = 'nsq审核消息发送成功';
				$this->result['state'] = CsmsReviewLog::STATE_SUCCESS;
			}
			Helper::console($data);
			unset($data);
		}
	}


    /**
     * rpc 处理外显
     * @param $data
     * @param $config
     * @return false
     */
	public function rpc($data, $config)
    {
        $config = json_decode($config, true);
        $servName = $config['servName'] ?? '';
        $method = $config['method'] ?? '';

        if(!$servName || !$method){
            $this->result['message'] = 'rpc审核消息配置错误';
            return false;
        }
        $rpcReview = new CsmsRpcReviewService();
        $result = $rpcReview->handle($data['data'], $config);
        $this->result = $result;
    }


	public function kafka()
	{


	}


    /**
     * 添加审核外显日志
     * @throws \Phalcon\Cache\Exception
     */
	public function addReviewLog()
    {
        $csmsReview = new CsmsReviewLog();
        $csmsReview->app_id = $this->data['app_id'];
        $csmsReview->cid = $this->data['id'];
        $csmsReview->stage = $this->stage;
        $csmsReview->choice = $this->data['choice'];
        $csmsReview->pk_value = $this->data['pk_value'];
        $csmsReview->uid = $this->data['uid'];
        $csmsReview->review = $this->choiceReview['review'];
        $csmsReview->info = json_encode($this->choiceReview, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $csmsReview->data = json_encode($this->reviewData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $csmsReview->state = $this->result['state'] ? 1 : 0;
        $csmsReview->message = $this->result['message'];
        $csmsReview->result = json_encode($this->result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $csmsReview->taskid = $this->data['taskid'];
        $csmsReview->dateline = time();
        $csmsReview->save();
    }


    /**
     * 往老系统转发审核结果
     */
    public function textChoicePush()
    {
        $process = new TextCsmsProcess();
        $this->result = $process->handle($this->reviewData);
    }

}