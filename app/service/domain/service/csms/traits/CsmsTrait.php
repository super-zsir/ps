<?php


namespace Imee\Service\Domain\Service\Csms\Traits;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Helper\Constant\AuditConstant;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Models\Xs\XsUserSettings;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsModulesChoice;
use Imee\Models\Xss\CsmsProduct;
use Imee\Models\Xss\CsmsStaff;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Helper;
use OSS\OssUpload;

trait CsmsTrait
{
    public $common_uid_language = [];

    private $sex = [
        '1' => '男',
        '2'  => '女'
    ];

    public static $allChoiceData = [];
    public static $redisTrait;

	/**
	 * 获取审核项名称
	 * @param string $choice
	 */
	public function getChoiceName($choice = '')
	{
		if($choice){
			$choiceInfo = $this->getChoiceInfo($choice);
			return $choiceInfo ? $choiceInfo['choice_name'] : '';
		}
		return '';
	}

	/**
	 * 获取审核项信息
	 * @param string $choice
	 * @return array
	 */
	public function getChoiceInfo($choice = '')
	{
		if($choice){
			$choiceInfo = CsmsChoice::findFirst([
				'conditions' => 'choice = :choice:',
				'bind' => [
					'choice' => $choice
				]
			]);
			return $choiceInfo ? $choiceInfo->toArray() : [];
		}
		return [];
	}


	/**
	 * 审核主管删除任务池
	 * @param $module
	 * @param $choice
	 * @param $id
	 */
	public function managerDeleteRedisTask($module, $choice, $id)
	{
		$redis = new RedisBase(RedisBase::REDIS_H5);
		$moduleUserKey = AuditConstant::REDIS_MODULE_USER.$module.'-'.$choice;
		$moduleUsers = $redis->sMembers($moduleUserKey);

		$redisKey = AuditConstant::REDIS_STAFF_TASK_PRE.$module.'-'.$choice;
		foreach ($moduleUsers as $admin) {
			$redisAdminKey = $redisKey.'-'.$admin;
			$userTask = $redis->sMembers($redisAdminKey);
			if (in_array($id, $userTask)) {
				$redis->sRem($redisAdminKey, $id);
			}
		}
	}

    /**
     * 根据cid 获取审核项名称
     * @param int|string $cid
     */
    public function getChoiceNameByCid(int $cid = 0)
    {
        if($cid){
            $choice = CsmsChoice::findOne($cid);
            return $choice ? $choice['choice_name'] : '';
        }
        return '';
    }


    /**
	 * 获取app 名称
	 * @param $appid
	 * @return string
	 */
	public function getAppName($appid)
	{
		$app = CsmsProduct::findFirst([
			'conditions' => 'app_id = :app_id:',
			'bind' => [
				'app_id' => $appid
			]
		]);
		return $app ? $app->name : '';
	}

	/**
	 * 获取审核阶段（模块）名
	 * @param string $module
	 */
	public function getModuleName($module = '')
	{
		$csmsModule = CsmsModules::findFirst([
			'conditions' => 'module = :module:',
			'bind' => [
				'module' => $module
			]
		]);
		return $csmsModule ? $csmsModule->module_name : '';
	}


	// 获取员工权限 - 扔到process
	public function getStaffPower($admin = '', $context = null)
	{
		$conditions = $bind = [];
		$conditions[] = 'user_id = :user_id: and state = :state:';
		$bind['user_id'] = $admin;
		$bind['state'] = CsmsUserChoice::STATUS_NORMAL;

		if (isset($context->module) && $context->module) {
			$conditions[] = "module = :module:";
			$bind['module'] = $this->context->module;
		}
		if (isset($context->choice) && $context->choice) {
			$conditions[] = "choice = :choice:";
			$bind['choice'] = $context->choice;
		}

		$all = CsmsUserChoice::find([
			'conditions' => implode(" and ", $conditions),
			'bind' => $bind
		])->toArray();
		if (!$all) {
			return [];
		}
		$data = [];
		foreach ($all as $key => $value) {
			if (!isset($data[$value['module']]) || !in_array($value['choice'], $data[$value['module']])) {
				$data[$value['module']][] = $value['choice'];
			}
		}
		return $data;
	}


	public function getAllModule()
	{
		return CsmsModules::findAll();
	}

	public function getAllChoice()
	{
		return CsmsModulesChoice::findAll();
	}

