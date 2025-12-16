<?php

namespace Imee\Service\Domain\Service\Csms\Process\Saas;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Saas\InitDataContext;
use Imee\Service\Domain\Service\Csms\Process\Saas\Plugins\AudioPlugin;
use Imee\Service\Domain\Service\Csms\Process\Saas\Plugins\VideoPlugin;
use Imee\Service\Helper;
use Imee\Service\Domain\Service\Csms\RiskService;
use Imee\Service\Domain\Service\Csms\Process\Saas\Plugins\ImagePlugin;
use Imee\Service\Domain\Service\Csms\Process\Saas\Plugins\TextPlugin;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Phalcon\Di;

class InitDataProcess
{

    use CsmsTrait;
    private $context;
    private $riskService;
    private $redis;


    private static $strategyCfg = [
        CsmsConstant::TYPE_TEXT => TextPlugin::class,
        CsmsConstant::TYPE_IMAGE => ImagePlugin::class,
	    CsmsConstant::TYPE_AUDIO => AudioPlugin::class,
        CsmsConstant::TYPE_VIDEO => VideoPlugin::class,
    ];

    public function __construct(InitDataContext $context)
    {
        $this->context = $context;
        $this->riskService = new RiskService;
        $this->redis = Di::getDefault()->getShared('redis');
    }


	/**
	 * 风控检测入口
	 * @return false
	 */
    public function handle()
    {
        if(ENV != 'dev'){
            $flag = $this->verifyRepeat();
            if ($flag) {
                return false;
            }
        }

        //审核没问题的 入宽表（按通过处理，可能存在多个异步结果）
        $last_insert_id = $this->addCsms();
        // 多字段处理
        foreach ($this->context->fieldContexts as $fieldContext) {
            //判断是敏感词还是图片
            $class = new self::$strategyCfg[$fieldContext->type]($fieldContext, $this->context, $this->riskService);
            $result = $class->getResult();

            // 如果已确定违规，直接进审核
            if (isset($result['machine']) && in_array($result['machine'], [CsmsAudit::MACHINE_REFUSE, CsmsAudit::MACHINE_DANGER])) {
                //入宽表
	            $this->addCsms($result, $last_insert_id);
                return false;
            }
        }
        if (isset($result['machine']) && in_array($result['machine'], [CsmsAudit::MACHINE_PASS, CsmsAudit::MACHINE_IDENTIFY])) {
            $this->addCsms($result, $last_insert_id);
        }
        return false;
    }


	/**
	 * 验证是否重复数据
	 * @return boolean
	 */
	private function verifyRepeat()
	{
		if (empty($this->context->fieldContexts)) {
			return true;
		} else {
			$sameFlag = true;
			foreach ($this->context->fieldContexts as $fieldContexts) {
				if ($fieldContexts->before != $fieldContexts->after) {
					$sameFlag = false;
				}
			}
			if ($sameFlag) {
				//抛错，不需要往下执行
				return true;
			}
		}
		return false;
	}


	/**
	 * 插入审核表
	 * @param string $reason
	 * tags
	 * machine
	 * reason
	 */
	private function addCsms($reason = [], $last_insert_id = 0)
	{
        if ($last_insert_id) {
            $model = CsmsAudit::handleOne(array(
                'id' => $last_insert_id
            ));
            // 当前审核项已违规则不再更新机审状态
            if (!in_array($model->machine, CsmsConstant::UNCHANGE_STATE)) {
                $model->machine = $reason['machine'] ?? CsmsAudit::MACHINE_UNKNOWN;
            }
            if (!empty($reason['reason'])) {
                $model->machine_reason = $reason['reason'] ?? '';
            }
            $tags = (isset($reason['tags']) && $reason['tags']) ? implode(',', $reason['tags']) : '';
            if (empty($model->tags) && !empty($tags)) {
                $model->tags = $tags;
            }
            $model->save();
            if (isset($reason['machine_valid']) && $reason['machine_valid']) {
                NsqClient::publish(NsqConstant::TOPIC_CSMS_NSQ, array(
                    'cmd' => 'csms.machine',
                    'data' => $model->toArray(),
                ));
            }
            return $last_insert_id;
        } else {
            $model = new CsmsAudit();
            $data = [
                'app_id' => $this->context->appId ?: 0,
                'uid' => $this->context->uid ?: 0,
                'choice' => $this->context->choice ?: '',
                'type' => $this->context->type ?: '',
                'pk_value' => $this->context->pkValue ?: '',
                'strategy' => $this->context->strategy ?: 0,
                'review' => $this->context->review ? 1 : 0,
                'level' => $this->context->level ?: 0,
                'dateline' => time(),
                'tasktime' => time(),
                'taskid' => $this->context->taskid ?: '',
                'sex' => $this->context->sex,
                'machine_reason' => $reason['reason'] ?? '',
                'deleted' => CsmsConstant::CSMS_STATE_UNCHECK,
                'extra' => $this->context->extra ? json_encode($this->context->extra) : ''
            ];

            $origin = $value = [];
            // 循环组装origin和value
            foreach ($this->context->fieldContexts as $field){
                if($field->before){
                    $origin[] = ['field' => $field->field, 'type' => $field->type, 'value' => $field->before];
                }
                if($field->after){
                    $value[] = ['field' => $field->field, 'type' => $field->type, 'value' => $field->after];
                }
            }
            $data['origin'] = json_encode($origin);
            $data['value'] = json_encode($value);

            // TODO - CSMS 还需要冗余一个机审结果
            $data['machine'] = $reason['machine'] ?? CsmsAudit::MACHINE_UNKNOWN;
            $data['tags'] = (isset($reason['tags']) && $reason['tags']) ? implode(',', $reason['tags']) : '';

            // 查询用户的语言和 大区
            $data['language'] = $this->getUserLanguage($data['uid']);
            $data['area'] = $this->getUserArea($data['uid']);

            // 异常调试
            $pkValue = $data['pk_value'] ?? '';
            if(in_array($pkValue, ['818042949', '818177800'])){
                echo 'csmsauditerror：';
                var_dump($data);
            }


            $res = $model->save($data);

            if(in_array($pkValue, ['818042949', '818177800'])){
                echo 'csmsauditerror：';
                var_dump($res);
            }

            // 更新复审数量缓存
            if ($this->redis->hExists('CsmsAudit:Deleted', $this->context->choice)) {
                $this->redis->hIncrBy('CsmsAudit:Deleted', $this->context->choice, 1);
            }
            Helper::console('CsmsAddData: '.json_encode($data));
            return $model->getWriteConnection()->lastInsertId($model->getSource());
        }
	}




}
