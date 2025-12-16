<?php
namespace Imee\Models\Xss;

use Imee\Comp\Common\Orm\Traits\ModelManagerTrait;

class CsmsOperateLog extends BaseModel
{
    use ModelManagerTrait;

    const addAudit = 1; // 新增审核项
    const editAudit = 2; // 修改审核项
    const addAuditField = 3; // 新增审核项字段
    const editAuditField = 4; // 编辑审核项字段
    const addAuditStage = 5; // 新增审核场景
    const editAuditStage = 6; // 编辑审核场景
    const addServicer = 7; // 新增服务商
    const editServicer = 8; // 编辑服务商
    const addServicerScene = 9; // 新增服务商场景
    const editServicerScene = 10; // 编辑服务商场景
    const addFeildScene = 11; // 新增字段场景
    const editFeildScene = 12; // 编辑字段场景
    const deleteFeildScene = 13; // 删除字段场景
    const addProduct = 14; // 新增字段场景
    const editProduct = 15; // 编辑字段场景
    const editMachine = 16; // 编辑机审代替人审
    const addMachine = 17; // 新增机审代替人审
}