	public function getModuleChoice()
	{

		$module = CsmsModules::find([
			'conditions' => 'state = :state:',
			'bind' => [
				'state' => CsmsModules::STATUS_NORMAL
			]
		])->toArray();
		if (!$module) {
			return $module;
		}
		$moduleIds = array_values(array_unique(array_column($module, 'mid')));
		$moduleChoice = array_column($module, null, 'mid');
		$choice = CsmsModulesChoice::find([
			'conditions' => 'mid in ({mid:array}) and state = :state:',
			'bind' => [
				'mid' => $moduleIds,
				'state' => CsmsModulesChoice::STATUS_NORMAL
			]
		])->toArray();
		if (!$choice) {
			$moduleChoice = $module;
		} else {
			foreach ($choice as $key => $value) {
				$moduleChoice[$value['mid']]['choice'][] = $value;
			}
			$moduleChoice = array_values($moduleChoice);
		}
		return $moduleChoice;
	}

    /**
     * 获取指定模块的 审核数
     * @param string $module
     */
    public function getCacheCount($module = '', $choice = '')
    {
        if (!self::$redisTrait) {
            self::$redisTrait = new RedisBase(RedisBase::REDIS_H5);
        }
        if (!self::$allChoiceData) {
            self::$allChoiceData = self::$redisTrait->hGetAll(AuditConstant::REDIS_CHECKED_CACHE.'-'.$module);
        }
        if($choice){
            return isset(self::$allChoiceData[$choice]) ? (int)self::$allChoiceData[$choice] : 0;
        }else{
            return self::$allChoiceData;
        }
    }

	/**
	 * 是否审核主管
	 * @param array $purview
	 * @return false
	 */
	public function isLeaderPurview($purview = [])
	{
		$uid = Helper::getSystemUid();
		$csmsUser = CsmsStaff::findFirst([
			'conditions' => 'user_id = :user_id:',
			'bind' => [
				'user_id' => $uid
			]
		]);
		$manager = $csmsUser ? $csmsUser->manager : 0;
		// 系统管理员
        $admin = Helper::getSystemUserInfo();
        $super = $admin['super'] ?? 0;
		return ($manager || $super);
	}


	/**
	 * 获取用户的app权限
	 * @param $appId
	 * @param $uid
	 * @param $purview
	 * @return array|false|string[]
	 */
	public function getAllowAppIds($appId, $uid, $purview = [])
	{
		$isLeader = $this->isLeaderPurview($purview);
		if (empty($appId) && $isLeader) {
			return [];
		}
		if (!empty($appId) && $isLeader) {
			return [$appId];
		}

		//获取对应用户当前配置的app_id
		$xsstKefuStaffArr = CsmsStaff::find([
			'conditions' => 'user_id = :uid:',
			'bind' => [
				'uid' => $uid,
			],
			'order' => 'id asc',
		])->toArray();

		$formatKefuStaffArr = array_column($xsstKefuStaffArr, null, 'user_id');
		if (empty($formatKefuStaffArr[$uid])) {
			return [INVALID_APPID];
		}

		$appIds = explode(',', $formatKefuStaffArr[$uid]['app_id']);

		if (empty($appId)) {
			//因为系统中存在app_id为0的数据
			$appIds[] = 0;
			return $appIds;
		}

		if (in_array($appId, $appIds)) {
			return [$appId];
		}
		return [INVALID_APPID];
	}


	/**
	 * 检验appIds的合法性
	 * @param array $appIds
	 * @return boolean
	 */
	public function isValidAppIds($appIds = [])
	{
		$flag = true;
		if (count($appIds) == 1 && $appIds[0] == INVALID_APPID) {
			$flag = false;
		}
		return $flag;
	}


	/**
	 * 判断当前审核项是否有下一审核阶段
	 * @param $choice string 审核项
	 * @param $stage string 当前阶段
	 */
	public function hasNextStage($choice, $stage)
	{
		$stages = array_keys(CsmsConstant::$csms_stages);
		$index = array_search($stage, $stages);
		$stage = $stages[$index] ?? 0;
		if(!$stage) return false;
		$choiceStage = CsmsChoiceStage::findFirst([
			'conditions' => 'choice = :choice: and stage = :stage: and state = :state:',
			'bind' => [
				'choice' => $choice,
				'stage' => $stage,
				'state' => CsmsChoiceStage::STATUS_NORMAL
			]
		]);
		if($choiceStage) return true;
		return false;
	}


	/**
	 * 为防止有些任务已经超时，审核时需检测，任务池是否还在
	 * @param $module
	 * @param $ids
	 */
	public function checkTaskTimeout($module, $choice, $uid, $ids = [])
	{
		$redis = new RedisBase(RedisBase::REDIS_H5);
		$redis_key = AuditConstant::REDIS_STAFF_TASK_PRE.$module.'-'.$choice;
		$task_members = $redis->sMembers($redis_key.'-'.$uid);
		if (!$task_members) {
			return false;
		}
		if (array_diff($ids, $task_members)) {
			return false;
		}
		return true;
	}


