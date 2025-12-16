<?php

namespace Imee\Models\Xsst;

class XsstAutoQuestionLog extends BaseModel
{
    const TYPE_TOUCH_AUTO = 1;
    const TYPE_NOMATCH = 2;
    const TYPE_MATCH_TOMANUAL = 3;
    public static $displayType = [
        self::TYPE_TOUCH_AUTO => '触发了自动回复',
        self::TYPE_NOMATCH => '未匹配自动应答回复指引走人工',
        self::TYPE_MATCH_TOMANUAL => '触发自动回复建议走人工',
    ];

    const IS_SERVICE_YES = 1;
    const IS_SERVICE_NO = 0;
    public static $displayIsService = [
        self::IS_SERVICE_YES => '是',
        self::IS_SERVICE_NO => '否',
    ];

    const VOTE_TYPE_VALID = 1;
    const VOTE_TYPE_INVALID = 0;

    public static $displayVoteType = [
        self::VOTE_TYPE_VALID => '有用',
        self::VOTE_TYPE_INVALID => '无用',
    ];

    public static function findRecords($start_ts, $end_ts, $language, $qids = array())
    {
        if ($start_ts <= 0) {
            return false;
        }
        $rec = self::query()->where("dateline >= :start:", ['start' => $start_ts]);
        if ($end_ts > 0) {
            $rec->andWhere("dateline < :end:", ['end' => $end_ts]);
        }
        if (!empty($language)) {
			$rec->andWhere("language = :language:", ['language' => $language]);
		}
		$rec->andWhere("app_id = :app_id:", ['app_id' => APP_ID]);

        if (!empty($qids)) {
            $rec->inWhere('qid', $qids);
        }
        return $rec->execute()->toArray();
    }
}
