<?php

namespace Imee\Models\Xss;

class XssAutoQuestion extends BaseModel
{
    const GUIDE_TO_SERVICE_YES = 1;
    const GUIDE_TO_SERVICE_NO = 0;

    public static $guide_to_service = [
      self::GUIDE_TO_SERVICE_NO => '否',
      self::GUIDE_TO_SERVICE_YES => '是',
    ];

    public static $QUESTION_TYPE = [
		1 => '平台功能',
		2 => '审核问题',
		3 => '平台玩法',
		4 => '平台活动',
		5 => '派对房问题',
		6 => '充值收益',
		7 => '投诉举报',
		8 => '处罚申诉',
		9 => '平台Bug',
		10 => '风控问题',
		11 => '提现问题',
		12 => '身份证问题',
		13 => '账号封禁问题',
		14 => '师徒问题',
		15 => '认证角色问题',
		16 => '礼物问题',
		17 => '公会问题',
		18 => '家族问题',
		19 => '注销问题',
		20 => 'APP问题',
		21 => '其他',
    ];

    public static function findQuestions($tag)
    {
        $res = self::query();
        if (!empty($tag)) {
            $res->andWhere("tag like '%{$tag}%'");
        }
        return $res->execute()->toArray();
    }

    public static function findQuestionTypes(): array
    {
        $res = self::query()
            ->columns('id, type')
            ->execute();

        if (empty($res)) {
            return [];
        }
        $question_types = [];
        foreach ($res as $type) {
            $question_types[$type['id']] = $type['type'];
        }
        return $question_types;
    }

    public static function findQidsByType($type = 0)
    {
        $res = self::query()
            ->columns('id');
        if ($type > 0) {
            $res->where("type = :type:", ['type' => $type]);
        }
        $res = $res->execute()->toArray();

        return array_column($res, 'id');
    }
}
