<?php

namespace Imee\Models\Xs;

class XsVideoLiveSessionLog extends BaseModel
{
    public static $primaryKey = 'id';

    const REASON_PREFIX = 'liveroom_ended_reason';

    const STATE_NORMAL = 0;
    const STATE_END = 1;
    const STATE_STOP = 2;

    public static $stateMap = [
        self::STATE_NORMAL => '直播中',
        self::STATE_END    => '已结束',
        self::STATE_STOP   => '已中断',
    ];

    const REASON_DISORDER = 1;
    const REASON_POLITICS = 2;
    const REASON_ILLEGAL = 3;
    const REASON_HATEFUL = 4;
    const REASON_PORN = 5;
    const REASON_ADVERT = 6;


    public static $reasonMap = [
        self::REASON_DISORDER => '扰乱平台秩序',
        self::REASON_POLITICS => '时事政治',
        self::REASON_ILLEGAL  => '违法信息',
        self::REASON_HATEFUL  => '低俗恶心',
        self::REASON_PORN     => '淫秽色情',
        self::REASON_ADVERT   => '诈骗广告',
    ];

    public static function getListJoinBigArea(array $conditions, string $joinCondition, int $page = 0, int $pageSize = 0): array
    {
        $fromTableName = self::getTableName();
        $toTableName = XsUserBigarea::getTableName();

        //不能用.*
        $columns = [
            $fromTableName . '.id',
            $fromTableName . '.end_type',
            $fromTableName . '.session_id',
            $fromTableName . '.uid',
            $fromTableName . '.start_time',
            $fromTableName . '.end_time',
            $fromTableName . '.state',
            $fromTableName . '.rid',
            $toTableName . '.bigarea_id',
        ];
        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns('count(*) as cnt')
            ->addfrom(self::class, $fromTableName)
            ->join(XsUserBigarea::class, $joinCondition, $toTableName);
        list($builder, $_) = self::parseCondition($builder, $conditions);
        $total = $builder->getQuery()->execute()->toArray();
        $total = $total[0]['cnt'] ?? 0;
        if (!$total) {
            return ['data' => [], 'total' => 0];
        }


        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, $fromTableName)
            ->join(XsUserBigarea::class, $joinCondition, $toTableName);
        list($builder, $_) = self::parseCondition($builder, $conditions);
        $builder->orderBy('id desc');
        if ($page && $pageSize) {
            $startLimit = ($page - 1) * $pageSize;
            $builder->limit($pageSize, $startLimit);
        }
        $data = $builder->getQuery()->execute()->toArray();
        return ['data' => $data, 'total' => $total];
    }
}