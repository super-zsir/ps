<?php

namespace Imee\Models\Xs;


class XsUserBigarea extends BaseModel
{
	public static $primaryKey = 'uid';

    public static function getUserBigareas(array $uid_arr): array
    {
        $data = self::find([
            'columns' => 'uid,bigarea_id',
            'conditions' => 'uid IN({uid:array})',
            'bind' => ['uid' => $uid_arr]
        ])->toArray();
        if (!empty($data)) {
            $data = array_column($data, 'bigarea_id', 'uid');
        }
        return $data;
    }

    public static function userBigArea($uid)
    {
        $fromTableName = self::getTableName();
        $toTableName = XsBigarea::getTableName();

        $condition = [];
        $condition[] = ["{$fromTableName}.uid", '=', intval($uid)];
        $joinCondition = "{$fromTableName}.bigarea_id = {$toTableName}.id";

        $columns = [
            $toTableName . '.name'
        ];

        $modelsManager = self::modelsManager();
        $builder = $modelsManager->createBuilder()
            ->columns($columns)
            ->addfrom(self::class, $fromTableName)
            ->leftjoin(XsBigarea::class, $joinCondition, $toTableName);
        list($builder, $_) = self::parseCondition($builder, $condition);
        $data = $builder->getQuery()->execute()->toArray();
        return $data[0]['name'] ?? '';
    }

    /**
     * 根据uid批量获取用户大区
     * @param array $uidArr uid
     * @param array $fieldArr 查询的字段
     * @param mix $columns
     * @return array
     */
    public static function getUserBigAreaBatch($uidArr = [], $fieldArr = ['uid', 'bigarea_id'], $columns = null)
    {
        if (empty($uidArr)) {
            return [];
        }
        if (!in_array('uid', $fieldArr)) {
            $fieldArr[] = 'uid';
        }
        $data = self::find(array(
            'columns' => implode(',', $fieldArr),
            'conditions' => "uid in ({uid:array})",
            'bind' => array(
                'uid' => $uidArr,
            ),
        ))->toArray();
        if (empty($data)) {
            return array();
        }

        return array_column($data, $columns, 'uid');
    }

    /**
     * 获取用户area id
     * @param $uid
     * @return string
     */
    public static function getUserArea($uid)
    {
        $userBigArea = self::findFirst([
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
        return $bigArea ? $bigArea->name : 'en';
    }

    public static function checkUidBigArea(array $uids, int $bigAreaId): array
    {
        $absent = [];
        foreach (array_chunk($uids, 200) as $item) {
            $list = self::getListByWhere([
                ['uid', 'in', $item],
                ['bigarea_id','=', $bigAreaId]
            ], 'uid');
            if (empty($list)) {
                $absent = array_merge($absent, $item);
                continue;
            }
            $uids = array_column($list,'uid');
            $diff = array_diff($item, $uids);
            if ($diff) {
                $absent = array_merge($absent, $diff);
            }
        }
        return $absent;
    }

    public static function getUserBigareasChunk(array $uidArr): array
    {
        if (empty($uidArr)) {
            return [];
        }
        $uidChunk = array_chunk($uidArr, 200);
        $dataMap = [];
        foreach ($uidChunk as $uids) {
            $data = self::find(array(
                'columns' => 'uid,bigarea_id',
                'conditions' => "uid in ({uid:array})",
                'bind' => array(
                    'uid' => $uids,
                ),
            ))->toArray();
            if ($data) {
                foreach ($data as $v) {
                    $dataMap[$v['uid']] = $v['bigarea_id'];
                }
            }
        }
        return $dataMap;
    }

}

