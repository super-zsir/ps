<?php

namespace Imee\Comp\Common\Log\Models\Xsst;


use Imee\Service\Helper;

class XsstNoticeLog extends BaseModel
{
    public static $primaryKey = 'id';

    const STATUS_WAIT = 0;
    const STATUS_SENDING = 1;

    /**
     * 获取通知数量
     * @param array $nids
     * @return array
     */
    public static function getNoticeCount(array $nids): array
    {
        $list = self::getListByWhere([
            ['nid', 'IN', $nids]
        ], 'count(*) as count, nid', 'nid desc', 0, 0, 'nid');

        return $list ? array_column($list, 'count', 'nid') : [];
    }

    /**
     * 获取记录列表并关联出webhook
     * @return array
     */
    public static function getSendNoticeList(): array
    {
        $sql = <<<SQL
SELECT
	l.*,
	g.webhook
FROM
	xsst_notice_log AS l
	LEFT JOIN xsst_notice_config AS n ON l.nid = n.id
	LEFT JOIN xsst_notice_group_config AS g ON n.gid = g.id 
WHERE
	l.STATUS =0
SQL;
        return Helper::fetch($sql, null, self::SCHEMA_READ);

    }
}