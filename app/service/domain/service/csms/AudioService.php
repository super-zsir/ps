<?php


namespace Imee\Service\Domain\Service\Csms;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Sdk\SdkShumei;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xss\CsmsAudioLog;
use Imee\Models\Xss\CsmsAudit;

/**
 * 音频类
 * Class AudioService
 * @package Imee\Service\Domain\Service\Csms
 */
class AudioService
{
    const TYPE_PASS = 'PASS';
    const TYPE_REVIEW = 'REVIEW';
    const TYPE_REJECT = 'REJECT';

    /**
     * 上传音频检测
     * @param array $data
     * @return bool|void
     * @throws \Exception
     */
	public function upload($data = [])
	{
		if(ENV == 'dev'){
			return $this->uploadAudio($data);
		}else{
			return NsqClient::publish(NsqConstant::TOPIC_CSMS_AUDIO, [
				'cmd' => 'audio.upload',
				'data' => $data
			]);
		}
	}

    /**
     * 开始上传音频文件
     * @param array $data
     * @return bool|void
     * @throws \Exception
     */
	public function uploadAudio($data = [])
	{
		$checkType = 'AD_PORN_POLITICAL_MOAN';
		$audio = $this->getAudioUrl($data);

		$sdk = new SdkShumei();
		$btid = $data['dataid'];
		$res = $sdk->uploadUrlAudio($btid, $audio, $checkType);

		$logData = array_merge($data, [
			'dataid' => $btid,
			'checkType' => $checkType,
			'response' => $res
		]);

		$this->addLog($logData);

		// 记录成功后，发送获取检测结果请求
		if ($res && $res['code'] && $res['code'] == SdkShumei::SUCCESS_CODE) {
			if(ENV == 'dev'){
				sleep(10);
				return $this->query([
					'btid' => $btid
				]);
			}else{
				return NsqClient::publish(NsqConstant::TOPIC_CSMS_AUDIO, array(
					'cmd' => 'audio_query',
					'data' => array(
						'btid' => $btid
					)
				), 5);
			}
		}
	}

	/**
	 * 获取图片地址
	 * @return string
	 */
	public function getAudioUrl($data = [])
	{
		$filename = '';
		// url类型的
		if($data['type'] == 'url'){
			$audio = $data['content'];
            $audio = str_replace('http:', 'needhttp', $audio);
            $audio = str_replace('https:', 'withhttps', $audio);
			$length = strrpos($audio, ":");
			$filename = $length ? substr($audio, 0, $length) : $audio;
            $filename = str_replace('needhttp', 'http:', $filename);
            $filename = str_replace('withhttps', 'https', $filename);
		}
		return $filename;
	}


	public function addLog($data)
	{

		$choice = $data['choice'] ?? '';
		$pk = $data['pk'] ?? '';
		$dataid = $data['dataid'] ?? '';
		$model = CsmsAudioLog::findFirst([
			'conditions' => 'dataid = :dataid:',
			'bind' => [
				'dataid' => $dataid
			]
		]);
		if(!$model){
			$model = new CsmsAudioLog();
		}
		$model->choice = $choice;
		$model->pk = $pk;
		$model->taskid = $data['taskid'];
		$model->dataid = $data['dataid'];
		$model->check_type = $data['checkType'];
		$model->audio = $data['content'];
		$model->dateline = time();
		$model->btid = $data['dataid'];
		$model->save();
	}


	/**
	 * 查询检测结果
	 */
	public function query($data = [])
	{
		$btid = $data['btid'] ?? '';
		if(!$btid) return false;
		$sdk = new SdkShumei();
		$res = $sdk->query_audio($btid);

		// 日志
		if($res['code'] == SdkShumei::SUCCESS_CODE){
			// 更新日志
			$this->updateLog([
				'btid' => $btid,
				'response' => $res
			]);
			return false;
		}
		// 查询失败，重复查询
		return true;
	}


	/**
	 * 更新数美检测结果
	 * @param array $data
	 * @return false
	 */
	public function updateLog($data = [])
	{
		$btid = $data['btid'] ?? '';
		if(!$btid) return false;
		$log = CsmsAudioLog::findFirst([
			'conditions' => 'btid = :btid:',
			'bind' => [
				'btid' => $btid
			]
		]);
		$response = $data['response'] ?? [];
		if($log && $response && $response['code'] && $response['code'] == SdkShumei::SUCCESS_CODE){
			// 具体违规类型
			$detail = isset($response['detail']) ? json_decode($response['detail'], true) : [];

			$log->audio_text = $response['audioText'] ?? '';
			$log->audio_time = $response['audioTime'] ?? 0;
			$log->risk_level = $response['riskLevel'];
			$log->risk_type = isset($detail['riskType']) ? $detail['riskType'] : 0;
			$log->response = json_encode($response);
			$log->save();
            // 更新机审结果
            $try = 3;
            while ($try > 0) {
                $try --;
                $audit = CsmsAudit::handleOne(array(
                    'taskid' => $log->taskid,
                ));
                if ($audit) {
                    if (!in_array($audit->machine, [CsmsAudit::MACHINE_REFUSE, CsmsAudit::MACHINE_DANGER])) {
                        if ($response['riskLevel'] == self::TYPE_REJECT) {
                            $audit->machine = CsmsAudit::MACHINE_REFUSE;
                        } elseif ($response['riskLevel'] == self::TYPE_PASS) {
                            $audit->machine = CsmsAudit::MACHINE_PASS;
                        }
                        $audit->save();
                    }
                    break;
                } else {
                    usleep(200000);
                }
            }

		}

	}

}