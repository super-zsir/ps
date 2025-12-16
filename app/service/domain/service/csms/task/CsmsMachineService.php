<?php

namespace Imee\Service\Domain\Service\Csms\Task;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Sdk\SdkSpam;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsUserLoginInfo;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsSpamLog;
use Imee\Models\Xss\XssReport;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Phalcon\Di;

/**
 * 内容安全管理 机审服务
 * Class CsmsMachineService
 * @package Imee\Service\Domain\Service\Csms\Task
 */
class CsmsMachineService
{
    use CsmsTrait;
	/**
	 * 思路：
	 * 写入csms库后，根据value 来判断
	 * 如果全是text 类型 拼接过spam
	 * 如果是image 直接取 machine 结果
	 * 如果是audio 不处理
	 * 如果是video 不处理
	 *
	 * @param array $data
	 * @return false
	 */
	public function handle($data = [])
	{
		$id = $data['id'] ?? 0;
		$this->id = $id;
		if (!$id) return false;
		$this->machine();
		return false;
	}

	public function machine()
	{
		$csms = CsmsAudit::findFirstValue($this->id);
		if (!$csms) return false;

		$content = $csms['value'];
		$content = json_decode($content, true);
		$types = array_values(array_unique(array_column($content, 'type')));

		// 一种类型的的
		if (count($types) == 1) {

			// 目前只处理纯文本的
			$type = current($types);
			if ($type == CsmsConstant::TYPE_TEXT) {
				$this->ctext($csms);
			}

			// 纯图片的
            if($type == CsmsConstant::TYPE_IMAGE){
                $this->cimage($csms);
            }


		} else {
			// 混合类型的
			return false;
		}
		return false;
	}


	public $personCheckChoice = [
	    'xs_user_name',
        'xs_user_sign',
        'xs_chatroom_name',
        'xs_chatroom_description',

        'xs_user_icon',
        'xs_user_photos',
        'room_custom_background',
        'xs_chatroom_cover'
    ];

    /**
     * 是否机审代替人审
     */
	public function reviewCheck($csms)
    {
        $choice = $csms['choice'];
        // 获取审核项类型
        $choiceInfo = CsmsChoice::findFirst([
            'conditions' => 'choice = :choice:',
            'bind' => [
                'choice' => $choice
            ]
        ]);
        if(!$choiceInfo) return false;
        // 全量人审审核项
        if(in_array($choice, $this->personCheckChoice)) return false;
        // 机审结果不是通过的，不处理
        if($csms['machine'] != CsmsConstant::CSMS_STATE_PASS) return false;
        // 用户当天被举报过的，不处理
        $report = XssReport::findFirst([
            'conditions' => 'rid = :rid: and to = :uid: and dateline >= :time:',
            'bind' => [
                'rid' => 0,
                'uid' => $csms['uid'],
                'time' => strtotime(date('Y-m-d', time()))
            ]
        ]);
        if($report) return false;
        // did 关联账号
        $did = XsUserLoginInfo::findFirst([
            'conditions' => 'uid = :uid:',
            'bind' => [
                'uid' => $csms['uid']
            ]
        ]);
        if(!$did) return false;
        $userDids = XsUserLoginInfo::find([
            'conditions' => 'did = :did:',
            'bind' => [
                'did' => $did->did
            ]
        ])->toArray();
        $uids = array_column($userDids, 'uid');
        if($uids){
            $users = XsUserProfile::find([
                'conditions' => 'uid in ({uids:array})',
                'bind' => [
                    'uids' => array_values($uids)
                ]
            ])->toArray();
            if($users){
                foreach ($users as $user){
                    if($user['deleted']){
                        return false;
                    }
                }
            }
        }
        return true;
    }


    /**
     * 存文本 处理spam机审
     * @param $data
     * @return false
     */
	public function ctext($csms = [])
	{
		$content = $csms['value'];
		$content = json_decode($content, true);

		$values = array_column($content, 'value');
        $texts = '';
        if($values){
            foreach ($values as $text){
                if(is_array($text)){
                    $texts .= implode('', $text);
                }else{
                    $texts .= $text;
                }
            }
        }
        if(!$texts) return false;
        // 是否机审代替人审 @todo 机审代替人审后面改成配置
        $review = $this->reviewCheck($csms);
        if(!$review) return false;


        // 文本入审规则
        $language = $csms['language'];
        if(in_array($language, ['zh_cn', 'zh_tw'])){
            //1、非汉子长度超过5，且占比超20%
            $languageText = preg_replace('/([\x80-\xff]*)/i', '', $texts);
            if(mb_strlen($languageText) > 5){
                $languageTextLength = mb_strlen($languageText);
                $textLength = mb_strlen($texts);
                if($languageTextLength / $textLength * 100 > 20){
                    return false;
                }
            }
        }


        if ($csms['machine'] == CsmsConstant::CSMS_STATE_PASS) {
            $this->spamPass([
                'user_id' => CsmsConstant::SYSTEM_OP,
                'deleted' => $csms['machine'],
                'id' => $csms['id']
            ]);
        }
	}