	/**
	 * 只指定模块添加 审核数
	 * @param string $module
	 * @return bool
	 */
	public function changeCacheCount($module = '', $data = ['id' => 'choice'])
	{
		if (!$module) {
			return false;
		}
		$redis = new RedisBase(RedisBase::REDIS_H5);
		if ($data) {
			foreach ($data as $key => $value) {
				$redis->hIncrBy(AuditConstant::REDIS_CHECKED_CACHE.'-'.$module, $value, 1);
			}
		}
		$redis->expire(AuditConstant::REDIS_CHECKED_CACHE.'-'.$module, (strtotime(date("Y-m-d", strtotime("+1 day"))) - time()));
		return true;
	}


	/**
	 * 旧审核系统 审核删除新系统的redis
	 * @param $module
	 * @param $choice
	 * @param $id
	 */
	public function oldDeleteRedisTask($module, $choice, $id)
	{
		$redis = new RedisBase(RedisBase::REDIS_H5);
		$moduleUserKey = CsmsConstant::REDIS_MODULE_USER.$module.'-'.$choice;
		$moduleUsers = $redis->sMembers($moduleUserKey);

		$redisKey = CsmsConstant::REDIS_STAFF_TASK_PRE.$module.'-'.$choice;
		foreach ($moduleUsers as $admin) {
			$redisAdminKey = $redisKey.'-'.$admin;
			$userTask = $redis->sMembers($redisAdminKey);
			if (in_array($id, $userTask)) {
				$redis->sRem($redisAdminKey, $id);
			}
		}
	}


	/**
	 * 获取cdn URL
	 * @param $url
	 * @return string
	 */
	public function getCdnVoiceUrl($url)
	{
		if (!$url) return "";
		if (preg_match('/(http|https):\/\/.*/is', $url)) {
			return $url;
		}
		if (preg_match('/public\/static\/icon\/.*/is', $url)) {
			return CDN_VOICE_DOMAIN . $url;
		}
		return CDN_VOICE_DOMAIN . $url;
	}

