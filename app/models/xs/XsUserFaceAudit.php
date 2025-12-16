<?php

namespace Imee\Models\Xs;

class XsUserFaceAudit extends BaseModel
{
    protected static $primaryKey = 'id';

    const YES_LIKE = 1;
    const NO_LIKE = 0;

    const WAIT_AUDIT = 0;    // 待审核
    const AUDIT_SUCCESS = 1; // 审核成功
    const AUDIT_ERROR = 2;   // 审核失败

    const FIRST_AUTH_OP = 1;        // 首次认证
    const PREVENT_CHEATING_OP = 2;  // 防作弊认证
    const OPERATE_ADMIN_UPLOAD = 3; // 运营后台上传
    const OPERATE_ADMIN_UID = 4;    // 运营后台替换uid

    /**
     * 根据人脸ID获取审核结果
     * @param int $entityId
     * @return array
     */
    public static function getListByEntityId(array $entityIdArr, string $field = '*'): array
    {
        if (empty($entityIdArr)) {
            return [];
        }

        $list = self::getListByWhere([
            ['entity_id', 'IN', $entityIdArr]
        ], $field);

        return $list ? array_column($list, null, 'entity_id') : [];
    }
}