    public function spam(array $data)
    {
//        $csms = CsmsAudit::handleOne(array(
//            'taskid' => $data['taskid']
//        ));
//        if (!$csms) {
//            return false;
//        }
        $language = $this->getUserLanguage($data['uid']);
        $texts = $data['content'];

        $spam_params = [

        ];
        $spam = new SdkSpam();

        $logParams = [
            'choice' => $data['choice'],
            'pk' => $data['pkValue'],
            'taskid' => $data['taskid'],
        ];

        $spam_result = $spam->checkParmText($texts, $language, $spam_params, $logParams);

        // 如果通过 直接改掉初审状态
        if ($spam_result) {
            $this->spamLog([
                'choice' => $data['choice'],
                'pk' => $data['pkValue'],
                'taskid' => $data['taskid'],
                'context' => $texts,
                'language' => $language,
                'params' => $spam_params,
                'res' => $spam_result
            ]);

            if (isset($spam_result['is_spam'])) {
                $deleted = $spam_result['is_spam'] + 1;
				$res = [];
				foreach ($data['scenes'] as $scene) {
					$res[$scene] = array(
						'machine' => $deleted
					);
				}
				$res['machine'] = $deleted;
                return $res;
            }
        }
        return false;
    }


	public function spamLog($logParam = [])
    {
        $logModel = new CsmsSpamLog();
        $logModel->cid = $logParam['id'] ?? 0;
        $logModel->choice = $logParam['choice'] ?? '';
        $logModel->pk = $logParam['pk'] ?? '';
        $logModel->taskid = isset($logParam['taskid']) ? $logParam['taskid'] : '';
        $logModel->content = isset($logParam['context']) ? $logParam['context'] : '';
        $logModel->language = $logParam['language'] ?? '';
        $logModel->params = json_encode($logParam['params']);
        $logModel->is_spam = isset($logParam['res']['is_spam']) ? $logParam['res']['is_spam'] : '-1';
        $logModel->spam_rate = isset($logParam['res']['spam_rate']) ? $logParam['res']['spam_rate'] : '';
        $logModel->model_ver = isset($logParam['res']['model_ver']) ? $logParam['res']['model_ver'] : '';
        $logModel->dateline = time();
        $logModel->save();
    }



	/**
	 * 文本审核
	 * @param array $where
	 * @return bool
	 */
	public function spamPass($where = [])
	{
		$op_id = isset($where['user_id']) ? $where['user_id'] : '';
		$deleted = isset($where['deleted']) ? $where['deleted'] : 0;
		$id = isset($where['id']) ? $where['id'] : 0;
		$rec = CsmsAudit::findFirst([
			'conditions' => 'id = :id:',
			'bind' => [
				'id' => $id
			]
		]);
		if(!$rec || !in_array($deleted, CsmsConstant::$allow_state)){
			return false;
		}
		$rec->deleted = $deleted;
		$rec->op_dateline = time();
		$rec->op = $op_id;
		$rec->deleted2 = CsmsConstant::CSMS_STATE_UNCHECK;
		$rec->save();
        // 更新复审数量缓存
        $redis = Di::getDefault()->getShared('redis');
        if ($redis->hExists('CsmsAudit:Deleted2', $rec->choice)) {
            $redis->hIncrBy('CsmsAudit:Deleted2', $rec->choice, 1);
        }
		return true;
	}


    /**
     * 纯图片类型
     * 如果机审结果是 严重违规，直接更改初审状态
     * @param array $csms
     */
	public function cimage($csms = [])
    {
        $id = $csms['id'];
        $deleted = $csms['machine'];
        $csmsModel = CsmsAudit::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $id
            ]
        ]);
        if(!$csmsModel) return false;
        if($deleted == CsmsAudit::MACHINE_DANGER){
            $csmsModel->deleted = $deleted;
            $csmsModel->op = CsmsConstant::SYSTEM_OP;
            $csmsModel->op_dateline = time();
            $csmsModel->save();

            // 发送初审外显
            NsqClient::publish(NsqConstant::TOPIC_CSMS_REVIEW, array(
                'cmd' => 'csms.verify',
                'data' => $csmsModel->toArray(),
            ));
        }
    }

    public function opValid(array $audit)
    {
        if (in_array($audit['machine'], [CsmsConstant::CSMS_STATE_PASS, CsmsConstant::CSMS_STATE_DELETE])) {

        }
    }

}