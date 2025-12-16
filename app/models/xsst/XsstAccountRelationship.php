<?php

namespace Imee\Models\Xsst;

class XsstAccountRelationship extends BaseModel
{

    public static function getValue($uid)
    {
        $data = self::findFirst(['uid=:uid:', 'bind' => ['uid' => $uid]]);
        return $data ? $data->toArray() : false;
    }

    public static function getUserDatas($uids = [])
    {
        if (empty($uids)) return [];

        $data = self::find([
            'uid in ({uids:array})',
            'bind' => ['uids' => $uids],
        ])->toArray();

        return $data;
    }

    public static function getGroup1UserAndGid($uid)
    {
        if (empty($uid)) {
            return [];
        }

        if (!is_array($uid)) {
            $uid = [$uid];
        }

        // 根据用户id查询gid1
        $account = self::find([
            'uid in ({uids:array})',
            'bind'    => ['uids' => $uid],
            'columns' => 'gid1',
        ])->toArray();
        if (!$account) {
            return [];
        }

        return self::find([
            'columns' => 'uid,gid1',
            'gid1 in ({gids:array})',
            'bind'    => ['gids' => array_values(array_unique(array_column($account, 'gid1')))],
        ])->toArray();
    }

    public static function getGroup1User($gids = [])
    {
        if (empty($gids)) return [];

        $data = self::find([
            'columns' => 'uid',
            'gid1 in ({gids:array})',
            'bind'    => ['gids' => array_values($gids)],
        ])->toArray();

        return empty($data) ? [] : array_values(array_unique(array_column($data, 'uid')));
    }

    public static function getGroup2User($gid2 = 0)
    {
        if (empty($gid2)) return [];
        $data = self::find([
            'columns' => 'uid,gid1,gid2',
            'gid2 = :gid2:',
            'bind'    => ['gid2' => $gid2],
        ])->toArray();

        return $data;
    }

    public static function getGroupUser($gid = 0, $field = 'gid2')
    {
        if (empty($gid)) return [];
        $data = self::find([
            'columns'    => 'uid,' . $field,
            'conditions' => $field . ' = :gid:',
            'bind'       => ['gid' => $gid],
        ])->toArray();

        return $data;
    }
}