    /**
     * 获取图片url
     * @param $url
     * @return string
     */
	public function getCdnImageUrl($url)
    {
        if (!$url) return "";
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }
        if (preg_match('/public\/static\/icon\/.*/is', $url)) {
            return CDN_IMG_DOMAIN . $url;
        }
        return CDN_IMG_DOMAIN . $url;
    }

    /**
     * 根据类型获取值
     * @param $value
     * @param $type
     * @return string
     */
	public function getTypeValue($value, $type, $csms= [])
    {
        if($type == CsmsConstant::TYPE_TEXT) return $value;
        if($type == CsmsConstant::TYPE_IMAGE){
            return $this->getChoiceImage($value, $csms);
//            return $this->getCdnImageUrl($value);
        }
        if($type == CsmsConstant::TYPE_AUDIO){
            $value = str_replace('http:', 'needhttp', $value);
            $value = str_replace('https:', 'withhttps', $value);
            $length = strrpos($value, ":");
            $filename = $length ? substr($value, 0, $length) : $value;
            $filename = str_replace('needhttp', 'http:', $filename);
            $filename = str_replace('withhttps', 'https:', $filename);
            return $this->getCdnVoiceUrl($filename);
        }
        if($type == CsmsConstant::TYPE_VIDEO){

        }
        return $value;
    }


    /**
     * 获取图片oss 授权
     * @param $value
     * @param array $csms
     * @return \OSS\Http\ResponseCore|string
     * @throws \OSS\Core\OssException
     */
    public function getChoiceImage($value, $csms = [])
    {
        $path = $this->getCdnImageUrl($value);
        // 视频直播截图要用oss 做授权访问
        if(isset($csms['choice']) && ($csms['choice'] == 'live_video_screen')){
            $bucket = OssUpload::PS_IMAGE;
            $upload = new OssUpload($bucket);
            $client = $upload->client();
            $parsePath = parse_url($value);
            $path = $client->signUrl($bucket, trim($parsePath['path'], '/'), 1800);
        }
        return $path;
    }



    public function getAllChoices()
    {
        $choices = CsmsChoice::find([
            'conditions' => 'state = :state:',
            'bind' => [
                'state' => CsmsChoice::STATUS_NORMAL
            ]
        ])->toArray();
        return $choices;
    }


    /**
     * @param array $condition
     * @return array
     */
    public function filter(array $condition)
    {
        return array_filter($condition, function ($item) {
            if ($item === '' || $item === null || $item === ['']) {
                return false;
            }
            return true;
        });
    }


    public function getUserLanguage($uid)
    {
        if (!isset($this->common_uid_language[$uid])) {
            $userSettings = XsUserSettings::findFirst([
                'columns' => 'language',
                'conditions' => 'uid = :uid:',
                'bind' => [
                    'uid' => $uid
                ]
            ]);
            $this->common_uid_language[$uid] = $userSettings ? $userSettings->language : '';
        }
        return $this->common_uid_language[$uid];
    }


    public function getUserArea($uid)
    {
        $userBigArea = XsUserBigarea::findFirst([
            'columns' => 'bigarea_id',
            'conditions' => 'uid = :uid:',
            'bind' => [
                'uid' => $uid
            ]
        ]);
        if(!$userBigArea) return '';
        $bigArea = XsBigarea::findFirst([
            'columns' => 'name',
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $userBigArea->bigarea_id
            ]
        ]);
        return $bigArea ? $bigArea->name : '';
    }


    /**
     * 获取当前登录用户的语言权限数组
     * @return false|string[]
     */
    public function getStaffLanguage()
    {
        $userinfo = Helper::getSystemUserInfo();
        $language = $userinfo['language'] ?? '';
        return explode(',', $language);
    }


    /**
     * 获取当前登录用户的地区权限
     */
    public function getStaffArea()
    {
        $userinfo = Helper::getSystemUserInfo();
//        $systemArea = $userinfo['bigarea'] ?? [];
//        if($systemArea){
//            $systemAreaIds = explode(',', $systemArea);
//            $systemBigarea = XsBigarea::find([
//                'conditions' => 'id in ({ids:array})',
//                'bind' => [
//                    'ids' => $systemAreaIds
//                ]
//            ])->toArray();
//            $systemArea = array_column($systemBigarea, 'name');
//        }
        $userArea = CsmsStaff::findFirst([
            'conditions' => 'user_id = :uid:',
            'bind' => [
                'uid' => $userinfo['user_id'] ?? 0
            ]
        ]);
        $userArea = $userArea ? explode(',', $userArea->area) : [];
        return $userArea;
//        return array_unique(array_merge($systemArea, $userArea));
    }


    /**
     * 获取用户违规统计
     * @param array $uids
     * @return array
     */
    public function getUserDanger($uids = [])
    {
        // 违规次数
        $refuses = CsmsAudit::find([
            'columns' => 'count(id) as refuse,uid',
            'conditions' => 'uid in ({uids:array}) and dateline >= :time: and deleted = :deleted:',
            'bind' => [
                'uids' => $uids,
                'time' => strtotime('-7 day'),
                'deleted' => CsmsConstant::CSMS_STATE_REJECT
            ],
            'group' => 'uid'
        ])->toArray();
        $refuses = array_column($refuses, null,'uid');
        // 封禁次数 - 待补
        $forbiddens = XsUserForbiddenLog::find([
            'columns' => 'count(id) as forbidden,uid',
            'conditions' => 'uid in ({uids:array}) and dateline >= :time: and deleted > :deleted:',
            'bind' => [
                'uids' => $uids,
                'time' => strtotime('-7 day'),
                'deleted' => 0
            ],
            'group' => 'uid'
        ])->toArray();
        $forbiddens = array_column($forbiddens, null, 'uid');

        foreach ($uids as $uid){
            if(!isset($format[$uid])){
                $format[$uid]['uid'] = $uid;
                $format[$uid]['refuse'] = $refuses[$uid]['refuse'] ?? 0;
                $format[$uid]['forbidden'] = $forbiddens[$uid]['forbidden'] ?? 0;
            }
        }
        return $format;
    }


    /**
     * 根据用户的地区翻译成对应的言语
     * @param $uid
     * @param $text
     */
    public function _translate($uid, $text) {
        $uarea = XsUserSettings::findFirst($uid);
        if ($uarea) {
            $lang = $uarea->language;
        }
        return __T($text, [], strtolower($lang));
    }

    /**
     * 获取数据对应是哪一个选项的-适用文本审核和机审
     * 目前只有 用户头像 是从 用户 里面单独分离出来的标识
     * @param $data
     * @return mixed
     */
    public function getTextChoice($data)
    {
        $table = $data['table'];
        $field = $data['field'];
        if ($table == 'xs_user_profile') {
            if ($field == 'tmp_icon') {
                return 'xs_user_profile1';
            }
            return $table;
        }
        if ($table == 'xs_fleet') {
            if ($field == 'tmp_icon') {
                return 'xs_fleet_icon';
            }
        }
        return $table;
    }

}