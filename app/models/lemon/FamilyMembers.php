<?php

namespace Imee\Models\Lemon;

class FamilyMembers extends BaseModel
{
    public static $primaryKey = 'fid';

    const ROLE_MEMBER = 'member';
    const ROLE_ADMIN = 'admin';
    const ROLE_OWNER = 'owner';

    public static $role = [
        self::ROLE_MEMBER => 'member',
        self::ROLE_ADMIN => 'admin',
        self::ROLE_OWNER => 'owner',
    ];

    public static function updateMember($fid, $uid, $data): array
    {
        $rec = static::useDb(self::SCHEMA)
            ->findFirst([
                "conditions" => "fid = :fid: and uid =:uid:",
                "bind" => ["fid" => $fid, "uid" => $uid],
            ]);

        if (!$rec) {
            return [false, '未查到该记录'];
        }

        foreach ($data as $key => $value) {
            $rec->$key = $value;
        }

        try {
            $result = $rec->save();
            return [true, $result];
        } catch (\PDOException $e) {
            return [false, $e->getMessage()];
        }
    }
}
